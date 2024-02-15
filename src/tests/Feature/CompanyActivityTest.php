<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CompanyActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_owner_can_view_activities_page()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(
            route(
                'companies.activities.index',
                $company
            )
        );

        $response->assertOk();
    }

    public function test_company_owner_can_see_only_his_companies_activities()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $activity = Activity::factory()->create(['company_id' => $company->id]);
        $activityTwo = Activity::factory()->create();

        $response = $this->actingAs($user)->get(
            route(
                'companies.activities.index',
                $company
            )
        );

        $response->assertSeeText($activity->name)
            ->assertDontSeeText($activityTwo->name);
    }

    public function test_company_owner_can_create_activity()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create();

        $response = $this->actingAs($user)->post(
            route('companies.activities.store', $company),
            [
                'name' => 'activity',
                'description' => 'description',
                'start_time' => '2024-02-15 17:00',
                'price' => 555,
                'guide_id' => $guide->id,
            ]
        );

        $response->assertRedirect(route('companies.activities.index', $company));

        $this->assertDatabaseHas('activities', [
            'company_id' => $company->id,
            'guide_id' => $guide->id,
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2024-02-15 17:00',
            'price' => 55500,
        ]);
    }

    public function test_can_upload_image()
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create();

        $file = UploadedFile::fake()->image('logo.jpg');

        $this->actingAs($user)->post(
            route('companies.activities.store', $company),
            [
                'name' => 'activity',
                'description' => 'description',
                'start_time' => '2024-02-15 17:00',
                'price' => 555,
                'guide_id' => $guide->id,
                'image' => $file,
            ]
        );
        // dd();

        Storage::disk('public')->assertExists('activities/' . $file->hashName());
    }

    public function test_cannot_upload_non_image_file()
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create();

        $file = UploadedFile::fake()->create('document.pdf', 1000, 'applocation/pdf');

        $response = $this->actingAs($user)->post(
            route('companies.activities.store', $company),
            [
                'name' => 'activity',
                'description' => 'description',
                'start_time' => '2024-02-15 17:00',
                'price' => 555,
                'guide_id' => $guide->id,
                'image' => $file,
            ]
        );

        $response->assertSessionHasErrors(['image']);

        Storage::disk('public')->assertMissing('activities/' . $file->hashName());
    }

    public function test_guides_are_shown_only_for_specific_company_in_create_form()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company->id]);

        $companyTwo = Company::factory()->create();
        $guideTwo = User::factory()->guide()->create(['company_id' => $companyTwo->id]);

        $response = $this->actingAs($user)->get(route('companies.activities.create', $company));

        $response->assertViewHas('guides', function (Collection $guides) use ($guide) {
            return $guide->name === $guides[$guide->id];
        });

        $response->assertViewHas('guides', function (Collection $guides) use ($guideTwo) {
            return !array_key_exists($guideTwo->id, $guides->toArray());
        });
    }

    public function test_guides_are_shown_only_for_specific_company_in_edit_form()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company->id]);
        $activity = Activity::factory()->create(['company_id' => $company->id]);

        $companyTwo = Company::factory()->create();
        $guideTwo = User::factory()->guide()->create(['company_id' => $companyTwo->id]);

        $response = $this->actingAs($user)->get(route('companies.activities.edit', [$company, $activity]));

        $response->assertViewHas('guides', function (Collection $guides) use ($guide) {
            return $guide->name === $guides[$guide->id];
        });

        $response->assertViewHas('guides', function (Collection $guides) use ($guideTwo) {
            return !array_key_exists($guideTwo->id, $guides->toArray());
        });
    }

    public function test_company_owner_can_edit_activity()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company->id]);
        $activity = Activity::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put(
            route(
                'companies.activities.update',
                [$company, $activity]
            ),
            [
                'name' => 'activity',
                'description' => 'description',
                'start_time' => '2023-02-15 10:00',
                'price' => 777,
                'guide_id' => $guide->id,
            ]
        );

        $response->assertRedirect(route('companies.activities.index', $company));

        $this->assertDatabaseHas('activities', [
            'company_id' => $company->id,
            'guide_id' => $guide->id,
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-02-15 10:00',
            'price' => 77700,
        ]);
    }

    public function test_company_owner_cannot_edit_activity_for_other_company()
    {
        $company = Company::factory()->create();
        $companyTwo = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create();
        $activity = Activity::factory()->create(['company_id' => $companyTwo->id]);

        $response = $this->actingAs($user)->put(
            route(
                'companies.activities.update',
                [$companyTwo, $activity]
            ),
            [
                'name' => 'activity',
                'description' => 'description',
                'start_time' => '2023-02-15 10:00',
                'price' => 777,
                'guide_id' => $guide->id,
            ]
        );

        $response->assertForbidden();
    }

    public function test_company_owner_can_delete_activity()
    {
        $company = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $activity = Activity::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->delete(
            route('companies.activities.destroy', [$company, $activity])
        );

        $response->assertRedirect(route('companies.activities.index', $company->id));

        $this->assertModelMissing($activity);
    }

    public function test_company_owner_cannot_delete_activity_for_other_company()
    {
        $company = Company::factory()->create();
        $companyTwo = Company::factory()->create();
        $user = User::factory()->companyOwner()->create(['company_id' => $company->id]);
        $activity = Activity::factory()->create(['company_id' => $companyTwo->id]);

        $response = $this->actingAs($user)->delete(
            route('companies.activities.destroy', [$companyTwo, $activity])
        );

        $response->assertForbidden();
    }

    public function test_admin_can_view_companies_activities()
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get(
            route(
                'companies.activities.index',
                $company
            )
        );

        $response->assertOk();
    }

    public function test_admin_can_create_activity_for_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create();

        $response = $this->actingAs($user)->post(
            route('companies.activities.store', $company),
            [
                'name' => 'activity',
                'description' => 'description',
                'start_time' => '2024-02-15 17:00',
                'price' => 555,
                'guide_id' => $guide->id,
            ]
        );

        $response->assertRedirect(route('companies.activities.index', $company));

        $this->assertDatabaseHas('activities', [
            'company_id' => $company->id,
            'guide_id' => $guide->id,
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2024-02-15 17:00',
            'price' => 55500,
        ]);
    }

    public function test_admin_can_edit_activity_for_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->create(['company_id' => $company->id]);
        $guide = User::factory()->guide()->create(['company_id' => $company->id]);
        $activity = Activity::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put(
            route(
                'companies.activities.update',
                [$company, $activity]
            ),
            [
                'name' => 'activity',
                'description' => 'description',
                'start_time' => '2023-02-15 10:00',
                'price' => 777,
                'guide_id' => $guide->id,
            ]
        );

        $response->assertRedirect(route('companies.activities.index', $company));

        $this->assertDatabaseHas('activities', [
            'company_id' => $company->id,
            'guide_id' => $guide->id,
            'name' => 'activity',
            'description' => 'description',
            'start_time' => '2023-02-15 10:00',
            'price' => 77700,
        ]);
    }
}