<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Mail\RegistrationInvite;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CompanyGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_view_his_companies_guides()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $secondUser = User::factory()->guide()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(
            route(
                'companies.guides.index',
                $company->id
            )
        );

        $response->assertOk()->assertSeeText($secondUser->name);
    }

    public function test_company_owner_cannot_view_other_companies_guides()
    {
        $company = Company::factory()->create();
        $companyTwo = Company::factory()->create();

        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(
            route(
                'companies.guides.index',
                $companyTwo->id
            )
        );

        $response->assertForbidden();
    }

    public function test_company_owner_can_send_invite_to_guide_for_his_company()
    {
        Mail::fake();

        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $userEmail = 'testcu@mail.ua';

        $response = $this->actingAs($user)->post(
            route('companies.guides.store', $company->id),
            [
                'email' => $userEmail,
            ]
        );

        Mail::assertSent(RegistrationInvite::class);

        $response->assertRedirect(route('companies.guides.index', $company->id));

        $this->assertDatabaseHas('user_invitations', [
            'email' => $userEmail,
            'registered_at' => null,
            'company_id' => $company->id,
            'role_id' => Role::GUIDE->value,
        ]);
    }

    public function test_invitation_can_be_sent_only_once_for_user()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $userEmail = 'testdoubleguide@mail.ua';

        $this->actingAs($user)->post(route('companies.guides.store', $company->id), [
            'email' => $userEmail,
        ]);

        $response = $this->actingAs($user)->post(route('companies.guides.store', $company->id), [
            'email' => $userEmail,
        ]);

        $response->assertInvalid(['email' => 'Invitation with this email address already requested']);
    }

    public function test_company_owner_cannot_create_guide_for_other_company()
    {
        $company = Company::factory()->create();
        $companyTwo = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $userEmail = 'testcu@mail.ua';

        $response = $this->actingAs($user)->post(
            route('companies.guides.store', $companyTwo->id),
            [
                'email' => $userEmail,
            ]
        );

        $response->assertForbidden();
    }

    public function test_company_owner_can_edit_guide_for_his_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $userName = 'updated company user';
        $userEmail = 'updated@mail.ua';

        $response = $this->actingAs($user)->put(
            route(
                'companies.guides.update',
                [$company->id, $user->id]
            ),
            [
                'name' => $userName,
                'email' => $userEmail,
            ]
        );

        $response->assertRedirect(route('companies.guides.index', $company->id));

        $this->assertDatabaseHas('users', [
            'name' => $userName,
            'email' => $userEmail,
            'company_id' => $company->id,
        ]);
    }

    public function test_company_owner_cannot_edit_guide_for_other_company()
    {
        $company = Company::factory()->create();
        $companyTwo = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $userName = 'updated company user';
        $userEmail = 'updated@mail.ua';

        $response = $this->actingAs($user)->put(
            route(
                'companies.guides.update',
                [$companyTwo->id, $user->id]
            ),
            [
                'name' => $userName,
                'email' => $userEmail,
            ]
        );

        $response->assertForbidden();
    }

    public function test_company_owner_can_delete_guide_for_his_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->delete(
            route('companies.guides.destroy', [$company->id, $user->id])
        );

        $response->assertRedirect(route('companies.guides.index', $company->id));

        $this->assertSoftDeleted($user->fresh());
    }

    public function test_company_owner_cannot_delete_guide_for_other_company()
    {
        $company = Company::factory()->create();
        $companyTwo = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->delete(
            route('companies.guides.destroy', [$companyTwo->id, $user->id])
        );

        $response->assertForbidden();
    }
}
