<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Prevent actual WebSocket broadcasts from firing during tests
        Event::fake();
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/posts', [
            'body' => 'Hello world!',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['body' => 'Hello world!'])
            ->assertJsonStructure(['id', 'body', 'likes_count', 'replies_count', 'reposts_count', 'liked', 'reposted', 'created_at', 'user']);

        $this->assertDatabaseHas('posts', ['body' => 'Hello world!', 'user_id' => $user->id]);
    }

    public function test_create_post_requires_body(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/posts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    public function test_create_post_body_cannot_exceed_500_chars(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/posts', [
            'body' => str_repeat('a', 501),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    public function test_create_post_requires_authentication(): void
    {
        $this->postJson('/api/posts', ['body' => 'Hello'])->assertStatus(401);
    }

    public function test_user_can_reply_to_a_post(): void
    {
        $user   = User::factory()->create();
        $parent = Post::factory()->for($user)->create();

        $response = $this->actingAs($user)->postJson('/api/posts', [
            'body'      => 'This is a reply.',
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['body' => 'This is a reply.']);

        $this->assertDatabaseHas('posts', ['parent_id' => $parent->id]);
        $this->assertEquals(1, $parent->fresh()->replies_count);
    }

    public function test_reply_to_nonexistent_post_fails(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/posts', [
            'body'      => 'Reply',
            'parent_id' => 9999,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    // ── Show ─────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonStructure(['post' => ['id', 'body', 'user'], 'replies']);
    }

    public function test_show_post_requires_authentication(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->getJson("/api/posts/{$post->id}")->assertStatus(401);
    }

    public function test_show_returns_404_for_missing_post(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/posts/9999')->assertStatus(404);
    }

    public function test_show_includes_replies(): void
    {
        $user   = User::factory()->create();
        $parent = Post::factory()->for($user)->create();
        $reply  = Post::factory()->for($user)->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($user)->getJson("/api/posts/{$parent->id}");

        $response->assertOk();
        $replies = $response->json('replies');
        $this->assertCount(1, $replies);
        $this->assertEquals($reply->id, $replies[0]['id']);
    }

    // ── Destroy ──────────────────────────────────────────────────────────────

    public function test_owner_can_delete_their_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)->deleteJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJson(['message' => 'Post removido.']);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_non_owner_cannot_delete_post(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post  = Post::factory()->for($owner)->create();

        $this->actingAs($other)->deleteJson("/api/posts/{$post->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_delete_post_requires_authentication(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->deleteJson("/api/posts/{$post->id}")->assertStatus(401);
    }

    public function test_delete_reply_decrements_parent_replies_count(): void
    {
        $user   = User::factory()->create();
        $parent = Post::factory()->for($user)->create(['replies_count' => 1]);
        $reply  = Post::factory()->for($user)->create(['parent_id' => $parent->id]);

        $this->actingAs($user)->deleteJson("/api/posts/{$reply->id}")->assertOk();

        $this->assertEquals(0, $parent->fresh()->replies_count);
    }
}
