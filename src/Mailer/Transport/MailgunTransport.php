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
 * @since     1.0.0
 */

namespace Mailgun\Mailer\Transport;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\FormData;
use Cake\Http\Client\FormDataPart;
use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Message;
use Mailgun\Mailer\Exception\MailgunApiException;

/**
 * Send mail using Mailgun API
 */
class MailgunTransport extends AbstractTransport
{
    /**
     * Default config for this class
     *
     * @var array
     */
    protected $_defaultConfig = [
        'apiEndpoint' => 'https://api.mailgun.net/v3',
        'domain' => '',
        'apiKey' => '',
    ];

    /**
     * Additional options allowed by Mailgun API
     *
     * @var array
     */
    protected $_allowedOptions = [
        'tag',
        'dkim',
        'deliverytime',
        'testmode',
        'tracking',
        'tracking-clicks',
        'tracking-opens',
        'require-tls',
        'skip-verification',
    ];

    protected $_mailgunHeaderPrefix = 'X-Mailgun';

    protected $_mailgunHeaders = [
        'X-Mailgun-Tag' => 'tag',
        'X-Mailgun-Dkim' => 'dkim',
        'X-Mailgun-Deliver-By' => 'deliverytime',
        'X-Mailgun-Drop-Message' => 'testmode',
        'X-Mailgun-Track' => 'tracking',
        'X-Mailgun-Track-Clicks' => 'tracking-clicks',
        'X-Mailgun-Track-Opens' => 'tracking-opens',
        'X-Mailgun-Require-TLS' => 'require-tls',
        'X-Mailgun-Skip-Verification' => 'skip-verification',
    ];

    /**
     * Prefix for setting options
     *
     * @var string
     */
    protected $_optionPrefix = 'o:';

    /**
     * Prefix for setting custom headers
     *
     * @var string
     */
    protected $_customHeaderPrefix = 'h:';

    /**
     * Prefix for variables
     *
     * @var string
     */
    protected $_varPrefix = 'v:';

    /**
     * FormData object
     *
     * @var \Cake\Http\Client\FormData
     */
    protected $_formData;

    /**
     * @var \Cake\Http\Client HTTP Client to use
     */
    public $Client;

    /**
     * Constructor
     *
     * @param array $config Configuration options.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->_formData = new FormData();
        $this->Client = new Client();
    }

    /**
     * Send mail
     *
     * @param \Cake\Mailer\Message $message Cake Email
     *
     * @return array An array with api response and email parameters
     *
     * @throws \Mailgun\Mailer\Exception\MailgunApiException If api key, domain, or from address is not set
     */
    public function send(Message $message): array
    {
        if (empty($this->getConfig('apiKey'))) {
            throw new MailgunApiException('Api Key for Mailgun could not found.');
        }

        if (empty($this->getConfig('domain'))) {
            throw new MailgunApiException('Domain for Mailgun could not found.');
        }

        $this->_prepareEmailAddresses($message);

        $subject = new FormDataPart('subject', $message->getSubject());
        $this->_formData->add($subject);

        $emailFormat = $message->getEmailFormat();
        $this->_formData->add('html', trim($message->getBodyHtml()));
        if ($emailFormat == 'both' || $emailFormat == 'text') {
            $this->_formData->add('text', trim($message->getBodyString()));
        }

        $this->_processHeaders($message);

        $attachments = $message->getAttachments();
        if (!empty($attachments)) {
            foreach ($attachments as $fileName => $attachment) {
                if (empty($attachment['contentId'])) {
                    $file = $this->_addFile('attachment', $attachment, (string)$fileName);
                } else {
                    $file = $this->_addFile('inline', $attachment, (string)$fileName);
                    $file->contentId($attachment['contentId']);
                }
                $file->disposition('attachment');
            }
        }

        try {
            return $this->_sendEmail();
        } catch (MailgunApiException $e) {
            throw $e;
        } finally {
            $this->_reset();
        }
    }

    /**
     * Add file attachment to email.
     *
     * @param string $partName Name of the file part
     * @param array $attachment Attachment as initially set via setAttachment()
     * @param string $fileName Desired filename of the attachment
     * @return \Cake\Http\Client\FormDataPart
     */
    protected function _addFile($partName, $attachment, $fileName = ''): \Cake\Http\Client\FormDataPart
    {
        if (isset($attachment['file'])) {
            $file = $this->_formData->addFile($partName, fopen($attachment['file'], 'r'));
        } else {
            $file = $this->_formData->newPart($partName, (string)base64_decode($attachment['data']));
            $file->type($attachment['mimetype']);
            $file->filename($fileName);
            $this->_formData->add($file);
        }

        return $file;
    }

    /**
     * Returns the parameters for API request.
     *
     * @return \Cake\Http\Client\FormData
     */
    public function getRequestData(): FormData
    {
        return $this->_formData;
    }

    /**
     * Prepares the email addresses
     *
     * @param \Cake\Mailer\Message $message Cake Email instance
     * @return void
     */
    protected function _prepareEmailAddresses(Message $message): void
    {
        $from = $message->getFrom();
        if (empty($from)) {
            throw new MailgunApiException('Missing from email address.');
        }
        if (key($from) != $from[key($from)]) {
            $this->_formData->add('from', sprintf("%s <%s>", $from[key($from)], key($from)));
        } else {
            $this->_formData->add('from', sprintf("%s <%s>", key($from), key($from)));
        }

        foreach ($message->getSender() as $senderEmail => $senderName) {
            $this->_formData->add('h:Sender', sprintf("%s <%s>", $senderName, $senderEmail));
        }

        foreach ($message->getTo() as $toEmail => $toName) {
            $this->_formData->add('to', sprintf("%s <%s>", $toName, $toEmail));
        }

        foreach ($message->getCc() as $ccEmail => $ccName) {
            $this->_formData->add('cc', sprintf("%s <%s>", $ccName, $ccEmail));
        }

        foreach ($message->getBcc() as $bccEmail => $bccName) {
            $this->_formData->add('bcc', sprintf("%s <%s>", $bccName, $bccEmail));
        }

        foreach ($message->getReplyTo() as $replyToEmail => $replyToName) {
            $this->_formData->add('h:Reply-To', sprintf("%s <%s>", $replyToName, $replyToEmail));
        }
    }

    /**
     * Make an API request to send email
     *
     * @return mixed JSON Response from Mailgun API
     */
    protected function _sendEmail()
    {
        $response = $this->Client->post(
            "{$this->getConfig('apiEndpoint')}/{$this->getConfig('domain')}/messages",
            (string)$this->_formData,
            [
                'auth' => ['username' => 'api', 'password' => $this->getConfig('apiKey')],
                'headers' => ['Content-Type' => $this->_formData->contentType()],
            ]
        );

        if (!$response->isSuccess()) {
            throw new MailgunApiException($response->getStringBody(), $response->getStatusCode());
        }

        $result = [];
        $result['apiResponse'] = $response->getJson();
        $result['responseCode'] = $response->getStatusCode();
        if (Configure::read('debug')) {
            $result['reqData'] = $this->_formData;
        }

        return $result;
    }

    /**
     * Resets
     *
     * @return void
     */
    protected function _reset(): void
    {
        $this->_formData = new FormData();
    }

    /**
     * Process the Email headers and covert them to mailgun form parts
     *
     * @param \Cake\Mailer\Message $message Email to work with
     *
     * @return void
     */
    protected function _processHeaders(Message $message): void
    {
        $customHeaders = $message->getHeaders(['_headers']);
        foreach ($customHeaders as $header => $value) {
            if (strpos($header, $this->_mailgunHeaderPrefix) === 0 && !empty($value)) {
                if ($header === $this->_mailgunHeaderPrefix . '-Recipient-Variables') {
                    $this->_formData->add("recipient-variables", $value);
                } elseif ($header === $this->_mailgunHeaderPrefix . '-Variables') {
                    foreach ($value as $k => $v) {
                        if (is_array($v)) {
                            $this->_formData->add("{$this->_varPrefix}$k", json_encode($v));
                        } else {
                            $this->_formData->add("{$this->_varPrefix}$k", $v);
                        }
                    }
                } elseif ($header === $this->_mailgunHeaderPrefix . '-Tag') {
                    $var = $this->_mailgunHeaders[$header];
                    if (is_string($value)) {
                        $value = json_decode($value);
                    }
                    $this->_formData->add("{$this->_optionPrefix}$var", $value);
                } else {
                    $var = $this->_mailgunHeaders[$header];
                    $this->_formData->add("{$this->_optionPrefix}$var", $value);
                }
            } elseif (strpos($header, $this->_customHeaderPrefix) === 0 && !empty($value)) {
                $this->_formData->add($header, $value);
            } else {
                $this->_formData->add($this->_customHeaderPrefix . $header, $value);
            }
        }
    }
}
