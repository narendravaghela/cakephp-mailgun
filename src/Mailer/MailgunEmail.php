<?php
declare(strict_types=1);

namespace Mailgun\Mailer;

use Cake\Mailer\Email as CoreEmail;

/**
 * CakePHP Email class.
 *
 * This class is used for sending Internet Message Format based
 * on the standard outlined in https://www.rfc-editor.org/rfc/rfc2822.txt
 *
 * ### Configuration
 *
 * Configuration for Email is managed by Email::config() and Email::configTransport().
 * Email::config() can be used to add or read a configuration profile for Email instances.
 * Once made configuration profiles can be used to re-use across various email messages your
 * application sends.
 *
 * @deprecated 5.0.0 This class will be removed in CakePHP 5.0 and Mailgun 6.0. Use MailgunTrait on your custom Mailer class
 */
class MailgunEmail extends CoreEmail
{
    use MailgunTrait;

    /**
     * Constructor
     *
     * @param array $config Configuration options.
     */
    public function __construct($config = null)
    {
        parent::__construct($config);

        $this->setProfile(['transport' => 'mailgun']);
    }
}
