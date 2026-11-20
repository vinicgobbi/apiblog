<?php

namespace Tests\Feature\Models;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_scope_excludes_pending_rejected_and_spam_comments(): void
    {
        $approved = Comment::factory()->approved()->create();
        Comment::factory()->pending()->create();
        Comment::factory()->rejected()->create();
        Comment::factory()->spam()->create();

        $this->assertEquals([$approved->id], Comment::approved()->pluck('id')->all());
    }

    public function test_new_comment_is_pending_and_ignores_mass_assigned_status(): void
    {
        $post = Post::factory()->published()->create();

        $comment = $post->comments()->create([
            'author_name' => 'Leitor',
            'author_email' => 'leitor@example.com',
            'body' => 'Ótimo post!',
            'status' => CommentStatus::Approved,
        ]);

        $this->assertSame(CommentStatus::Pending, $comment->fresh()->status);
    }

    public function test_serialized_comment_hides_author_email_and_request_metadata(): void
    {
        $comment = Comment::factory()->create();

        $this->assertArrayNotHasKey('author_email', $comment->toArray());
        $this->assertArrayNotHasKey('ip_address', $comment->toArray());
        $this->assertArrayNotHasKey('user_agent', $comment->toArray());
    }
}
