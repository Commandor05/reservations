<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CompanyUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_company_users_page()
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('companies.users.index', $company->id));

        $response->assertOK();
    }

    public function test_admin_can_create_user_for_a_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create();
        $userName = 'test company user';
        $userEmail = 'testcu@mail.ua';

        $response = $this->actingAs($user)->post(
            route('companies.users.store', $company->id),
            [
                'name' => $userName,
                'email' => $userEmail,
                'password' => 'password',
            ]
        );

        $response->assertRedirect(route('companies.users.index', $company->id));

        $this->assertDatabaseHas('users', [
            'name' => $userName,
            'email' => $userEmail,
        ]);
    }

    public function test_admin_can_edit_user_for_a_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create(['company_id' => $company->id]);
        $userName = 'updated company user';
        $userEmail = 'updated@mail.ua';

        $response = $this->actingAs($user)->put(
            route(
                'companies.users.update',
                [$company->id, $user->id]
            ),
            [
                'name' => $userName,
                'email' => $userEmail,
            ]
        );

        $response->assertRedirect(route('companies.users.index', $company->id));

        $this->assertDatabaseHas('users', [
            'name' => $userName,
            'email' => $userEmail,
        ]);
    }

    public function test_admin_can_delete_user_for_a_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->delete(
            route('companies.users.destroy', [$company->id, $user->id])
        );

        $response->assertRedirect(route('companies.users.index', $company->id));

        $this->assertSoftDeleted($user->fresh());
    }
}
