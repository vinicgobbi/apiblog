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
}
