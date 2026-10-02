<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $status = 'approved', array $extra = []): User
    {
        return User::factory()->create(['role' => $role, 'status' => $status] + $extra);
    }

    private function clinicFixture(User $doctor): array
    {
        $patient = DB::table('patients')->insertGetId(['patient_code' => 'P-1', 'name' => 'مريض', 'age' => 40, 'created_at' => now(), 'updated_at' => now()]);
        $visit = DB::table('visits')->insertGetId(['patient_id' => $patient, 'doctor_id' => $doctor->id, 'visit_date' => today(), 'created_at' => now(), 'updated_at' => now()]);
        $medicine = DB::table('medicines')->insertGetId(['name' => 'Paracetamol', 'unit_price' => 2000, 'quantity' => 5, 'created_at' => now(), 'updated_at' => now()]);
        $testType = DB::table('lab_test_types')->insertGetId(['name' => 'CBC', 'price' => 15000, 'created_at' => now(), 'updated_at' => now()]);

        return compact('patient', 'visit', 'medicine', 'testType');
    }

    // ── Web ────────────────────────────────────────────────────────────────

    public function test_guests_are_sent_to_login_and_impersonation_route_is_gone(): void
    {
        $admin = $this->user('admin');

        $this->get('/')->assertRedirect('/login');
        $this->get('/login-as/' . $admin->id)->assertNotFound();
        $this->get('/login')->assertOk()->assertDontSee($admin->email);
        $this->assertGuest();
    }

    public function test_password_login_and_rejected_accounts(): void
    {
        $doctor = $this->user('doctor', 'approved', ['password' => 'Correct-Horse-1']);
        $this->post('/login', ['email' => $doctor->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $doctor->email, 'password' => 'Correct-Horse-1'])->assertRedirect('/');
        $this->assertAuthenticatedAs($doctor);
        $this->get('/')->assertOk()->assertSee('عيادة التشخيص');

        $this->post('/logout');
        $rejected = $this->user('pending', 'rejected', ['password' => 'Correct-Horse-1']);
        $this->post('/login', ['email' => $rejected->email, 'password' => 'Correct-Horse-1']);
        $this->assertGuest();
    }

    public function test_pharmacist_reaches_pharmacy_dashboard(): void
    {
        $this->actingAs($this->user('pharmacist'))->get('/')->assertOk();
    }

    public function test_non_admin_cannot_switch_dashboards_or_review_requests(): void
    {
        $doctor = $this->user('doctor');
        $pending = $this->user('pending', 'pending');
        $reqId = DB::table('role_requests')->insertGetId(['user_id' => $pending->id, 'requested_role' => 'doctor', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($doctor)->get('/switch-role/admin')->assertForbidden();
        $this->actingAs($doctor)->post("/role-requests/$reqId/approve")->assertForbidden();
        $this->actingAs($pending)->post("/role-requests/$reqId/approve")->assertForbidden();
        $this->assertSame('pending', $pending->fresh()->role);

        // State changes are POST-only, so a link or image on another site cannot trigger them.
        $admin = $this->user('admin');
        $this->actingAs($admin)->get("/role-requests/$reqId/approve")->assertStatus(405);
        $this->actingAs($admin)->post("/role-requests/$reqId/approve")->assertRedirect('/');
        $this->assertSame('doctor', $pending->fresh()->role);
        $this->assertSame('approved', $pending->fresh()->status);
    }

    public function test_pending_user_cannot_request_admin_role(): void
    {
        $pending = $this->user('pending', 'pending');
        $this->actingAs($pending)->get('/')->assertOk()->assertSee('تقديم طلب');
        $this->actingAs($pending)->post('/request-role', ['requested_role' => 'admin'])->assertSessionHasErrors('requested_role');
        $this->actingAs($pending)->post('/request-role', ['requested_role' => 'hr'])->assertRedirect('/');
        $this->assertDatabaseHas('role_requests', ['user_id' => $pending->id, 'requested_role' => 'hr', 'status' => 'pending']);
    }

    public function test_dashboards_only_show_the_users_own_notifications(): void
    {
        DB::table('notifications')->insert(['target_role' => 'admin', 'title' => 'ADMIN-ONLY-SECRET', 'message' => 'x', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->user('doctor'))->get('/')->assertOk()->assertDontSee('ADMIN-ONLY-SECRET');
    }

    // ── API ────────────────────────────────────────────────────────────────

    public function test_every_api_route_requires_a_token(): void
    {
        foreach (['/api/v1/admin/stats', '/api/v1/doctor/dashboard', '/api/v1/pharmacy/dashboard', '/api/v1/lab/dashboard',
                  '/api/v1/inventory/dashboard', '/api/v1/accountant/dashboard', '/api/v1/hr/dashboard', '/api/v1/role-requests',
                  '/api/v1/doctor/patients/1/history', '/api/v1/auth/me'] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
        foreach (['/api/v1/role-requests/1/approve', '/api/v1/pharmacy/prescriptions/1/dispense', '/api/v1/accountant/vouchers',
                  '/api/v1/hr/fingerprint', '/api/v1/auth/request-role'] as $url) {
            $this->postJson($url)->assertUnauthorized();
        }
    }

    public function test_roles_are_enforced_per_department(): void
    {
        Sanctum::actingAs($this->user('doctor'));
        $this->getJson('/api/v1/doctor/dashboard')->assertOk();
        $this->getJson('/api/v1/pharmacy/dashboard')->assertForbidden();
        $this->getJson('/api/v1/admin/stats')->assertForbidden();
        $this->postJson('/api/v1/accountant/vouchers', ['voucher_type' => 'income', 'category' => 'x', 'amount' => 1, 'description' => 'x'])->assertForbidden();

        Sanctum::actingAs($this->user('pending', 'pending'));
        $this->getJson('/api/v1/doctor/dashboard')->assertForbidden();

        Sanctum::actingAs($this->user('admin'));
        $this->getJson('/api/v1/pharmacy/dashboard')->assertOk();
        $this->getJson('/api/v1/admin/stats')->assertOk()->assertJsonMissingPath('data.doctors.0.password');
    }

    public function test_api_uses_the_authenticated_user_not_ids_from_the_request(): void
    {
        $doctor = $this->user('doctor');
        $other = $this->user('doctor');
        $f = $this->clinicFixture($doctor);

        Sanctum::actingAs($doctor);
        $this->postJson('/api/v1/doctor/visits', ['doctor_id' => $other->id, 'patient_id' => $f['patient'], 'diagnosis' => 'x'])->assertOk();
        $this->assertDatabaseMissing('visits', ['doctor_id' => $other->id]);

        $this->postJson('/api/v1/doctor/lab-requests', ['visit_id' => $f['visit'], 'patient_id' => 999999, 'test_type_id' => $f['testType']])->assertStatus(422);

        $pending = $this->user('pending', 'pending');
        Sanctum::actingAs($pending);
        $this->postJson('/api/v1/auth/request-role', ['user_id' => $doctor->id, 'requested_role' => 'doctor'])->assertOk();
        $this->assertSame('doctor', $doctor->fresh()->role); // another user's account is untouched
        $this->postJson('/api/v1/auth/request-role', ['requested_role' => 'admin'])->assertStatus(422);
    }

    public function test_prescription_is_dispensed_once_and_stock_never_goes_negative(): void
    {
        $doctor = $this->user('doctor');
        $f = $this->clinicFixture($doctor);
        $rx = DB::table('prescriptions')->insertGetId(['visit_id' => $f['visit'], 'doctor_id' => $doctor->id, 'patient_id' => $f['patient'], 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('prescription_items')->insert(['prescription_id' => $rx, 'medicine_id' => $f['medicine'], 'dosage' => 'x', 'duration' => 'x', 'created_at' => now(), 'updated_at' => now()]);

        Sanctum::actingAs($this->user('pharmacist'));
        $this->postJson("/api/v1/pharmacy/prescriptions/$rx/dispense")->assertOk();
        $this->postJson("/api/v1/pharmacy/prescriptions/$rx/dispense")->assertStatus(409);
        $this->assertSame(4, DB::table('medicines')->where('id', $f['medicine'])->value('quantity'));
        $this->assertSame(1, DB::table('vouchers')->where('category', 'pharmacy_income')->count());

        // Out of stock: the dispense is rolled back entirely.
        DB::table('medicines')->where('id', $f['medicine'])->update(['quantity' => 0]);
        $rx2 = DB::table('prescriptions')->insertGetId(['visit_id' => $f['visit'], 'doctor_id' => $doctor->id, 'patient_id' => $f['patient'], 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('prescription_items')->insert(['prescription_id' => $rx2, 'medicine_id' => $f['medicine'], 'dosage' => 'x', 'duration' => 'x', 'created_at' => now(), 'updated_at' => now()]);
        $this->postJson("/api/v1/pharmacy/prescriptions/$rx2/dispense")->assertStatus(409);
        $this->assertSame('pending', DB::table('prescriptions')->where('id', $rx2)->value('status'));
        $this->assertSame(0, DB::table('medicines')->where('id', $f['medicine'])->value('quantity'));
    }

    public function test_lab_result_rejects_script_urls_and_completes_once(): void
    {
        $doctor = $this->user('doctor');
        $f = $this->clinicFixture($doctor);
        $req = DB::table('lab_requests')->insertGetId(['visit_id' => $f['visit'], 'patient_id' => $f['patient'], 'doctor_id' => $doctor->id, 'test_type_id' => $f['testType'], 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);

        Sanctum::actingAs($this->user('lab_tech'));
        $this->postJson("/api/v1/lab/requests/$req/complete", ['result_summary' => 'ok', 'report_file_url' => 'javascript:alert(1)'])->assertStatus(422);
        $this->postJson("/api/v1/lab/requests/$req/complete", ['result_summary' => 'ok', 'report_file_url' => 'https://files.example/r.pdf'])->assertOk();
        $this->postJson("/api/v1/lab/requests/$req/complete", ['result_summary' => 'again'])->assertStatus(409);
        $this->assertSame(1, DB::table('vouchers')->where('category', 'lab_income')->count());
    }

    public function test_google_login_requires_server_config_and_valid_tokens(): void
    {
        config(['services.google.client_id' => null]);
        $this->postJson('/api/v1/auth/google', ['token' => 'anything'])->assertStatus(503);

        config(['services.google.client_id' => 'test-client.apps.googleusercontent.com']);
        $this->postJson('/api/v1/auth/google', ['token' => 'not-a-real-jwt'])->assertUnauthorized();
        $this->postJson('/api/v1/auth/google', [])->assertStatus(422);
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    public function test_rejecting_a_request_revokes_the_users_api_tokens(): void
    {
        $pending = $this->user('pending', 'pending');
        $pending->createToken('t');
        $reqId = DB::table('role_requests')->insertGetId(['user_id' => $pending->id, 'requested_role' => 'doctor', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);

        Sanctum::actingAs($this->user('admin'));
        $this->postJson("/api/v1/role-requests/$reqId/reject")->assertOk();
        $this->postJson("/api/v1/role-requests/$reqId/approve")->assertNotFound(); // already reviewed
        $this->assertSame(0, $pending->tokens()->count());
        $this->assertSame('pending', $pending->fresh()->role);
    }
}
