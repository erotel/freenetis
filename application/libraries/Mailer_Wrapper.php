<?php defined('SYSPATH') or die('No direct script access.');

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class Mailer_Wrapper
{
  private Mailer $mailer;

  public function __construct(?string $dsn = null)
  {
    // Default: sendmail (nejméně nastavování)
    // Alternativa: smtp://localhost:25
    $dsn = $dsn ?: (string)Config::get('mailer_dsn', 'sendmail://default');

    $transport = Transport::fromDsn($dsn);
    $this->mailer = new Mailer($transport);
  }

  public function sendHtml(
    string $from,
    string $to,
    string $subject,
    string $htmlBody,
    array $bcc = [],
    array $attachments = []
  ): void {
    $email = (new Email())
      ->from(new Address($from, 'PVfree.net'))
      ->to($to)
      ->subject($subject)
      ->html($htmlBody)
      ->text($this->htmlToText($htmlBody));

    foreach ($bcc as $b) {
      $email->addBcc($b);
    }

    foreach ($attachments as $a) {
      if (empty($a['path'])) continue;

      $email->attachFromPath(
        $a['path'],
        $a['name'] ?? null,
        $a['mime'] ?? null
      );
    }

    $this->mailer->send($email);
  }

  private function htmlToText(string $html): string
  {
    // jednoduchý fallback: br/p/div -> \n a strip_tags
    $h = str_ireplace(
      ['<br>', '<br/>', '<br />', '</p>', '</div>', '</tr>', '</li>'],
      "\n",
      $html
    );
    $h = html_entity_decode($h, ENT_QUOTES, 'UTF-8');
    $h = strip_tags($h);
    // zredukuj prázdné řádky
    $h = preg_replace("/\n{3,}/", "\n\n", $h);
    return trim($h);
  }


  }
