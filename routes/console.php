<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('send-mail {recipient? : Recipient email address}', function () {
    $apiKey = config('services.mailtrap.api_token');

    if (empty($apiKey)) {
        $this->error('MAILTRAP_API_TOKEN is not configured in .env or config/services.php.');
        $this->comment('Please set MAILTRAP_API_TOKEN in your .env file.');
        return 1;
    }

    $toEmail = $this->argument('recipient') ?? '2024118263@ufp.edu.pt';
    $fromAddress = config('services.mailtrap.from_address', 'hello@demomailtrap.co');
    $fromName = config('services.mailtrap.from_name', config('app.name', 'Barbearia'));

    $email = (new MailtrapEmail())
        ->from(new Address($fromAddress, $fromName))
        ->to(new Address($toEmail))
        ->subject('You are awesome!')
        ->category('Integration Test')
        ->text('Congrats for sending test email with Mailtrap!');

    $this->info("Sending test email to {$toEmail} via Mailtrap API...");

    try {
        $response = MailtrapClient::initSendingEmails(
            apiKey: $apiKey
        )->send($email);

        $responseData = ResponseHelper::toArray($response);
        $this->info('Email sent successfully!');
        $this->line(json_encode($responseData, JSON_PRETTY_PRINT));
        $this->comment('Check sent logs at: https://mailtrap.io/sending/email_logs');

        return 0;
    } catch (\Throwable $e) {
        $this->error('Failed to send email: ' . $e->getMessage());
        return 1;
    }
})->purpose('Send Mail with Mailtrap API');
