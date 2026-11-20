<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    private const array VALID_COMMENT = [
        'author_name' => 'Leitora',
        'author_email' => 'leitora@example.com',
        'body' => 'Ótimo post, obrigada!',
    ];

    public function test_index_lists_only_approved_comments_in_chronological_order(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'meu-post']);
        $second = Comment::factory()->approved()->for($post)->create(['created_at' => now()->subHour()]);
        $first = Comment::factory()->approved()->for($post)->create(['created_at' => now()->subDay()]);
        Comment::factory()->pending()->for($post)->create();
        Comment::factory()->rejected()->for($post)->create();
        Comment::factory()->spam()->for($post)->create();
        Comment::factory()->approved()->create();

        $this->getJson('/api/posts/meu-post/comments')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$first->id, $second->id])
            ->assertJsonPath('meta.total', 2);
    }

    public function test_index_exposes_only_public_fields(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'meu-post']);
        $comment = Comment::factory()->approved()->for($post)->create([
            'author_name' => 'Leitora',
            'author_email' => 'segredo@example.com',
            'body' => 'Comentário',
            'ip_address' => '203.0.113.7',
        ]);

        $response = $this->getJson('/api/posts/meu-post/comments')->assertOk();

        $response->assertJsonPath('data.0', [
            'id' => $comment->id,
            'author_name' => 'Leitora',
            'body' => 'Comentário',
            'created_at' => $comment->created_at->toIso8601String(),
        ]);
        $this->assertStringNotContainsString('segredo@example.com', $response->getContent());
        $this->assertStringNotContainsString('203.0.113.7', $response->getContent());
    }

    public function test_index_returns_404_for_unpublished_post(): void
    {
        Post::factory()->draft()->create(['slug' => 'rascunho']);

        $this->getJson('/api/posts/rascunho/comments')->assertNotFound();
    }

    public function test_store_saves_pending_comment_with_request_metadata_and_returns_202(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'meu-post']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->withHeader('User-Agent', 'NavegadorDeTeste/1.0')
            ->postJson('/api/posts/meu-post/comments', self::VALID_COMMENT)
            ->assertStatus(202)
            ->assertJsonPath('message', 'Comentário enviado! Ele será publicado após a moderação.');

        $comment = $post->comments()->sole();
        $this->assertSame(CommentStatus::Pending, $comment->status);
        $this->assertSame('Leitora', $comment->author_name);
        $this->assertSame('leitora@example.com', $comment->author_email);
        $this->assertSame('203.0.113.7', $comment->ip_address);
        $this->assertSame('NavegadorDeTeste/1.0', $comment->user_agent);
    }

    public function test_new_comment_is_not_listed_until_approved(): void
    {
        Post::factory()->published()->create(['slug' => 'meu-post']);

        $this->postJson('/api/posts/meu-post/comments', self::VALID_COMMENT)->assertStatus(202);

        $this->getJson('/api/posts/meu-post/comments')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_store_ignores_status_sent_by_the_client(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'meu-post']);

        $this->postJson('/api/posts/meu-post/comments', [...self::VALID_COMMENT, 'status' => 'approved'])->assertStatus(202);

        $this->assertSame(CommentStatus::Pending, $post->comments()->sole()->status);
    }

    public function test_store_with_honeypot_filled_responds_as_success_but_discards_comment(): void
    {
        Post::factory()->published()->create(['slug' => 'meu-post']);

        $this->postJson('/api/posts/meu-post/comments', [...self::VALID_COMMENT, 'website' => 'https://spam.example'])
            ->assertStatus(202)
            ->assertJsonPath('message', 'Comentário enviado! Ele será publicado após a moderação.');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_store_with_missing_or_invalid_fields_returns_422(): void
    {
        Post::factory()->published()->create(['slug' => 'meu-post']);

        $this->postJson('/api/posts/meu-post/comments', ['author_email' => 'invalido', 'body' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'author_name' => 'O nome é obrigatório',
                'author_email' => 'Informe um e-mail válido',
                'body' => 'O comentário deve ter no mínimo 2 caracteres',
            ]);
    }

    public function test_store_returns_404_for_draft_or_scheduled_post(): void
    {
        Post::factory()->draft()->create(['slug' => 'rascunho']);
        Post::factory()->scheduled()->create(['slug' => 'agendado']);

        $this->postJson('/api/posts/rascunho/comments', self::VALID_COMMENT)->assertNotFound();
        $this->postJson('/api/posts/agendado/comments', self::VALID_COMMENT)->assertNotFound();
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_fourth_comment_within_a_minute_returns_429(): void
    {
        Post::factory()->published()->create(['slug' => 'meu-post']);

        foreach (range(1, 3) as $attempt) {
            $this->postJson('/api/posts/meu-post/comments', self::VALID_COMMENT)->assertStatus(202);
        }

        $this->postJson('/api/posts/meu-post/comments', self::VALID_COMMENT)->assertTooManyRequests();
        $this->assertDatabaseCount('comments', 3);
    }

    public function test_long_user_agent_is_truncated_to_column_size(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'meu-post']);

        $this->withHeader('User-Agent', str_repeat('a', 1000))
            ->postJson('/api/posts/meu-post/comments', self::VALID_COMMENT)
            ->assertStatus(202);

        $this->assertSame(512, strlen($post->comments()->sole()->user_agent));
    }
}
