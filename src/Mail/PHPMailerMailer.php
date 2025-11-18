<?php

declare(strict_types=1);

namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use Radix\Config\Config;
use Radix\Mailer\MailerInterface;
use Radix\Viewer\TemplateViewerInterface;

class PHPMailerMailer implements MailerInterface
{
    private PHPMailer $mailer;
    private TemplateViewerInterface $templateViewer;
    private Config $config;
    private string $fromEmail; // Standard From-email
    private string $fromName;  // Standard From-namn

    public function __construct(TemplateViewerInterface $templateViewer, Config $config)
    {
        $this->mailer = new PHPMailer(true);
        $this->templateViewer = $templateViewer;
        $this->config = $config;

        // Hämta inställningarna från konfigurationen
        $mailConfig = $this->config->get('email');
        if (!is_array($mailConfig)) {
            throw new \UnexpectedValueException('Config "email" måste vara en array.');
        }

        /** @var array{
         *     charset?: string,
         *     host?: string,
         *     auth?: bool,
         *     username?: string,
         *     password?: string,
         *     secure?: string,
         *     port?: int,
         *     email?: string,
         *     from?: string
         * } $mailConfig
         */

        $this->mailer->isSMTP();

        $debugValue = $this->config->get('email.debug');
        $debugOn = $debugValue === '1' || $debugValue === 1 || $debugValue === true;
        if ($debugOn) {
            $this->mailer->SMTPDebug = 2;
        }

        $this->mailer->CharSet   = $mailConfig['charset']  ?? 'UTF-8';
        $this->mailer->Host      = $mailConfig['host']     ?? 'localhost';
        $this->mailer->SMTPAuth  = $mailConfig['auth']     ?? true;
        $this->mailer->Username  = $mailConfig['username'] ?? '';
        $this->mailer->Password  = $mailConfig['password'] ?? '';
        $this->mailer->SMTPSecure = $mailConfig['secure']  ?? PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port      = $mailConfig['port']     ?? 587;

        // Definiera standard `From`-adress och namn från inställningarna
        $this->fromEmail = $mailConfig['email'] ?? '';
        $this->fromName  = $mailConfig['from']  ?? '';
    }

    /**
     * @param array<string, mixed> $options
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            // Sätt `From` från standardvärden om inget skickas
            $fromEmail = $this->fromEmail;
            $fromName  = $this->fromName;

            // Kontrollera om `From` skickas med som alternativ och uppdatera
            if (
                isset($options['from'])
                && is_string($options['from'])
                && filter_var($options['from'], FILTER_VALIDATE_EMAIL)
            ) {
                $fromEmail = $options['from'];
            }

            if (isset($options['from_name']) && is_string($options['from_name']) && $options['from_name'] !== '') {
                $fromName = $options['from_name'];
            }

            // Validera `From`-adressen
            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid From email address: $fromEmail");
            }

            // Sätt avsändare och mottagare
            $this->mailer->setFrom($fromEmail, $fromName);
            $this->mailer->addAddress($to);

            // Lägg till Reply-To om det skickas
            if (
                isset($options['reply_to'])
                && is_string($options['reply_to'])
                && filter_var($options['reply_to'], FILTER_VALIDATE_EMAIL)
            ) {
                $this->mailer->addReplyTo($options['reply_to']);
            }

            // is_html som ren bool
            $isHtml = true;
            if (array_key_exists('is_html', $options)) {
                $isHtml = is_bool($options['is_html']) ? $options['is_html'] : (bool) $options['is_html'];
            }

            $this->mailer->isHTML($isHtml);
            $this->mailer->Subject = $subject;

            // Rendera template om det specificeras
            if (isset($options['template']) && is_string($options['template']) && $options['template'] !== '') {
                $template = $options['template'];

                $data = isset($options['data']) && is_array($options['data'])
                    ? $options['data']
                    : [];
                /** @var array<string,mixed> $data */

                $body = $this->templateViewer->render($template, $data);
            }

            $this->mailer->Body = $body;

            return $this->mailer->send();
        } catch (\Exception $e) {
            error_log("Mail could not be sent. Error: " . $e->getMessage());
            return false;
        }
    }
}