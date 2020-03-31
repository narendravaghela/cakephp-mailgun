<?php
/**
 * Mailgun Plugin for CakePHP 3
 * Copyright (c) Narendra Vaghela (http://www.narendravaghela.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.md
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2020 Copyright (c) Narendra Vaghela (http://www.narendravaghela.com)
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
use Cake\Mailer\Email;
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

    /**
     * @var string
     */
    protected $_mailgunHeaderPrefix = 'X-Mailgun';

    /**
     * @var array
     */
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
     * Constructor
     *
     * @param array $config Configuration options.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->_formData = new FormData();
    }

    /**
     * Send mail
     *
     * @param \Cake\Mailer\Email $email Cake Email
     * @return array An array with api response and email parameters
     * @throws MailgunApiException If api key or domain is not set
     */
    public function send(Email $email)
    {
        if (empty($this->getConfig('apiKey'))) {
            throw new MailgunApiException('Api Key for Mailgun could not found.');
        }

        if (empty($this->getConfig('domain'))) {
            throw new MailgunApiException('Domain for Mailgun could not found.');
        }

        $this->_prepareEmailAddresses($email);

        $subject = new FormDataPart('subject', $email->getSubject());
        $this->_formData->add($subject);

        $emailFormat = $email->getEmailFormat();
        $this->_formData->add('html', trim($email->message(Email::MESSAGE_HTML)));
        if ('both' == $emailFormat || 'text' == $emailFormat) {
            $this->_formData->add('text', trim($email->message(Email::MESSAGE_TEXT)));
        }

        $this->_processHeaders($email);

        $attachments = $email->getAttachments();
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
    protected function _addFile($partName, $attachment, $fileName = '')
    {
        if (isset($attachment['file'])) {
            $file = $this->_formData->addFile($partName, fopen($attachment['file'], 'r'));
        } else {
            $file = $this->_formData->newPart($partName, base64_decode($attachment['data']));
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
    public function getRequestData()
    {
        return $this->_formData;
    }

    /**
     * Sets additional option
     *
     * This will set extra option to use in message request
     *
     * Example
     * ```
     *  $email = new Email('mailgun');
     *  $emailInstance = $email->getTransport();
     *  $emailInstance->setOption('testmode', 'yes');
     *  $emailInstance->setOption('tag', ['newsletter', 'monthly newsletter']);
     *
     *  $email->send();
     * ```
     *
     * @see https://documentation.mailgun.com/en/latest/api-sending.html#sending
     * @param string $name Name of option
     * @param string|array $value Option value or array of values (string)
     * @return $this
     * @throws MailgunApiException If value is not a valid string or array
     * @deprecated 4.0.0 Please use Mailgun\Mailer\MailgunEmail or Cake\Mailer\Email and set the X-Mailgun-* headers directly.
     * @codeCoverageIgnore
     */
    public function setOption($name, $value)
    {
        deprecationWarning('setOption(): is deprecated please use Mailgun\Mailer\MailgunEmail or Cake\Mailer\Email and set the X-Mailgun-* headers directly.');

        if (!in_array($name, $this->_allowedOptions)) {
            throw new MailgunApiException("setOption(): {$name} is not a valid option name for Mailgun.");
        }

        if (is_array($value)) {
            foreach ($value as $optionValue) {
                $this->_formData->add("{$this->_optionPrefix}$name", (string)$optionValue);
            }
        } elseif (is_string($value) || is_numeric($value)) {
            $this->_formData->add("{$this->_optionPrefix}$name", (string)$value);
        } else {
            throw new MailgunApiException("setOption(): Value of option must be a valid string or array.");
        }

        return $this;
    }

    /**
     * Sets custom message variable
     *
     * This data will be passed as a header within the email, X-Mailgun-Variables
     * The value must be a valid JSON string or array (will be convetred to JSON)
     *
     * Example
     * ```
     *  $email = new Email('mailgun');
     *  $emailInstance = $email->getTransport();
     *  $emailInstance->setCustomMessageData('my-custom-data', '{"my_message_id": 123}');
     *
     *  // or
     *  $customMessageData = ['foo' => 'bar', 'john' => 'doe'];
     *  $emailInstance->setCustomMessageData('my-custom-data', json_encode($customMessageData));
     *
     *  // or
     *  $customMessageData = ['foo' => 'bar', 'john' => 'doe'];
     *  $emailInstance->setCustomMessageData('my-custom-data', $customMessageData);
     *
     *  $email->send();
     * ```
     *
     * @param string $name Variable name
     * @param mixed $value A valid JSON string or array
     * @return $this
     * @throws MailgunApiException If value is not a valid JSON string
     * @deprecated 4.0.0 Please use Mailgun\Mailer\MailgunEmail or Cake\Mailer\Email and set the X-Mailgun-* headers directly.
     * @codeCoverageIgnore
     */
    public function setCustomMessageData($name, $value)
    {
        deprecationWarning('setCustomMessageData(): is deprecated please use Mailgun\Mailer\MailgunEmail or Cake\Mailer\Email and set the X-Mailgun-* headers directly.');

        if (is_array($value)) {
            $this->_formData->add("{$this->_varPrefix}$name", json_encode($value));
        } elseif (is_string($value)) {
            $decoded = json_decode($value);
            if (!empty($decoded) && json_last_error() == JSON_ERROR_NONE) {
                $this->_formData->add("{$this->_varPrefix}$name", $value);
            } else {
                throw new MailgunApiException("setCustomMessageData(): Value must be a valid JSON string or an array.");
            }
        }

        return $this;
    }

    /**
     * Sets recipient variables
     *
     * This will set a JSON data to Mailgun message and will be replaced in
     * message body of each recipient.
     *
     * Example
     * ```
     *  $email = new Email('mailgun');
     *  $emailInstance = $email->getTransport();
     *
     *  $recipientData = [
     *      'foo@example.com' => ['name' => 'Foo Bar'],
     *      'john@example.com' => ['name' => 'John Doe'],
     *  ];
     *  $emailInstance->setRecipientVars($recipientData);
     *
     *  $email->send();
     * ```
     *
     * In your message body, you can use %recipient.name% and it will be replaced
     * by actual value passed in recipient variable.
     *
     * Note:
     *  - Recipient's email address must be set as key of array
     *  - You should set recipient variables in case of batch sending (multiple recipients)
     *
     * @param string|array $value A valid JSON string or array
     * @return $this
     * @deprecated 4.0.0 Please use Mailgun\Mailer\MailgunEmail or Cake\Mailer\Email and set the X-Mailgun-* headers directly.
     * @codeCoverageIgnore
     */
    public function setRecipientVars($value)
    {
        deprecationWarning('setRecipientVars(): is deprecated please use Mailgun\Mailer\MailgunEmail or Cake\Mailer\Email and set the X-Mailgun-* headers directly.');

        if (is_array($value)) {
            $this->_formData->add("recipient-variables", json_encode($value));
        } else {
            $decoded = json_decode($value);
            if (!empty($decoded) && json_last_error() == JSON_ERROR_NONE) {
                $this->_formData->add("recipient-variables", $value);
            } else {
                throw new MailgunApiException("setRecipientVars(): Value must be a valid JSON string.");
            }
        }

        return $this;
    }

    /**
     * Prepares the email addresses
     *
     * @param \Cake\Mailer\Email $email Cake Email instance
     * @return void
     */
    protected function _prepareEmailAddresses(Email $email)
    {
        $from = $email->getFrom();
        if (key($from) != $from[key($from)]) {
            $this->_formData->add('from', sprintf("%s <%s>", $from[key($from)], key($from)));
        } else {
            $this->_formData->add('from', sprintf("%s <%s>", key($from), key($from)));
        }

        foreach ($email->getSender() as $senderEmail => $senderName) {
            $this->_formData->add('h:Sender', sprintf("%s <%s>", $senderName, $senderEmail));
        }

        foreach ($email->getTo() as $toEmail => $toName) {
            $this->_formData->add('to', sprintf("%s <%s>", $toName, $toEmail));
        }

        foreach ($email->getCc() as $ccEmail => $ccName) {
            $this->_formData->add('cc', sprintf("%s <%s>", $ccName, $ccEmail));
        }

        foreach ($email->getBcc() as $bccEmail => $bccName) {
            $this->_formData->add('bcc', sprintf("%s <%s>", $bccName, $bccEmail));
        }

        foreach ($email->getReplyTo() as $replyToEmail => $replyToName) {
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
        $http = new Client();
        $response = $http->post("{$this->getConfig('apiEndpoint')}/{$this->getConfig('domain')}/messages", (string)$this->_formData, [
            'auth' => ['username' => 'api', 'password' => $this->getConfig('apiKey')],
            'headers' => ['Content-Type' => $this->_formData->contentType()],
        ]);

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
    protected function _reset()
    {
        $this->_formData = new FormData();
    }

    /**
     * Process the Email headers and covert them to mailgun form parts
     *
     * @param Email $email Email to work with
     *
     * @return void
     */
    protected function _processHeaders(Email $email)
    {
        $customHeaders = $email->getHeaders(['_headers']);
        foreach ($customHeaders as $header => $value) {
            if (0 === strpos($header, $this->_mailgunHeaderPrefix) && !empty($value)) {
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
            } elseif (0 === strpos($header, $this->_customHeaderPrefix) && !empty($value)) {
                $this->_formData->add($header, $value);
            } else {
                $this->_formData->add($this->_customHeaderPrefix . $header, $value);
            }
        }
    }
}
