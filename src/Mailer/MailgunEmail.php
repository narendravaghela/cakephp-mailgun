<?php
namespace Mailgun\Mailer;

use Cake\Mailer\Email as CoreEmail;
use Mailgun\Mailer\Exception\MailgunApiException;

class MailgunEmail extends CoreEmail
{
    const TIMEFORMAT = 'D, d M Y H:i:s O';

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

    /**
     * Sets the Mailgun Tags for this message.
     *
     * @param array|string $tags Array of tags.
     *
     * @return $this
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#tagging
     */
    public function setTags($tags)
    {
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }
        if (count($tags) > 3) {
            throw new MailgunApiException('You can only set a max of 3 tags.');
        }

        $this->addHeaders(['X-Mailgun-Tag' => json_encode($tags)]);

        return $this;
    }

    /**
     * Enables/disables DKIM signatures on a per-message basis.
     *
     * @param bool $enable True to enable DKIM, False to disable DKIM.
     *
     * @return $this
     */
    public function enableDkim($enable = true)
    {
        $this->addHeaders(['X-Mailgun-Dkim' => $enable ? 'yes' : 'no']);

        return $this;
    }

    /**
     * Desired time of delivery.
     *
     * @param \DateTime $time Time to deliver message
     *
     * @return $this
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#id8
     * @see https://documentation.mailgun.com/en/latest/api-intro.html#date-format
     *
     * @throws \Exception Emits Exception if error encountered.
     */
    public function deliverBy($time)
    {
        if ($time->diff(new \DateTime())->days > 3) {
            throw new MailgunApiException('Delivery date can only be max of 3 days in the future.');
        }
        $this->addHeaders(['X-Mailgun-Deliver-By' => $time->format(self::TIMEFORMAT)]);

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
    public function testMode($drop = true)
    {
        $this->addHeaders(['X-Mailgun-Drop-Message' => $drop ? 'yes' : 'no']);

        return $this;
    }

    /**
     * Togfgles tracking on a per-message basis
     *
     * @param bool $track True to track message, False to not track message.
     *
     * @return $this
     *
     * @see https://documentation.mailgun.com/en/latest/user_manual.html#tracking-messages
     */
    public function enableTracking($track = true)
    {
        $this->addHeaders(['X-Mailgun-Track' => $track ? 'yes' : 'no']);

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
    public function trackClicks($track = null)
    {
        if ($track === null) {
            $this->addHeaders(['X-Mailgun-Track-Clicks' => 'htmlonly']);
        } else {
            $this->addHeaders(['X-Mailgun-Track-Clicks' => $track ? 'yes' : 'no']);
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
    public function trackOpens($track = false)
    {
        $this->addHeaders(['X-Mailgun-Track-Opens' => $track ? 'yes' : 'no']);

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
    public function requireTls($tls = false)
    {
        $this->addHeaders(['X-Mailgun-Require-TLS' => $tls ? 'true' : 'false']);

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
    public function skipVerification($verify = false)
    {
        $this->addHeaders(['X-Mailgun-Skip-Verification' => $verify ? 'true' : 'false']);

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
    public function setRecipientVars(array $vars)
    {
        $this->addHeaders(['X-Mailgun-Recipient-Variables' => json_encode($vars)]);

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
    public function setMailgunVars(array $vars)
    {
        $this->addHeaders(['X-Mailgun-Variables' => $vars]);

        return $this;
    }
}
