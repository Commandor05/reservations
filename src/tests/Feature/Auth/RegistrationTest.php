<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Mail\RegistrationInvite;
use App\Models\User;
use App\Models\Company;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_user_can_register_with_token_for_company_owner_Role()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $userName = 'test company user';
        $userEmail = 'testcu@mail.ua';

        $this->actingAs($user)->post(route('companies.users.store', $company->id), [
            'email' => $userEmail,
        ]);

        $invitation = UserInvitation::where('email', $userEmail)->firstOrFail();

        Auth::logout();

        $response = $this->withSession(['invitation_token' => $invitation->token])->post('/register', [
            'name' => $userName,
            'email' => $userEmail,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $userName,
            'email' => $userEmail,
            'company_id' => $company->id,
            'role_id' => Role::COMPANY_OWNER->value,
        ]);

        $this->assertAuthenticated();

        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_user_can_register_with_token_for_guide_Role()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $userName = 'test company user';
        $userEmail = 'testcu@mail.ua';

        $this->actingAs($user)->post(route('companies.guides.store', $company->id), [
            'email' => $userEmail,
        ]);

        $invitation = UserInvitation::where('email', $userEmail)->firstOrFail();

        Auth::logout();

        $response = $this->withSession(['invitation_token' => $invitation->token])->post('/register', [
            'name' => $userName,
            'email' => $userEmail,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $userName,
            'email' => $userEmail,
            'company_id' => $company->id,
            'role_id' => Role::GUIDE->value,
        ]);

        $this->assertAuthenticated();

        $response->assertRedirect(RouteServiceProvider::HOME);
    }
}
