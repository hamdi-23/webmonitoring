<?php

namespace Tests\Feature;

use App\Enums\MonitorStatus;
use App\Models\Monitor;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\MonitorStatusChanged;
use App\Services\MonitorChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.bot_token' => 'TEST-TOKEN', 'services.telegram.chat_id' => '12345']);
    }

    public function test_going_down_notifies_users_by_mail_and_telegram(): void
    {
        Notification::fake();
        Http::fake(['*' => Http::response('', 500)]);
        $user = User::factory()->create();
        $monitor = Monitor::factory()->up()->create();

        app(MonitorChecker::class)->check($monitor);

        Notification::assertSentTo($user, MonitorStatusChanged::class,
            fn ($n, $channels) => $n->status === MonitorStatus::Down && $channels === ['mail']);
        Notification::assertSentOnDemand(MonitorStatusChanged::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['telegram'] === '12345' && $channels === [TelegramChannel::class]);
    }

    public function test_recovery_notifies_with_downtime(): void
    {
        Notification::fake();
        Http::fake(['*' => Http::response('ok')]);
        $user = User::factory()->create();
        $monitor = Monitor::factory()->down()->create(['status_changed_at' => now()->subMinutes(42)]);

        app(MonitorChecker::class)->check($monitor);

        Notification::assertSentTo($user, MonitorStatusChanged::class, function ($n) {
            return $n->status === MonitorStatus::Up && str_contains($n->downtime(), '42');
        });
    }

    public function test_no_notification_without_status_change_or_for_first_up(): void
    {
        Notification::fake();
        Http::fake(['*' => Http::response('ok')]);
        User::factory()->create();

        app(MonitorChecker::class)->check(Monitor::factory()->create());      // pending -> up
        app(MonitorChecker::class)->check(Monitor::factory()->up()->create()); // up -> up

        Notification::assertNothingSent();
    }

    public function test_first_check_down_notifies(): void
    {
        Notification::fake();
        Http::fake(['*' => Http::response('', 404)]);
        $user = User::factory()->create();

        app(MonitorChecker::class)->check(Monitor::factory()->create());

        Notification::assertSentTo($user, MonitorStatusChanged::class);
    }

    public function test_channels_can_be_disabled(): void
    {
        Notification::fake();
        config(['monitoring.notify.mail' => false, 'services.telegram.chat_id' => null]);
        Http::fake(['*' => Http::response('', 500)]);
        User::factory()->create();

        app(MonitorChecker::class)->check(Monitor::factory()->up()->create());

        Notification::assertNothingSent();
    }

    public function test_telegram_channel_posts_to_bot_api(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $monitor = Monitor::factory()->create(['name' => 'Toko <Utama>']);

        (new AnonymousNotifiable)->route('telegram', '12345')
            ->notifyNow(new MonitorStatusChanged($monitor, MonitorStatus::Down, 'Status 500'));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.telegram.org/botTEST-TOKEN/sendMessage'
                && $request['chat_id'] === '12345'
                && $request['parse_mode'] === 'HTML'
                && str_contains($request['text'], 'DOWN')
                && str_contains($request['text'], 'Toko &lt;Utama&gt;')
                && str_contains($request['text'], 'Status 500');
        });
    }

    public function test_mail_content(): void
    {
        $monitor = Monitor::factory()->create(['name' => 'Toko']);
        $mail = (new MonitorStatusChanged($monitor, MonitorStatus::Down, 'Status 500'))->toMail(new User);

        $this->assertSame('🔴 DOWN: Toko', $mail->subject);
        $this->assertSame(route('monitors.show', $monitor), $mail->actionUrl);
    }
}
