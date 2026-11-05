<?php

namespace App\Http\Controllers;

use App\Models\CredentialTemplate;
use App\Models\CredentialTemplateShare;
use App\Models\User;
use App\Services\Audit;
use App\Services\ShareLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CredentialTemplateController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CredentialTemplate::class);
        $admin = in_array($request->user()->role, ['company_admin', 'branch_admin'], true);
        $templates = CredentialTemplate::where('company_id', $request->user()->company_id)->with('fields')
            ->when(! $admin, fn ($q) => $q->whereHas('shares', fn ($s) => $s->where('user_id', $request->user()->id)->available()))
            ->when($admin, fn ($q) => $q->withCount('credentials'))
            ->orderBy('name')->get();

        return view('credentials.templates.index', ['templates' => $templates, 'canManage' => $admin]);
    }

    public function show(Request $request, CredentialTemplate $template, Audit $audit, ShareLimits $limits): Response
    {
        Gate::authorize('view', $template);
        if ($request->user()->role === 'employee') {
            DB::transaction(function () use ($template, $request, $limits, $audit) {
                $shares = $template->shares()->where('user_id', $request->user()->id)->available()->orderBy('id')->pluck('id');
                foreach ($shares as $id) {
                    if ($limits->consume('credential_template_shares', $id, $request->user()->id)) {
                        $audit->record('credential_template.viewed', $template, $template->company_id);

                        return;
                    }
                }
                abort(403);
            }, 3);
        }
        $template->load('fields');

        return response()->view('credentials.templates.show', [
            'template' => $template,
            'canShare' => Gate::allows('share', $template),
            'users' => Gate::allows('share', $template)
                ? User::where('company_id', $template->company_id)->where('role', 'employee')
                    ->where('is_active', true)->where('approval_status', 'approved')->orderBy('name')->get() : collect(),
            'shares' => Gate::allows('share', $template) ? $template->shares()->with('user')->latest()->get() : collect(),
        ])->header('Cache-Control', 'no-store, private')->header('Pragma', 'no-cache');
    }

    public function create(): View
    {
        Gate::authorize('create', CredentialTemplate::class);

        return view('credentials.templates.form', ['template' => null]);
    }

    public function store(Request $request, Audit $audit): RedirectResponse
    {
        Gate::authorize('create', CredentialTemplate::class);
        $data = $this->validated($request);
        $template = DB::transaction(function () use ($request, $data) {
            $template = CredentialTemplate::create(['company_id' => $request->user()->company_id, 'name' => $data['name']]);
            $this->syncFields($template, $data['fields']);

            return $template;
        });
        $audit->record('credential_template.created', $template, $template->company_id);

        return redirect()->route('credential-templates.index')->with('status', 'Plantilla creada.');
    }

    public function edit(CredentialTemplate $template): View
    {
        Gate::authorize('update', $template);

        return view('credentials.templates.form', ['template' => $template->load('fields')->loadCount('credentials')]);
    }

    public function update(Request $request, CredentialTemplate $template, Audit $audit): RedirectResponse
    {
        Gate::authorize('update', $template);
        $data = $this->validated($request);
        DB::transaction(function () use ($template, $data) {
            $template->update(['name' => $data['name']]);
            $this->syncFields($template, $data['fields']);
        });
        $audit->record('credential_template.updated', $template, $template->company_id);

        return redirect()->route('credential-templates.index')->with('status', 'Plantilla actualizada.');
    }

    public function destroy(CredentialTemplate $template, Audit $audit): RedirectResponse
    {
        Gate::authorize('delete', $template);
        if ($template->credentials()->exists()) {
            throw ValidationException::withMessages(['template' => 'La plantilla está en uso. Eliminá primero sus credenciales.']);
        }
        $audit->record('credential_template.deleted', $template, $template->company_id);
        $template->delete();

        return redirect()->route('credential-templates.index')->with('status', 'Plantilla eliminada.');
    }

    public function share(Request $request, CredentialTemplate $template, Audit $audit, ShareLimits $limits): RedirectResponse
    {
        Gate::authorize('share', $template);
        $data = $limits->validated($request);
        $user = User::where('company_id', $template->company_id)->where('role', 'employee')
            ->where('is_active', true)->where('approval_status', 'approved')->findOrFail($data['user_id']);
        $share = DB::transaction(function () use ($template, $user, $request, $data, $limits) {
            if ($template->shares()->where('user_id', $user->id)->available()->exists()) {
                throw ValidationException::withMessages(['user_id' => 'Este usuario ya tiene acceso activo a la plantilla.']);
            }

            return $template->shares()->create([
                'company_id' => $template->company_id,
                'user_id' => $user->id,
                'shared_by_id' => $request->user()->id,
                'max_accesses' => $data['max_accesses'] ?? null,
                'expires_at' => $limits->expiresAt($data),
            ]);
        }, 3);
        $audit->record('credential_template.shared', $template, $template->company_id, ['share_id' => $share->id, 'user_id' => $user->id]);

        return back()->with('status', 'Plantilla compartida.');
    }

    public function revokeShare(CredentialTemplate $template, CredentialTemplateShare $share, Audit $audit): RedirectResponse
    {
        Gate::authorize('share', $template);
        abort_unless($share->template_id === $template->id && $share->company_id === $template->company_id, 404);
        if ($share->revoked_at === null) {
            $share->update(['revoked_at' => now()]);
            $audit->record('credential_template.share_revoked', $template, $template->company_id, ['share_id' => $share->id]);
        }

        return back()->with('status', 'Acceso revocado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'fields' => ['required', 'array', 'min:1', 'max:30'],
            'fields.*.id' => ['nullable', 'integer'],
            'fields.*.name' => ['required', 'string', 'max:120'],
            'fields.*.type' => ['required', Rule::in(['text', 'password', 'url'])],
            'fields.*.required' => ['nullable', 'boolean'],
        ]);
    }

    private function syncFields(CredentialTemplate $template, array $fields): void
    {
        $existing = $template->fields()->get()->keyBy('id');
        $ids = array_filter(array_column($fields, 'id'));
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['fields' => 'Hay campos repetidos.']);
        }
        foreach ($ids as $id) {
            if (! $existing->has((int) $id)) {
                throw ValidationException::withMessages(['fields' => 'Un campo no pertenece a esta plantilla.']);
            }
        }
        foreach ($existing as $field) {
            if (in_array($field->id, $ids)) {
                continue;
            }
            if ($field->values()->where(fn ($query) => $query->whereNotNull('value')->orWhereNotNull('secret_ciphertext'))->exists()) {
                throw ValidationException::withMessages(['fields' => 'No se puede quitar un campo que ya contiene datos.']);
            }
            $field->values()->delete();
            $field->delete();
        }
        foreach (array_values($fields) as $position => $data) {
            $field = isset($data['id']) ? $existing->get((int) $data['id']) : null;
            if ($field && $field->type !== $data['type'] && $field->values()->where(fn ($query) => $query->whereNotNull('value')->orWhereNotNull('secret_ciphertext'))->exists()) {
                throw ValidationException::withMessages(['fields' => 'No se puede cambiar el tipo de un campo que ya contiene datos.']);
            }
            if ($field && $field->type !== $data['type']) {
                $field->values()->delete();
            }
            $attributes = ['name' => $data['name'], 'type' => $data['type'], 'position' => $position, 'required' => (bool) ($data['required'] ?? false)];
            $field ? $field->update($attributes) : $template->fields()->create($attributes);
        }
    }
}
