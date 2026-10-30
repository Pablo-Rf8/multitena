<?php
namespace App\Http\Controllers;

use App\Models\{Category, Company, CompanySetting, Device, MobileSession, User};
use App\Services\AccessNotice;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkforceController extends Controller
{
    private const PERMISSIONS = ['contacts.read', 'contacts.write', 'opportunities.read', 'opportunities.write'];

    public function index(Request $request): View
    {
        $id = $request->user()->company_id;
        return view('workforce.index', [
            'employees' => User::where('company_id', $id)->where('role', 'employee')->with('category')->latest()->paginate(20),
            'categories' => Category::where('company_id', $id)->orderBy('name')->get(),
            'branches' => $request->user()->company->branches()->orderBy('name')->get(),
            'devices' => Device::where('company_id', $id)->latest()->limit(50)->get(),
            'sessions' => MobileSession::where('company_id', $id)->with(['user', 'device'])->latest()->limit(50)->get(),
            'settings' => CompanySetting::firstOrCreate(['company_id' => $id]),
            'notifications' => DB::table('access_notifications')->where('company_id', $id)->where('recipient_id', $request->user()->id)->whereNull('read_at')->latest()->limit(20)->get(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        return view('workforce.create', ['companies' => Company::orderBy('name')->get()]);
    }

    public function createForCompany(Request $request): View
    {
        return view('workforce.register', ['branches' => $request->user()->company->branches()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function storeForCompany(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $company = $request->user()->company;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $company->id)->where('is_active', true)],
        ]);
        if (! $this->allowedDomain($data['email'], CompanySetting::firstOrCreate(['company_id' => $company->id])->email_domains ?? [])) return back()->withErrors(['email' => 'Dominio no autorizado.'])->withInput();
        $employee = $this->createEmployee($company, $data['name'], $data['email'], $data['password'], $data['phone'] ?? null);
        $employee->update(['branch_id' => $data['branch_id']]);
        return redirect()->route('workforce.index')->with('status', 'Empleado pendiente de aprobación.');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'company_id' => ['required', Rule::exists('companies', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        $company = Company::findOrFail($data['company_id']);
        $settings = CompanySetting::firstOrCreate(['company_id' => $company->id]);
        if (! $this->allowedDomain($data['email'], $settings->email_domains ?? [])) {
            return back()->withErrors(['email' => 'Dominio no autorizado.'])->withInput();
        }
        $user = $this->createEmployee($company, $data['name'], $data['email'], $data['password'], $data['phone'] ?? null);
        return back()->with('status', 'Empleado '.$user->email.' pendiente de aprobación.');
    }

    public function import(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $data = $request->validate([
            'company_id' => ['required', Rule::exists('companies', 'id')->where('is_active', true)],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
        ]);
        $company = Company::findOrFail($data['company_id']);
        $settings = CompanySetting::firstOrCreate(['company_id' => $company->id]);
        $handle = fopen($data['file']->getRealPath(), 'rb');
        if (fgetcsv($handle) !== ['name', 'email', 'password']) {
            fclose($handle);
            return back()->withErrors(['file' => 'Encabezado requerido: name,email,password']);
        }
        $count = 0;
        $errors = [];
        $line = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if ($line > 1001) { $errors[] = 'Máximo 1000 filas.'; break; }
            if (count($row) !== 3) { $errors[] = "Fila $line: columnas incorrectas."; continue; }
            [$name, $email, $password] = $row;
            $email = strtolower(trim($email));
            if (! trim($name) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || User::where('email', $email)->exists() || ! $this->allowedDomain($email, $settings->email_domains ?? [])) {
                $errors[] = "Fila $line: datos inválidos o correo duplicado.";
                continue;
            }
            $this->createEmployee($company, trim($name), $email, $password);
            $count++;
        }
        fclose($handle);
        return back()->with('status', "$count empleados importados.")->with('import_errors', $errors);
    }

    private function createEmployee(Company $company, string $name, string $email, string $password, ?string $phone = null): User
    {
        return DB::transaction(function () use ($company, $name, $email, $password, $phone) {
            $user = $company->users()->create([
                'branch_id' => $company->branches()->where('is_central', true)->firstOrFail()->id,
                'name' => $name, 'email' => $email, 'phone' => $phone, 'password' => $password,
                'role' => 'employee', 'approval_status' => 'pending',
            ]);
            AccessNotice::admins($company->id, 'employee_pending', $user->id, 'Empleado pendiente de aprobación');
            return $user;
        });
    }

    public function review(Request $request, int $employee): RedirectResponse
    {
        $user = User::where('company_id', $request->user()->company_id)->where('role', 'employee')->findOrFail($employee);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('company_id', $user->company_id)->where('is_active', true)],
        ]);
        abort_unless($user->approval_status === 'pending', 409);
        $user->update(['approval_status' => $data['decision'], 'category_id' => $data['category_id'] ?? null, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        DB::table('access_notifications')->where('company_id', $user->company_id)->where('kind', 'employee_pending')->where('subject_id', $user->id)->update(['read_at' => now()]);
        AccessNotice::user($user, 'employee_'.$data['decision'], $user->id, 'Solicitud '.($data['decision'] === 'approved' ? 'aprobada' : 'rechazada'));
        return back()->with('status', 'Solicitud revisada.');
    }

    public function editEmployee(Request $request, int $employee): View
    {
        $user = User::where('company_id', $request->user()->company_id)->where('role', 'employee')->findOrFail($employee);
        return view('workforce.employee', ['employee' => $user, 'categories' => Category::where('company_id', $user->company_id)->where('is_active', true)->orderBy('name')->get(), 'branches' => $request->user()->company->branches()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function updateEmployee(Request $request, int $employee): RedirectResponse
    {
        $user = User::where('company_id', $request->user()->company_id)->where('role', 'employee')->findOrFail($employee);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $user->company_id)->where('is_active', true)],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('company_id', $user->company_id)->where('is_active', true)],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['email'] = strtolower(trim($data['email']));
        if (! $this->allowedDomain($data['email'], CompanySetting::firstOrCreate(['company_id' => $user->company_id])->email_domains ?? [])) {
            return back()->withErrors(['email' => 'Dominio no autorizado.'])->withInput();
        }
        $user->update($data);
        if (! $user->is_active) $this->revokeEmployeeSessions($user, 'account_suspended');
        return redirect()->route('workforce.index')->with('status', 'Empleado actualizado.');
    }

    public function employeeStatus(Request $request, int $employee): RedirectResponse
    {
        $user = User::where('company_id', $request->user()->company_id)->where('role', 'employee')->findOrFail($employee);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $user->update($data);
        if (! $user->is_active) $this->revokeEmployeeSessions($user, 'account_suspended');
        return back()->with('status', 'Estado del empleado actualizado.');
    }

    private function revokeEmployeeSessions(User $user, string $reason): void
    {
        foreach (MobileSession::where('user_id', $user->id)->where('status', 'active')->get() as $session) {
            if ($session->token_id) DB::table('personal_access_tokens')->where('id', $session->token_id)->delete();
            $session->update(['status' => 'revoked', 'end_reason' => $reason, 'ended_at' => now()]);
        }
    }

    public function category(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('categories')->where('company_id', $request->user()->company_id)], 'permissions' => ['array'], 'permissions.*' => [Rule::in(self::PERMISSIONS)]]);
        Category::create(['company_id' => $request->user()->company_id, 'name' => $data['name'], 'permissions' => $data['permissions'] ?? []]);
        return back()->with('status', 'Categoría creada.');
    }

    public function updateCategory(Request $request, int $category): RedirectResponse
    {
        $category = Category::where('company_id', $request->user()->company_id)->findOrFail($category);
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('categories')->where('company_id', $category->company_id)->ignore($category->id)], 'permissions' => ['array'], 'permissions.*' => [Rule::in(self::PERMISSIONS)], 'is_active' => ['required', 'boolean']]);
        $category->update(['name' => $data['name'], 'permissions' => $data['permissions'] ?? [], 'is_active' => $data['is_active']]);
        if (! $category->is_active) {
            foreach (User::where('category_id', $category->id)->get() as $user) $this->revokeEmployeeSessions($user, 'category_disabled');
        }
        return back()->with('status', 'Categoría actualizada.');
    }

    public function deleteCategory(Request $request, int $category): RedirectResponse
    {
        $category = Category::where('company_id', $request->user()->company_id)->findOrFail($category);
        abort_if(User::where('category_id', $category->id)->exists(), 409, 'Desasigná los empleados antes de eliminar la categoría.');
        $category->delete();
        return back()->with('status', 'Categoría eliminada.');
    }

    public function assignCategory(Request $request, int $employee): RedirectResponse
    {
        $user = User::where('company_id', $request->user()->company_id)->where('role', 'employee')->findOrFail($employee);
        $data = $request->validate(['category_id' => ['nullable', Rule::exists('categories', 'id')->where('company_id', $user->company_id)->where('is_active', true)]]);
        $user->update(['category_id' => $data['category_id'] ?? null]);
        return back()->with('status', 'Categoría actualizada.');
    }

    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email_domains' => ['nullable', 'string', 'max:1000'],
            'employee_access_enabled' => ['required', 'boolean'],
            'require_device_approval' => ['required', 'boolean'],
            'token_lifetime_hours' => ['required', 'integer', 'between:1,2160'],
        ]);
        $domains = array_values(array_filter(array_map(fn ($d) => strtolower(trim($d)), explode(',', $data['email_domains'] ?? ''))));
        foreach ($domains as $domain) {
            if (! filter_var('test@'.$domain, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(['email_domains' => 'Dominio inválido.']);
            }
        }
        CompanySetting::updateOrCreate(['company_id' => $request->user()->company_id], [
            'email_domains' => $domains,
            'employee_access_enabled' => $data['employee_access_enabled'],
            'require_device_approval' => $data['require_device_approval'],
            'token_lifetime_hours' => $data['token_lifetime_hours'],
        ]);
        return back()->with('status', 'Configuración guardada.');
    }

    public function device(Request $request, int $device): RedirectResponse
    {
        $device = Device::where('company_id', $request->user()->company_id)->findOrFail($device);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'revoked'])]]);
        $device->update(['status' => $data['status']]);
        DB::table('access_notifications')->where('company_id', $device->company_id)->where('kind', 'device_pending')->where('subject_id', $device->id)->update(['read_at' => now()]);
        if ($data['status'] === 'revoked') {
            foreach (MobileSession::where('device_id', $device->id)->where('status', 'active')->get() as $session) {
                DB::table('personal_access_tokens')->where('id', $session->token_id)->delete();
                $session->update(['status' => 'revoked', 'end_reason' => 'device_revoked', 'ended_at' => now()]);
            }
        }
        AccessNotice::user(User::findOrFail($device->user_id), 'device_'.$data['status'], $device->id, 'Dispositivo '.($data['status'] === 'approved' ? 'autorizado' : 'revocado'));
        return back()->with('status', 'Dispositivo actualizado.');
    }

    public function endSession(Request $request, int $session): RedirectResponse
    {
        $session = MobileSession::where('company_id', $request->user()->company_id)->findOrFail($session);
        abort_unless($session->status === 'active', 409);
        if ($session->token_id) DB::table('personal_access_tokens')->where('id', $session->token_id)->delete();
        $session->update(['status' => 'revoked', 'end_reason' => 'admin_revoked', 'ended_at' => now()]);
        return back()->with('status', 'Sesión finalizada.');
    }

    private function allowedDomain(string $email, array $domains): bool
    {
        return $domains === [] || in_array(Str::afterLast($email, '@'), $domains, true);
    }
}
