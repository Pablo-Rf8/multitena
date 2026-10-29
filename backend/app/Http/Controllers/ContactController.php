<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\Audit;
use App\Services\CrmScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    private const STATUSES = ['prospect', 'qualified', 'customer', 'inactive'];

    public function index(Request $request, CrmScope $scope): View
    {
        $query = $this->filtered($request, $scope)->with('branch');

        return view('contacts.index', [
            'contacts' => $query->latest()->paginate(15)->withQueryString(),
            'branches' => $request->user()->role === 'company_admin' ? $request->user()->company->branches()->orderBy('name')->get() : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('contacts.form', [
            'contact' => null,
            'branches' => $request->user()->company->branches()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Audit $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $contact = $request->user()->company->contacts()->create($data);
        $audit->record('contact.created', $contact, $contact->company_id, ['name' => $contact->name]);

        return redirect()->route('contacts.show', $contact)->with('status', 'Contacto creado.');
    }

    public function show(Request $request, int $contact, CrmScope $scope): View
    {
        $relations = ['branch', 'notes.author'];
        if ($request->user()->role !== 'employee' || in_array('opportunities.read', $request->user()->category?->permissions ?? [], true)) {
            $relations[] = 'opportunities';
        }
        $contact = $scope->contacts($request->user())->with($relations)->findOrFail($contact);

        return view('contacts.show', compact('contact'));
    }

    public function edit(Request $request, int $contact, CrmScope $scope): View
    {
        return view('contacts.form', [
            'contact' => $scope->contacts($request->user())->findOrFail($contact),
            'branches' => $request->user()->company->branches()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, int $contact, CrmScope $scope, Audit $audit): RedirectResponse
    {
        $contact = $scope->contacts($request->user())->findOrFail($contact);
        $data = $this->validated($request, $contact);
        DB::transaction(function () use ($contact, $data, $audit): void {
            $contact->update($data);
            $contact->opportunities()->update(['branch_id' => $contact->branch_id]);
            $audit->record('contact.updated', $contact, $contact->company_id, ['name' => $contact->name]);
        });

        return redirect()->route('contacts.show', $contact)->with('status', 'Contacto actualizado.');
    }

    public function export(Request $request, CrmScope $scope): StreamedResponse
    {
        $contacts = $this->filtered($request, $scope)->with('branch')->orderBy('id')->cursor();

        return response()->streamDownload(function () use ($contacts): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['ID', 'Nombre', 'Organización', 'Correo', 'Teléfono', 'Estado', 'Sucursal', 'Origen', 'Demo']);
            foreach ($contacts as $contact) {
                fputcsv($stream, array_map(self::csvCell(...), [
                    $contact->id, $contact->name, $contact->organization, $contact->email, $contact->phone,
                    $contact->status, $contact->branch->name, $contact->source, $contact->is_demo ? 'Sí' : 'No',
                ]));
            }
            fclose($stream);
        }, 'contactos.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request, CrmScope $scope): Builder
    {
        $query = $scope->contacts($request->user());
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')
                ->orWhere('organization', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%'));
        }
        if (in_array($request->query('status'), self::STATUSES, true)) {
            $query->where('status', $request->query('status'));
        }
        if ($request->user()->role === 'company_admin' && ctype_digit((string) $request->query('branch_id', ''))) {
            $query->where('branch_id', $request->query('branch_id'));
        }

        return $query;
    }

    private function validated(Request $request, ?Contact $contact = null): array
    {
        $company = $request->user()->company;
        $branchRule = Rule::exists('branches', 'id')->where('company_id', $company->id);
        if (! $contact || (string) $request->input('branch_id') !== (string) $contact->branch_id) {
            $branchRule->where('is_active', true);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'organization' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'source' => ['nullable', 'string', 'max:80'],
            'branch_id' => $request->user()->role === 'company_admin'
                ? ['required', 'integer', $branchRule]
                : ['prohibited'],
        ]);
        if ($request->user()->role !== 'company_admin') {
            $data['branch_id'] = $request->user()->branch_id;
        }

        return $data;
    }

    private static function csvCell(mixed $value): string
    {
        $text = (string) $value;

        return preg_match('/^[\s\x00-\x1f]*[=+\-@]/u', $text) ? "'".$text : $text;
    }
}
