<?php
namespace App\Http\Controllers;

use App\Models\{Contact, Opportunity};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Validation\Rule;

class MobileCrmController extends Controller
{
    private function permit(Request $request, string $permission): void
    {
        $user = $request->user();
        abort_if($user->role === 'super_admin' || ($user->role === 'employee' && (! $user->category?->is_active || ! in_array($permission, $user->category?->permissions ?? [], true))), 403);
    }

    public function contacts(Request $request)
    {
        $this->permit($request, 'contacts.read');
        $user = $request->user();
        return Contact::where('company_id', $user->company_id)
            ->when($user->role !== 'company_admin', fn ($query) => $query->where('branch_id', $user->branch_id))
            ->latest()->paginate(20);
    }

    public function opportunities(Request $request)
    {
        $this->permit($request, 'opportunities.read');
        $user = $request->user();
        return Opportunity::where('company_id', $user->company_id)
            ->when($user->role !== 'company_admin', fn ($query) => $query->where('branch_id', $user->branch_id))
            ->latest()->paginate(20);
    }

    public function createContact(Request $request): JsonResponse
    {
        $this->permit($request, 'contacts.write');
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'organization' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(['prospect', 'qualified', 'customer', 'inactive'])],
            'source' => ['nullable', 'string', 'max:80'],
            'branch_id' => $user->role === 'company_admin'
                ? ['required', Rule::exists('branches', 'id')->where('company_id', $user->company_id)->where('is_active', true)]
                : ['prohibited'],
        ]);
        $data['branch_id'] = $user->role === 'company_admin' ? $data['branch_id'] : $user->branch_id;
        $contact = $user->company->contacts()->create($data);
        return response()->json($contact, 201);
    }

    public function createOpportunity(Request $request): JsonResponse
    {
        $this->permit($request, 'opportunities.write');
        $user = $request->user();
        $data = $request->validate([
            'contact_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'stage' => ['required', Rule::in(['new', 'qualified', 'proposal', 'won', 'lost'])],
            'expected_close_on' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $contact = Contact::where('company_id', $user->company_id)
            ->when($user->role !== 'company_admin', fn ($query) => $query->where('branch_id', $user->branch_id))
            ->findOrFail($data['contact_id']);
        abort_unless($contact->branch->is_active, 422);
        $opportunity = $contact->opportunities()->create([
            'company_id' => $user->company_id, 'branch_id' => $contact->branch_id,
            'title' => $data['title'], 'amount_cents' => (int) round($data['amount'] * 100),
            'stage' => $data['stage'], 'expected_close_on' => $data['expected_close_on'] ?? null,
            'description' => $data['description'] ?? null,
        ]);
        return response()->json($opportunity, 201);
    }
}
