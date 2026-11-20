<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_every_moderation_endpoint_returns_401_without_token(): void
    {
        $comment = Comment::factory()->create();

        $this->getJson('/api/admin/comments')->assertUnauthorized();
        $this->getJson('/api/admin/comments/counts')->assertUnauthorized();
        $this->getJson("/api/admin/comments/{$comment->id}")->assertUnauthorized();
        $this->patchJson("/api/admin/comments/{$comment->id}", ['status' => 'approved'])->assertUnauthorized();
        $this->deleteJson("/api/admin/comments/{$comment->id}")->assertUnauthorized();
        $this->assertSame(CommentStatus::Pending, $comment->fresh()->status);
    }

    public function test_index_lists_comments_newest_first_with_private_data_and_post(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Meu post', 'slug' => 'meu-post']);
        $older = Comment::factory()->approved()->for($post)->create(['created_at' => now()->subDay()]);
        $newer = Comment::factory()->pending()->for($post)->create([
            'author_email' => 'leitora@example.com',
            'ip_address' => '203.0.113.7',
        ]);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/comments')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$newer->id, $older->id])
            ->assertJsonPath('data.0.author_email', 'leitora@example.com')
            ->assertJsonPath('data.0.ip_address', '203.0.113.7')
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.0.post', ['id' => $post->id, 'title' => 'Meu post', 'slug' => 'meu-post']);
    }

    public function test_index_filters_by_status(): void
    {
        $pending = Comment::factory()->pending()->create();
        Comment::factory()->approved()->create();
        Comment::factory()->spam()->create();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/comments?status=pending')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$pending->id]);
    }

    public function test_index_filters_by_post(): void
    {
        $post = Post::factory()->published()->create();
        $onPost = Comment::factory()->for($post)->create();
        Comment::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/comments?post_id={$post->id}")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$onPost->id]);
    }

    public function test_index_with_invalid_status_returns_422(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/comments?status=arquivado')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'O status deve ser "pending", "approved", "rejected" ou "spam"']);
    }

    public function test_comment_on_trashed_post_still_shows_its_post(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Removido']);
        Comment::factory()->for($post)->create();
        $post->delete();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/comments')
            ->assertOk()
            ->assertJsonPath('data.0.post.title', 'Removido');
    }

    public function test_counts_returns_total_per_status_including_zeros(): void
    {
        Comment::factory()->count(3)->pending()->create();
        Comment::factory()->approved()->create();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/comments/counts')
            ->assertOk()
            ->assertExactJson(['data' => ['pending' => 3, 'approved' => 1, 'rejected' => 0, 'spam' => 0]]);
    }

    public function test_approving_comment_makes_it_visible_on_the_blog(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'meu-post']);
        $comment = Comment::factory()->pending()->for($post)->create();
        $this->getJson('/api/posts/meu-post/comments')->assertJsonCount(0, 'data');

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/comments/{$comment->id}", ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->getJson('/api/posts/meu-post/comments')->assertJsonPath('data.*.id', [$comment->id]);
    }

    public function test_marking_approved_comment_as_spam_hides_it_from_the_blog(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'meu-post']);
        $comment = Comment::factory()->approved()->for($post)->create();

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/comments/{$comment->id}", ['status' => 'spam'])
            ->assertOk();

        $this->assertSame(CommentStatus::Spam, $comment->fresh()->status);
        $this->getJson('/api/posts/meu-post/comments')->assertJsonCount(0, 'data');
    }

    public function test_update_never_changes_the_comment_text(): void
    {
        $comment = Comment::factory()->pending()->create(['body' => 'Texto original', 'author_name' => 'Leitora']);

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/comments/{$comment->id}", ['status' => 'approved', 'body' => 'Alterado', 'author_name' => 'Outro'])
            ->assertOk();

        $this->assertSame('Texto original', $comment->fresh()->body);
        $this->assertSame('Leitora', $comment->fresh()->author_name);
    }

    public function test_update_with_invalid_status_returns_422(): void
    {
        $comment = Comment::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/comments/{$comment->id}", ['status' => 'publicado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'O status deve ser "pending", "approved", "rejected" ou "spam"']);
    }

    public function test_destroy_deletes_comment(): void
    {
        $comment = Comment::factory()->create();

        $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/comments/{$comment->id}")->assertNoContent();

        $this->assertModelMissing($comment);
    }

    public function test_admin_cannot_create_comments_through_moderation_endpoint(): void
    {
        $this->actingAs($this->admin, 'api')->postJson('/api/admin/comments', [])->assertMethodNotAllowed();
    }
}
