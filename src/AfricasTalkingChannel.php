<?php

namespace MShule\AfricasTalking;

use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Notifications\Notification;
use MShule\AfricasTalking\Exceptions\CouldNotSendNotification;

class AfricasTalkingChannel
{
    protected $client;

    public function __construct(AfricasTalking $client)
    {
        $this->client = $client;
    }

    /**
     * Send the given notification via Africa is Talking.
     *
     * @param mixed                                  $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     */
    public function send($notifiable, Notification $notification)
    {
        $sendable = [];

        if (! ($message = $notification->toAfricasTalking($notifiable)) instanceof AfricasTalkingMessage) {
            throw CouldNotSendNotification::invalidMessageObject($message);
        }

        if (! ($to = $this->getTo($notifiable, $notification))) {
            throw CouldNotSendNotification::missingTo();
        }

        if (is_array($to)) {
            $to = implode(',', $to);
        }

        $sendable['to'] = $to;

        if (! $content = $message->getContent()) {
            throw CouldNotSendNotification::missingContent();
        }

        $sendable['message'] = $content;

        if ($from = $this->getFrom($notifiable, $notification)) {
            $sendable['from'] = $from;
        }

        $response = $this->client->sms()->send($sendable);

        if (! isset($response['data']->SMSMessageData->Recipients) || ! isset($response['data']->SMSMessageData->Message)) {
            throw CouldNotSendNotification::gatewayException(503);
        }

        $recipients = $response['data']->SMSMessageData->Recipients;
        $summary = $response['data']->SMSMessageData->Message;

        if (! count($recipients) && isset($summary)) {
            throw CouldNotSendNotification::gatewayException(402, $summary);
        }

        if (1 == count($recipients)) {
            // codes 100: Processed, 101: Sent 102: Queued are success status codes
            if (! in_array($recipients[0]->statusCode, [100, 101, 102])) {
                throw CouldNotSendNotification::gatewayException($recipients[0]->statusCode);
            }
        }

        return $recipients;
    }

    /**
     * Get the number to send a notification to.
     *
     * @param mixed                                  $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @return mixed
     */
    protected function getTo($notifiable, Notification $notification)
    {
        if ($to = $notification->toAfricasTalking($notifiable)->getTo()) {
            return $to;
        }
        if ($to = $notifiable->routeNotificationFor('africastalking', $notification)) {
            if (is_array($to)) {
                $to = implode(',', $to);
            }

            return $to;
        }
        if (isset($notifiable->phone)) {
            return $notifiable->phone;
        }
        if (isset($notifiable->phone_number)) {
            return $notifiable->phone_number;
        }
        if (isset($notifiable->mobile)) {
            return $notifiable->mobile;
        }
        if (isset($notifiable->mobile_number)) {
            return $notifiable->mobile_number;
        }

        return;
    }

    /**
     * Get the number to send a notification from.
     *
     * @param mixed                                  $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @return mixed
     */
    protected function getFrom($notifiable, Notification $notification)
    {
        if ($from = $notification->toAfricasTalking($notifiable)->getFrom()) {
            return $from;
        }

        return config('services.africastalking.from');
    }
}
