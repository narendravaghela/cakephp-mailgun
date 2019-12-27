<?php
declare(strict_types=1);

namespace Mailgun\Mailer;

use Cake\Mailer\Mailer;

class MailgunMailer extends Mailer
{
    use MailgunTrait;
}
