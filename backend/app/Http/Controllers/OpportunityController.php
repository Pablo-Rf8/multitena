<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use App\Services\CrmScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OpportunityController extends Controller
{
    private const STAGES = ['new', 'qualified', 'proposal', 'won', 'lost'];

    public function index(Request $request, CrmScope $scope): View
    {
        $query = $this->filtered($request, $scope);
        $totals = (clone $query)->selectRaw('stage, count(*) as total, sum(amount_cents) as amount')->groupBy('stage')->get()->keyBy('stage');

        return view('opportunities.index', [
            'opportunities' => $query->with(['contact', 'branch'])->latest()->paginate(15)->withQueryString(),
            'totals' => $totals,
            'branches' => $request->user()->role === 'company_admin' ? $request->user()->company->branches()->orderBy('name')->get() : collect(),
        ]);
    }

    public function create(Request $request, CrmScope $scope): View
    {
        $contactId = (int) $request->query('contact', 0);
        $contact = $scope->contacts($request->user())->findOrFail($contactId);
        abort_unless($contact->branch->is_active, 422);

        return view('opportunities.form', ['opportunity' => null, 'contact' => $contact]);
    }

    public function store(Request $request, CrmScope $scope, Audit $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $contact = $scope->contacts($request->user())->findOrFail($data['contact_id']);
        abort_unless($contact->branch->is_active, 422);
        $opportunity = $contact->opportunities()->create($this->attributes($data, $contact->company_id, $contact->branch_id));
        $audit->record('opportunity.created', $opportunity, $contact->company_id, ['title' => $opportunity->title]);

        return redirect()->route('contacts.show', $contact)->with('status', 'Oportunidad creada.');
    }

    public function edit(Request $request, int $opportunity, CrmScope $scope): View
    {
        $opportunity = $scope->opportunities($request->user())->with('contact')->findOrFail($opportunity);

        return view('opportunities.form', ['opportunity' => $opportunity, 'contact' => $opportunity->contact]);
    }

    public function update(Request $request, int $opportunity, CrmScope $scope, Audit $audit): RedirectResponse
    {
        $opportunity = $scope->opportunities($request->user())->findOrFail($opportunity);
        $data = $this->validated($request, $opportunity->contact_id);
        $opportunity->update($this->attributes($data, $opportunity->company_id, $opportunity->branch_id));
        $audit->record('opportunity.updated', $opportunity, $opportunity->company_id, ['stage' => $opportunity->stage, 'title' => $opportunity->title]);

        return redirect()->route('contacts.show', $opportunity->contact_id)->with('status', 'Oportunidad actualizada.');
    }

    public function export(Request $request, CrmScope $scope): StreamedResponse
    {
        $items = $this->filtered($request, $scope)->with(['contact', 'branch'])->orderBy('id')->cursor();
        $currency = $request->user()->company->currency;

        return response()->streamDownload(function () use ($items, $currency): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['ID', 'Título', 'Contacto', 'Sucursal', 'Etapa', 'Monto', 'Moneda', 'Cierre estimado', 'Demo']);
            foreach ($items as $item) {
                fputcsv($stream, array_map(self::csvCell(...), [
                    $item->id, $item->title, $item->contact->name, $item->branch->name,
                    $item->stage, number_format($item->amount_cents / 100, 2, '.', ''), $currency,
                    $item->expected_close_on?->format('Y-m-d'), $item->is_demo ? 'Sí' : 'No',
                ]));
            }
            fclose($stream);
        }, 'oportunidades.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request, CrmScope $scope): Builder
    {
        $query = $scope->opportunities($request->user());
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where('title', 'like', '%'.$term.'%');
        }
        if (in_array($request->query('stage'), self::STAGES, true)) {
            $query->where('stage', $request->query('stage'));
        }
        if ($request->user()->role === 'company_admin' && ctype_digit((string) $request->query('branch_id', ''))) {
            $query->where('branch_id', $request->query('branch_id'));
        }

        return $query;
    }

    private function validated(Request $request, ?int $existingContactId = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'stage' => ['required', Rule::in(self::STAGES)],
            'expected_close_on' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'contact_id' => $existingContactId ? ['prohibited'] : ['required', 'integer'],
        ];

        return $request->validate($rules);
    }

    private function attributes(array $data, int $companyId, int $branchId): array
    {
        return [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'title' => trim($data['title']),
            'amount_cents' => (int) round((float) $data['amount'] * 100),
            'stage' => $data['stage'],
            'expected_close_on' => $data['expected_close_on'] ?? null,
            'description' => $data['description'] ?? null,
        ];
    }

    private static function csvCell(mixed $value): string
    {
        $text = (string) $value;

        return preg_match('/^[\s\x00-\x1f]*[=+\-@]/u', $text) ? "'".$text : $text;
    }
}
