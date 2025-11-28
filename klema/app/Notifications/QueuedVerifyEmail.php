<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued version of Laravel's email verification notification.
 * This prevents email sending from blocking HTTP requests.
 */
class QueuedVerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }
}
