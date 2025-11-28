<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued version of Laravel's password reset notification.
 * This prevents email sending from blocking HTTP requests.
 * Also overrides the reset URL to use the frontend URL instead of a Laravel route.
 */
class QueuedResetPassword extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  string  $token
     */
    public function __construct($token)
    {
        parent::__construct($token);
    }

    /**
     * Get the reset URL for the given notifiable.
     * Override to use frontend URL instead of Laravel route.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function resetUrl($notifiable)
    {
        $frontendUrl = env('FRONTEND_URL', env('APP_URL', 'http://localhost'));
        $token = $this->token;
        $email = $notifiable->getEmailForPasswordReset();

        // Build the frontend reset URL matching AuthContainer's expected format
        // AuthContainer supports both /password/reset/{token} path and ?view=reset&token=... query params
        // Using query param format for better SPA compatibility: /app?view=reset&token=...&email=...
        $baseUrl = rtrim($frontendUrl, '/');
        $encodedToken = urlencode($token);
        $encodedEmail = urlencode($email);
        
        return "{$baseUrl}/app?" . http_build_query([
            'view' => 'reset',
            'token' => $token,
            'email' => $email,
        ]);
    }
}

