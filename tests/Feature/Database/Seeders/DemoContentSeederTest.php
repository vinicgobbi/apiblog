<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_posts_for_the_existing_admin(): void
    {
        $admin = User::factory()->create();

        $this->seed(DemoContentSeeder::class);

        $this->assertSame(15, Post::published()->count());
        $this->assertSame(18, Post::whereBelongsTo($admin, 'author')->count());
        $this->assertSame(0, Post::whereNull('slug')->count());
        $this->assertTrue(Page::published()->where('slug', 'sobre')->exists());
    }

    public function test_does_not_run_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->seed(DemoContentSeeder::class);

        $this->assertDatabaseCount('posts', 0);
    }
}
