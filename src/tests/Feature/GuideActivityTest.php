<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GuideActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_can_access_my_activities_page()
    {
        $user = User::factory()->guide()->create();

        $response = $this->actingAs($user)->get(route('guide-activity.show'));

        $response->assertOk();
    }

    public function test_ordinary_user_cannot_access_guide_activities_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('guide-activity.show'));

        $response->assertForbidden();
    }

    public function test_guides_sees_activities_only_assigned_to_him()
    {
        $user = User::factory()->guide()->create();
        $activity = Activity::factory()->create(['guide_id' => $user->id]);
        $activityOther = Activity::factory()->create();

        $response = $this->actingAs($user)->get(route('guide-activity.show'));

        $response->assertSeeText($activity->name);
        $response->assertDontSeeText($activityOther);
    }

    public function test_guide_sees_activities_ordered_by_time_correctly()
    {
        $user = User::factory()->guide()->create();
        $activity = Activity::factory()->create(['guide_id' => $user->id, 'start_time' => now()->addWeek()]);
        $activityTwo = Activity::factory()->create(['guide_id' => $user->id, 'start_time' => now()->addMonth()]);
        $activityThree = Activity::factory()->create(['guide_id' => $user->id, 'start_time' => now()->addMonths(2)]);

        $response = $this->actingAs($user)->get(route('guide-activity.show'));

        $response->assertSeeTextInOrder([
            $activity->name,
            $activityTwo->name,
            $activityThree->name,
        ]);
    }


}
