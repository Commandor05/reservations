<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticatid_user_can_access_home_page()
    {
        $response = $this->get(route('home'));

        $response->assertOk();
    }

    public function test_authenticated_user_can_access_home_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
    }

    public function test_show_no_activities_when_theres_no_upcoming_activities()
    {
        $response = $this->get(route('home'));

        $response->assertSeeText('No activities');
    }

    public function test_pagination_isnt_shown_when_activities_are_9()
    {
        Activity::factory(9)->create();

        $response = $this->get(route('home'));

        $response->assertDontSee('Next');
    }

    public function test_pagination_shows_correct_results()
    {
        Activity::factory(9)->create();
        $activity = Activity::factory()->create([
            'start_time' => now()->addYear()
        ]);

        $response = $this->get(route('home'));

        $response->assertSee('Next');

        $response = $this->get(route('home') . '/?page=2');
        $response->assertSee($activity->name);
    }

    public function test_order_by_start_time_is_correct()
    {
        $activity = Activity::factory()->create([
            'start_time' => now()->addWeek()
        ]);

        $activityTwo = Activity::factory()->create([
            'start_time' => now()->addMonth()
        ]);

        $activityThree = Activity::factory()->create([
            'start_time' => now()->addYear()
        ]);

        $response = $this->get(route('home'));

        $response->assertSeeTextInOrder([
            $activity->name,
            $activityTwo->name,
            $activityThree->name,
        ]);
    }
}
