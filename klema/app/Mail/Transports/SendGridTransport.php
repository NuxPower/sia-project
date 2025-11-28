<?php

namespace App\Mail\Transports;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class SendGridTransport extends AbstractTransport
{
    protected $apiKey;

    public function __construct(string $apiKey)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        
        $from = $email->getFrom()[0];
        $to = array_map(fn($addr) => $addr->getAddress(), $email->getTo());
        
        $body = [
            'personalizations' => [
                [
                    'to' => array_map(fn($email) => ['email' => $email], $to),
                ],
            ],
            'from' => [
                'email' => $from->getAddress(),
                'name' => $from->getName() ?: null,
            ],
            'subject' => $email->getSubject(),
            'content' => [
                [
                    'type' => $email->getHtmlBody() ? 'text/html' : 'text/plain',
                    'value' => $email->getHtmlBody() ?: $email->getTextBody(),
                ],
            ],
        ];

        // Handle CC
        if (!empty($email->getCc())) {
            $body['personalizations'][0]['cc'] = array_map(
                fn($addr) => ['email' => $addr->getAddress()],
                $email->getCc()
            );
        }

        // Handle BCC
        if (!empty($email->getBcc())) {
            $body['personalizations'][0]['bcc'] = array_map(
                fn($addr) => ['email' => $addr->getAddress()],
                $email->getBcc()
            );
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://api.sendgrid.com/v3/mail/send', $body);

            if ($response->failed()) {
                Log::error('SendGrid API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'to' => $to,
                    'subject' => $email->getSubject(),
                ]);
                
                throw new \RuntimeException(
                    sprintf('SendGrid API error: %s', $response->body()),
                    $response->status()
                );
            }

            Log::info('SendGrid email sent successfully', [
                'to' => $to,
                'subject' => $email->getSubject(),
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::error('SendGrid transport exception', [
                'message' => $e->getMessage(),
                'to' => $to,
                'subject' => $email->getSubject(),
            ]);
            throw $e;
        }
    }

    public function __toString(): string
    {
        return sprintf('sendgrid+api://%s', 'sendgrid');
    }
}

