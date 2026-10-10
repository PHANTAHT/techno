<?php

namespace Tests\Feature;

use App\Http\Controllers\GoogleController;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\MoodleConfig;
use App\Models\Task;
use App\Models\User;
use App\Notifications\DeadlineReminder;
use App\Services\MoodleHttp;
use Google\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openrouter.api_key' => 'test-key', 'services.openrouter.model' => 'test-model']);
        Http::preventStrayRequests();
    }

    public function test_removed_reset_endpoint_cannot_delete_any_tasks(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Task::create(['user_id' => $other->id, 'title' => 'Keep me']);
        $this->actingAs($owner)->get('/reset-tasks')->assertNotFound();
        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_notification_preferences_are_persisted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->patch('/settings/notifications', ['notif_deadline_1day' => '1'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $settings = json_decode($user->fresh()->settings, true);
        $this->assertTrue($settings['notifications']['notif_deadline_1day']);
        $this->assertFalse($settings['notifications']['notif_deadline_3days']);
    }

    public function test_external_attachment_survives_storage_and_accessor(): void
    {
        $user = User::factory()->create();
        $task = Task::create(['user_id' => $user->id, 'title' => 'Attachment',
            'url' => 'https://example.com/task', 'file_url' => 'https://example.com/file.pdf']);
        $this->assertSame('https://example.com/file.pdf', $task->fresh()->file_url);
        $this->assertTrue($task->fresh()->hasFile());
    }

    public function test_task_edit_can_save_and_other_users_cannot_modify_it(): void
    {
        $owner = User::factory()->create();
        $task = Task::create(['user_id' => $owner->id, 'title' => 'Original']);
        $this->actingAs($owner)->get('/tasks/'.$task->id.'/edit')->assertOk()->assertSee('Original');
        $this->patch('/tasks/'.$task->id, ['title' => 'Updated', 'status' => 'done'])->assertRedirect();
        $this->assertSame('Updated', $task->fresh()->title);
        $this->assertSame('done', $task->fresh()->status);
        $this->actingAs(User::factory()->create())->patch('/tasks/'.$task->id, ['title' => 'Forbidden'])->assertForbidden();
        $this->assertSame('Updated', $task->fresh()->title);
    }

    public function test_failed_moodle_sync_does_not_delete_existing_tasks(): void
    {
        $user = User::factory()->create();
        MoodleConfig::create(['user_id' => $user->id, 'moodle_url' => 'https://moodle.example.com', 'token' => 'test']);
        Task::create(['user_id' => $user->id, 'title' => 'Keep Moodle task', 'source' => 'moodle', 'source_id' => '1']);
        $this->mock(MoodleHttp::class)->shouldReceive('client')->andReturn(Http::timeout(1));
        Http::fake(['*' => Http::response('Bad Gateway', 502)]);
        $this->actingAs($user)->get('/moodle/sync')->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_malformed_successful_moodle_response_keeps_tasks(): void
    {
        $user = User::factory()->create();
        MoodleConfig::create(['user_id' => $user->id, 'moodle_url' => 'https://moodle.example.com', 'token' => 'test']);
        Task::create(['user_id' => $user->id, 'title' => 'Keep task', 'source' => 'moodle', 'source_id' => '1']);
        $this->mock(MoodleHttp::class)->shouldReceive('client')->andReturn(Http::timeout(1));
        Http::fake(['*' => Http::response(['unexpected' => true])]);
        $this->actingAs($user)->get('/moodle/sync')->assertSessionHas('error');
        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_ai_chat_saves_answer_without_accessing_another_users_session(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $session = ChatSession::create(['user_id' => $other->id, 'title' => 'Private']);
        ChatMessage::create(['session_id' => $session->id, 'role' => 'user', 'content' => 'private-secret']);
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'AI works']]]])]);
        $response = $this->actingAs($user)->postJson('/ai/chat', ['message' => 'Hello', 'session_id' => $session->id]);
        $response->assertOk()->assertJsonPath('reply', 'AI works');
        $this->assertNotEquals($session->id, $response->json('session_id'));
        Http::assertSent(fn ($request) => ! str_contains(json_encode($request->data()), 'private-secret'));
        $this->assertDatabaseHas('chat_messages', ['session_id' => $response->json('session_id'), 'content' => 'AI works']);
    }

    public function test_ai_provider_errors_are_readable_and_do_not_save_fake_answers(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'sensitive provider detail']], 402)]);
        $this->actingAs(User::factory()->create())->postJson('/ai/chat', ['message' => 'Hello'])
            ->assertStatus(503)->assertJsonPath('error', 'Saldo OpenRouter tidak cukup.');
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_ai_connection_failures_return_readable_errors(): void
    {
        Http::fake(fn () => throw new ConnectionException('Internal network details'));
        $this->actingAs(User::factory()->create())->postJson('/ai/chat', ['message' => 'Hello'])
            ->assertStatus(503)->assertJsonPath('error', 'Koneksi AI gagal atau timeout. Periksa koneksi server.');
    }

    public function test_remote_file_summary_does_not_send_moodle_token_to_ai(): void
    {
        $user = User::factory()->create();
        $task = Task::create(['user_id' => $user->id, 'title' => 'Remote file', 'source' => 'moodle',
            'file_url' => 'https://moodle.example.com/document.txt?token=private-moodle-token']);
        $this->mock(MoodleHttp::class)->shouldReceive('download')->once()->andReturn(
            new \Illuminate\Http\Client\Response(new Response(200, [], 'Remote document contents'))
        );
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Remote summary']]]])]);
        $this->actingAs($user)->postJson('/ai/summarize-file/'.$task->id)->assertOk()->assertJsonPath('summary', 'Remote summary');
        Http::assertSent(fn ($request) => str_contains(json_encode($request->data()), 'Remote document contents')
            && ! str_contains(json_encode($request->data()), 'private-moodle-token'));
    }

    public function test_ai_rejects_empty_messages_and_arbitrary_attachment_urls(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/ai/chat', [])->assertUnprocessable();
        $this->postJson('/ai/chat', ['message' => 'Read file', 'context' => 'file_url: https://attacker.example/file.pdf'])
            ->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_ai_email_and_task_explanation_return_provider_answers(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Useful answer']]]])]);
        $this->actingAs(User::factory()->create())->postJson('/ai/summarize-email', ['content' => 'Meeting tomorrow'])
            ->assertOk()->assertJsonPath('summary', 'Useful answer');
        $this->postJson('/ai/explain-task', ['title' => 'Study math'])
            ->assertOk()->assertJsonPath('explanation', 'Useful answer');
    }

    public function test_ai_summarizes_local_file_and_enforces_ownership(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('task-files/example.txt', 'Important document contents');
        $owner = User::factory()->create();
        $task = Task::create(['user_id' => $owner->id, 'title' => 'Read', 'file_path' => 'task-files/example.txt', 'file_type' => 'txt']);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Document summary']]]])]);
        $this->actingAs($owner)->postJson('/ai/summarize-file/'.$task->id)->assertOk()->assertJsonPath('summary', 'Document summary');
        Http::assertSent(fn ($r) => str_contains(json_encode($r->data()), 'Important document contents'));
        $this->actingAs(User::factory()->create())->postJson('/ai/summarize-file/'.$task->id)->assertForbidden();
    }

    public function test_google_callback_requires_the_login_state(): void
    {
        $this->get('/google/callback?code=untrusted&state=wrong')->assertForbidden();
    }

    public function test_google_login_starts_on_the_callback_origin(): void
    {
        config(['services.google.redirect' => 'http://localhost:8000/google/callback']);
        $this->get('http://127.0.0.1:8000/google/redirect')->assertRedirect('http://localhost:8000/google/redirect');
        $this->assertEmpty(session('google_oauth_states', []));
    }

    public function test_google_login_tabs_keep_separate_single_use_states(): void
    {
        config(['app.url' => 'http://localhost', 'services.google.redirect' => 'http://localhost/google/callback', 'services.google.client_id' => 'demo-client']);
        $first = $this->get('http://localhost/google/redirect');
        $this->assertStringContainsString('accounts.google.com', $first->headers->get('Location'));
        parse_str(parse_url($first->headers->get('Location'), PHP_URL_QUERY), $firstQuery);
        $second = $this->get('http://localhost/google/redirect');
        parse_str(parse_url($second->headers->get('Location'), PHP_URL_QUERY), $secondQuery);
        $this->assertNotSame($firstQuery['state'], $secondQuery['state']);
        $this->get('/google/callback?state=invalid')->assertForbidden();
        $url = '/google/callback?error=access_denied&state='.$firstQuery['state'];
        $this->get($url)->assertRedirect('/login');
        $this->get($url)->assertForbidden();
        $this->get('/google/callback?error=access_denied&state='.$secondQuery['state'])->assertRedirect('/login');
    }

    public function test_deadline_reminders_honor_preferences_and_do_not_repeat(): void
    {
        Notification::fake();
        $this->travelTo(now()->startOfHour());
        $user = User::factory()->create(['settings' => json_encode(['notifications' => ['notif_deadline_1day' => false, 'notif_deadline_3days' => true]])]);
        Task::create(['user_id' => $user->id, 'title' => 'Disabled reminder', 'deadline' => now()->addHours(12)]);
        Task::create(['user_id' => $user->id, 'title' => 'Enabled reminder', 'deadline' => now()->addHours(60)]);
        $this->artisan('reminders:send')->assertSuccessful();
        $this->artisan('reminders:send')->assertSuccessful();
        Notification::assertSentToTimes($user, DeadlineReminder::class, 1);
    }

    public function test_authenticated_main_pages_render_without_google_connection(): void
    {
        $user = User::factory()->create();
        $task = Task::create(['user_id' => $user->id, 'title' => 'Unsafe ` ${content} </script>', 'description' => '<img src=x onerror=alert(1)>', 'deadline' => now()->addDay()]);
        foreach (['/dashboard', '/calendar', '/tasks', '/tasks/create', '/tasks/'.$task->id, '/tasks/'.$task->id.'/edit', '/settings', '/emails'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_google_sync_preserves_completed_calendar_tasks(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $task = Task::create(['user_id' => $user->id, 'title' => 'Event', 'source' => 'calendar', 'source_id' => 'event-1', 'status' => 'done']);
        $handler = new MockHandler([
            new Response(200, [], json_encode(['courses' => []])),
            new Response(200, [], json_encode(['items' => [
                ['id' => 'event-1', 'summary' => 'Updated event', 'start' => ['dateTime' => now()->addDay()->toRfc3339String()]],
            ]])),
        ]);
        $client = new Client;
        $client->setAccessToken(['access_token' => 'test', 'created' => time(), 'expires_in' => 3600]);
        $client->setHttpClient(new \GuzzleHttp\Client(['handler' => HandlerStack::create($handler)]));
        $controller = new GoogleController;
        (new \ReflectionMethod($controller, 'runAutoSync'))->invoke($controller, $client);
        $this->assertSame('done', $task->fresh()->status);
        $this->assertSame('Updated event', $task->fresh()->title);
    }

    public function test_all_controller_routes_reference_existing_methods(): void
    {
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (! str_starts_with($action, 'App\\Http\\Controllers\\')) {
                continue;
            }
            [$class, $method] = str_contains($action, '@') ? explode('@', $action) : [$action, '__invoke'];
            $this->assertTrue(method_exists($class, $method), $action.' is missing');
        }
    }

    public function test_moodle_http_blocks_private_addresses_and_insecure_urls(): void
    {
        foreach (['http://example.com/file', 'https://127.0.0.1/file', 'https://10.0.0.1/file', 'https://example.com:8080/file'] as $url) {
            try {
                (new MoodleHttp)->client($url);
                $this->fail('Unsafe URL accepted');
            } catch (\RuntimeException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
        Http::assertNothingSent();
    }
}
