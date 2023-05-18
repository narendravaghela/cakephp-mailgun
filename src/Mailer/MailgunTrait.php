<?php
declare(strict_types=1);

/**
 * Mailgun Plugin for CakePHP
 * Copyright (c) Narendra Vaghela (http://www.narendravaghela.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.md
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Narendra Vaghela (http://www.narendravaghela.com)
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      https://github.com/narendravaghela/cakephp-mailgun
 * @since     5.0.0
 */

namespace Mailgun\Mailer;

use DateTime;
use Mailgun\Mailer\Exception\MailgunApiException;
use Mailgun\MailgunPlugin;

/**
 * Trait MailgunTrait
 *
 * @package Mailgun\Mailer
 *
 * @property \Cake\Mailer\Message $message
 */
trait MailgunTrait
{
    /**
     * Desired time of delivery.
     *
     * @param \DateTime $time Time to deliver message
     *
     * @return $this
     *
     * @throws \Mailgun\Mailer\Exception\MailgunApiException If delivery date is greater than 3 days.
     * @throws \Exception If date time is invalid
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#id8
     * @see https://documentation.mailgun.com/en/latest/api-intro.html#date-format
     *
     */
    public function deliverBy(DateTime $time): static
    {
        if ($time->diff(new DateTime())->days > 3) {
            throw new MailgunApiException('Delivery date can only be max of 3 days in the future.');
        }
        $this->message->addHeaders(['X-Mailgun-Deliver-By' => $time->format(MailgunPlugin::TIMEFORMAT)]);

        return $this;
    }

    /**
     * Enables/disables DKIM signatures on a per-message basis.
     *
     * @param bool $enable True to enable DKIM, False to disable DKIM.
     *
     * @return $this
     */
    public function enableDkim(bool $enable = true): static
    {
        $this->message->addHeaders(['X-Mailgun-Dkim' => $enable ? 'yes' : 'no']);

        return $this;
    }

    /**
     * Toggles tracking on a per-message basis
     *
     * @param bool $track True to track message, False to not track message.
     *
     * @return $this
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#tracking-messages
     */
    public function enableTracking(bool $track = true): static
    {
        $this->message->addHeaders(['X-Mailgun-Track' => $track ? 'yes' : 'no']);

        return $this;
    }

    /**
     * Require the message to be sent via TLS
     *
     * @param bool $tls True to require the message to be sent via TLS, False to try TLS first and then downgrade to
     * plain text.
     *
     * @return $this
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#tls-sending
     */
    public function requireTls(bool $tls = false): static
    {
        $this->message->addHeaders(['X-Mailgun-Require-TLS' => $tls ? 'true' : 'false']);

        return $this;
    }

    /**
     * Attach custom data to the message.
     *
     * @param array $vars Array of data to attach to the message.
     *
     * @return $this
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#manual-customdata
     */
    public function setMailgunVars(array $vars): static
    {
        $this->message->addHeaders(['X-Mailgun-Variables' => $vars]);

        return $this;
    }

    /**
     * Variables to substitute when sending batched messages.
     *
     * @param array $vars Array of variables to set. The first level key must be the recipient email address.
     *
     * @return $this
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#batch-sending
     */
    public function setRecipientVars(array $vars): static
    {
        $this->message->addHeaders(['X-Mailgun-Recipient-Variables' => json_encode($vars)]);

        return $this;
    }

    /**
     * Sets the Mailgun Tags for this message.
     *
     * @param array|string $tags Array of tags.
     *
     * @return $this
     *
     * @throws \Mailgun\Mailer\Exception\MailgunApiException if more than 3 tags are set
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#tagging
     */
    public function setTags(array|string $tags): static
    {
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }
        if (count($tags) > 3) {
            throw new MailgunApiException('You can only set a max of 3 tags.');
        }

        $this->message->addHeaders(['X-Mailgun-Tag' => json_encode($tags)]);

        return $this;
    }

    /**
     * Verify TLS certificate
     *
     * @param bool $verify True to not verify the certificate and hostname when sending, False to verify the certificate
     * and hostname if the certifiate and hostname cannot be verified a TLS connection will not be established.
     *
     * @return $this
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#tls-sending
     */
    public function skipVerification(bool $verify = false): static
    {
        $this->message->addHeaders(['X-Mailgun-Skip-Verification' => $verify ? 'true' : 'false']);

        return $this;
    }

    /**
     * Enables sending in test mode.
     *
     * @param bool $drop True to drop message, False to send message.
     *
     * @return $this
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#manual-testmode
     */
    public function testMode(bool $drop = true): static
    {
        $this->message->addHeaders(['X-Mailgun-Drop-Message' => $drop ? 'yes' : 'no']);

        return $this;
    }

    /**
     * Toggles click tracking on a per-message basis.
     *
     * @param bool|null $track True to track click, False to not track click, null to set HTML only click tracking.
     *
     * @return $this
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#tracking-messages
     */
    public function trackClicks(bool $track = null): static
    {
        if ($track === null) {
            $this->message->addHeaders(['X-Mailgun-Track-Clicks' => 'htmlonly']);
        } else {
            $this->message->addHeaders(['X-Mailgun-Track-Clicks' => $track ? 'yes' : 'no']);
        }

        return $this;
    }

    /**
     * Toggles open tracking on a per-message basis.
     *
     * @param bool $track True to enable open tracking, False to disable open tracking.
     *
     * @return $this
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#tracking-messages
     */
    public function trackOpens(bool $track = false): static
    {
        $this->message->addHeaders(['X-Mailgun-Track-Opens' => $track ? 'yes' : 'no']);

        return $this;
    }
}
