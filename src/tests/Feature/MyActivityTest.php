<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MyActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_activities_dones_not_show_other_users_activities()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $user->activities()->attach($activity);

        $userTwo = User::factory()->create();
        $activityTwo = Activity::factory()->create();
        $userTwo->activities()->attach($activityTwo);

        $response = $this->actingAs($user)->get(route('my-activity.show'));

        $response->assertSeeText($activity->name);
        $response->assertDontSeeText($activityTwo->name);
    }

    public function test_my_activities_shows_order_by_time_correctly()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create(['start_time' => now()->addWeek()]);
        $activityTwo = Activity::factory()->create(['start_time' => now()->addMonth()]);
        $activityThree = Activity::factory()->create(['start_time' => now()->addMonths(2)]);

        $user->activities()->attach($activity);
        $user->activities()->attach($activityTwo);
        $user->activities()->attach($activityThree);

        $response = $this->actingAs($user)->get(route('my-activity.show'));

        $response->assertSeeTextInOrder([
            $activity->name,
            $activityTwo->name,
            $activityThree->name,
        ]);
    }

    public function test_can_cancel_activity()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $user->activities()->attach($activity);

        $response = $this->actingAs($user)->delete(route('my-activity.destroy', $activity));

        $response->assertRedirect(route('my-activity.show'));

        $this->assertCount(0, $user->activities()->get());
    }

    public function test_cannot_cancel_activity_for_other_user()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $user->activities()->attach($activity);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->delete(route('my-activity.destroy', $activity));

        $response->assertForbidden();

        $this->assertCount(1, $user->activities()->get());

    }
}
