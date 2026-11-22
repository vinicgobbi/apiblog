<?php

namespace Tests\Feature\Models;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_scope_excludes_unpublished_pages(): void
    {
        $published = Page::factory()->published()->create();
        Page::factory()->create();

        $this->assertEquals([$published->id], Page::published()->pluck('id')->all());
    }

    public function test_new_page_goes_to_the_end_of_the_menu(): void
    {
        $first = Page::factory()->create();
        Page::factory()->create(['position' => 7]);

        $last = Page::factory()->create();

        $this->assertSame(1, $first->position);
        $this->assertSame(8, $last->position);
    }

    public function test_position_is_not_mass_assignable(): void
    {
        $page = Page::factory()->create();

        $page->update(['position' => 99]);

        $this->assertSame(1, $page->fresh()->position);
    }

    public function test_ordered_scope_sorts_by_position_then_id(): void
    {
        $third = Page::factory()->create(['position' => 2]);
        $first = Page::factory()->create(['position' => 1]);
        $second = Page::factory()->create(['position' => 1]);

        $this->assertEquals([$first->id, $second->id, $third->id], Page::ordered()->pluck('id')->all());
    }
}
