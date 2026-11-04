<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Models\CredentialField;
use App\Models\CredentialTemplate;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CredentialController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Credential::class);
        $user = $request->user();
        $query = Credential::where('company_id', $user->company_id)->where(function ($q) use ($user) {
            $q->where('owner_id', $user->id)->orWhereHas('grants', fn ($g) => $g->where('user_id', $user->id));
        });
        if ($user->role === 'employee') {
            $query->where('visibility', 'shared');
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.trim((string) $request->query('q')).'%');
        }
        if ($request->filled('template') && ctype_digit((string) $request->query('template'))) {
            $query->where('template_id', $request->query('template'));
        }
        if (in_array($request->query('visibility'), ['private', 'shared'], true)) {
            $query->where('visibility', $request->query('visibility'));
        }
        $templateQuery = CredentialTemplate::where('company_id', $user->company_id);
        if ($user->role === 'employee') {
            $templateQuery->whereHas('credentials', fn ($q) => $q->where('visibility', 'shared')->whereHas('grants', fn ($g) => $g->where('user_id', $user->id)));
        }

        return view('credentials.index', [
            'credentials' => $query->with(['template', 'owner'])->latest()->paginate(15)->withQueryString(),
            'templates' => $templateQuery->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Credential::class);
        $templates = CredentialTemplate::where('company_id', $request->user()->company_id)->with('fields')->orderBy('name')->get();
        $template = $templates->firstWhere('id', (int) $request->query('template'));

        return view('credentials.form', ['credential' => null, 'templates' => $templates, 'template' => $template, 'values' => collect()]);
    }

    public function store(Request $request, Audit $audit): RedirectResponse
    {
        Gate::authorize('create', Credential::class);
        $data = $request->validate(['title' => ['required', 'string', 'max:160'], 'template_id' => ['required', 'integer'], 'visibility' => ['required', Rule::in(['private', 'shared'])], 'values' => ['nullable', 'array']]);
        $template = CredentialTemplate::where('company_id', $request->user()->company_id)->findOrFail($data['template_id']);
        $values = $this->validatedValues($request, $template, null);
        $credential = DB::transaction(function () use ($request, $data, $template, $values) {
            $credential = Credential::create(['company_id' => $request->user()->company_id, 'owner_id' => $request->user()->id, 'template_id' => $template->id, 'title' => $data['title'], 'visibility' => $data['visibility']]);
            $this->saveValues($credential, $template, $values);

            return $credential;
        });
        $audit->record('credential.created', $credential, $credential->company_id);

        return redirect()->route('credentials.show', $credential)->with('status', 'Credencial creada.');
    }

    public function show(Request $request, Credential $credential, Audit $audit): View
    {
        Gate::authorize('view', $credential);
        $audit->record('credential.viewed', $credential, $credential->company_id);
        $credential->load(['template.fields', 'values', 'owner', 'grants.user']);

        return view('credentials.show', [
            'credential' => $credential,
            'values' => $credential->values->keyBy('field_id'),
            'canEdit' => Gate::allows('update', $credential),
            'canShare' => Gate::allows('share', $credential),
            'canReveal' => Gate::allows('reveal', $credential),
            'users' => Gate::allows('share', $credential)
                ? User::where('company_id', $credential->company_id)->where('is_active', true)->where('id', '!=', $credential->owner_id)
                    ->where(fn ($q) => $q->where('role', '!=', 'employee')->orWhere('approval_status', 'approved'))
                    ->when($credential->visibility === 'private', fn ($q) => $q->whereIn('role', ['company_admin', 'branch_admin']))->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function edit(Request $request, Credential $credential): View
    {
        Gate::authorize('update', $credential);
        $credential->load(['template.fields', 'values']);

        return view('credentials.form', ['credential' => $credential, 'template' => $credential->template,
            'templates' => CredentialTemplate::where('company_id', $request->user()->company_id)->orderBy('name')->get(), 'values' => $credential->values->keyBy('field_id')]);
    }

    public function update(Request $request, Credential $credential, Audit $audit): RedirectResponse
    {
        Gate::authorize('update', $credential);
        $data = $request->validate(['title' => ['required', 'string', 'max:160'], 'visibility' => ['required', Rule::in(['private', 'shared'])], 'values' => ['nullable', 'array']]);
        if ($credential->owner_id !== $request->user()->id && $data['visibility'] !== $credential->visibility) {
            abort(403);
        }
        if ($data['visibility'] === 'private' && $credential->visibility !== 'private' && $credential->grants()->whereHas('user', fn ($q) => $q->where('role', 'employee'))->exists()) {
            throw ValidationException::withMessages(['visibility' => 'Revocá primero los accesos de empleados antes de hacerla privada.']);
        }
        $template = $credential->template()->with('fields')->firstOrFail();
        $values = $this->validatedValues($request, $template, $credential);
        DB::transaction(function () use ($credential, $data, $template, $values) {
            $credential->update(['title' => $data['title'], 'visibility' => $data['visibility']]);
            $this->saveValues($credential, $template, $values);
        });
        $audit->record('credential.updated', $credential, $credential->company_id);

        return redirect()->route('credentials.show', $credential)->with('status', 'Credencial actualizada.');
    }

    public function destroy(Credential $credential, Audit $audit): RedirectResponse
    {
        Gate::authorize('delete', $credential);
        $audit->record('credential.deleted', $credential, $credential->company_id);
        $credential->delete();

        return redirect()->route('credentials.index')->with('status', 'Credencial eliminada.');
    }

    public function grant(Request $request, Credential $credential, Audit $audit): RedirectResponse
    {
        Gate::authorize('share', $credential);
        $data = $request->validate(['user_id' => ['required', 'integer'], 'level' => ['required', Rule::in(['view', 'edit'])], 'can_reveal' => ['nullable', 'boolean']]);
        $user = User::where('company_id', $credential->company_id)->where('is_active', true)
            ->where(fn ($q) => $q->where('role', '!=', 'employee')->orWhere('approval_status', 'approved'))->findOrFail($data['user_id']);
        abort_if($user->id === $credential->owner_id, 422);
        abort_if($credential->visibility === 'private' && ! in_array($user->role, ['company_admin', 'branch_admin'], true), 403);
        $credential->grants()->updateOrCreate(['user_id' => $user->id], ['level' => $data['level'], 'can_reveal' => (bool) ($data['can_reveal'] ?? false)]);
        $audit->record('credential.grant_updated', $credential, $credential->company_id, ['user_id' => $user->id]);

        return back()->with('status', 'Acceso actualizado.');
    }

    public function revoke(Credential $credential, User $user, Audit $audit): RedirectResponse
    {
        Gate::authorize('share', $credential);
        abort_unless($user->company_id === $credential->company_id, 404);
        $credential->grants()->where('user_id', $user->id)->delete();
        $audit->record('credential.grant_revoked', $credential, $credential->company_id, ['user_id' => $user->id]);

        return back()->with('status', 'Acceso revocado.');
    }

    public function reveal(Credential $credential, CredentialField $field, Audit $audit): JsonResponse
    {
        Gate::authorize('reveal', $credential);
        abort_unless($field->template_id === $credential->template_id && $field->type === 'password', 404);
        $value = $credential->values()->where('field_id', $field->id)->firstOrFail();
        abort_unless($value->secret_ciphertext !== null, 404);
        $audit->record('credential.secret_revealed', $credential, $credential->company_id, ['field_id' => $field->id]);

        return response()->json(['value' => Crypt::decryptString($value->secret_ciphertext)])->header('Cache-Control', 'no-store, private')->header('Pragma', 'no-cache');
    }

    private function validatedValues(Request $request, CredentialTemplate $template, ?Credential $credential): array
    {
        $fields = $template->fields;
        $existingSecrets = $credential ? $credential->values()->whereNotNull('secret_ciphertext')->pluck('field_id')->all() : [];
        $raw = $request->input('values', []);
        if (! is_array($raw) || array_diff(array_keys($raw), $fields->modelKeys())) {
            throw ValidationException::withMessages(['values' => 'Los campos no pertenecen a la plantilla.']);
        }
        $validated = [];
        foreach ($fields as $field) {
            $input = $raw[$field->id] ?? null;
            if ($field->type === 'password' && in_array($field->id, $existingSecrets, true) && ($input === null || $input === '')) {
                continue;
            }
            $rule = [$field->required ? 'required' : 'nullable', 'string', 'max:2048'];
            if ($field->type === 'url') {
                $rule[] = 'url:http,https';
            }
            $key = 'values.'.$field->id;
            $validator = Validator::make(['values' => [$field->id => $input]], [$key => $rule]);
            $validator->setAttributeNames([$key => $field->name]);
            $validated[$field->id] = data_get($validator->validate(), $key);
        }

        return $validated;
    }

    private function saveValues(Credential $credential, CredentialTemplate $template, array $values): void
    {
        foreach ($template->fields as $field) {
            if (! array_key_exists($field->id, $values)) {
                continue;
            }
            $value = $values[$field->id];
            if ($value === null || $value === '') {
                $credential->values()->where('field_id', $field->id)->delete();

                continue;
            }
            $credential->values()->updateOrCreate(['field_id' => $field->id], $field->type === 'password'
                ? ['value' => null, 'secret_ciphertext' => Crypt::encryptString($value)]
                : ['value' => $value, 'secret_ciphertext' => null]);
        }
    }
}
