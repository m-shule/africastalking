<?php

namespace MShule\AfricasTalking\Test;

use Mockery;
use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use MShule\AfricasTalking\AfricasTalkingChannel;
use MShule\AfricasTalking\AfricasTalkingMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AfricasTalkingChannelTest extends TestCase
{
    use RefreshDatabase;

    /** @var AfricasTalking */
    private $client;

    /** @var AfricasTalkingChannel */
    private $channel;

    /** @var AfricasTalkingMessage */
    private $message;

    public function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();

        $this->client = Mockery::mock(new AfricasTalking('sandbox', 'cc5053b1da44cf59a4d7d58caed6965e79498f991f7bac9b6885db594b7baa02'));
        $this->channel = new AfricasTalkingChannel($this->client);
        $this->message = new AfricasTalkingMessage();
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(AfricasTalking::class, $this->client);
        $this->assertInstanceOf(AfricasTalkingChannel::class, $this->channel);
        $this->assertInstanceOf(AfricasTalkingMessage::class, $this->message);
    }

    /** @test */
    public function it_can_send_a_notification()
    {
        $notifiable = new TestNotifiable();
        $notification = new TestNotification();

        $this->channel->send($notifiable, $notification);
    }
}

class TestNotifiable
{
    use Notifiable;

    public function routeNotificationForAfricasTalking()
    {
        return '+254712345678';
    }
}

class TestNotification extends Notification
{
    public function toAfricasTalking($notifiable)
    {
        return (new AfricasTalkingMessage())->content('Hello World!');
    }
}
