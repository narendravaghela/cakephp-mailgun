<?php
/**
 * Mailgun plugin for CakePHP 3
 * Copyright (c) Narendra Vaghela (http://www.narendravaghela.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.md
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Narendra Vaghela (http://www.narendravaghela.com)
 * @link          https://github.com/narendravaghela/cakephp-mailgun
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace MailgunEmail\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;
use Http\Adapter\Guzzle6\Client;
use MailgunEmail\Mailer\Exception\MissingCredentialsException;
use Mailgun\Mailgun;
use Mailgun\Tests\Mock\Mailgun as MailgunTest;

/**
 * Mailgun Transport class
 *
 * Send email using Mailgun SDK
 */
class MailgunTransport extends AbstractTransport
{

    /**
     * Default config for this class
     *
     * @var array
     */
    protected $_defaultConfig = [
        'apiKey' => null,
        'domain' => null,
        'ssl' => true,
        'isTest' => false // for Unit Test only
    ];

    /**
     * CakePHP Email object
     *
     * @var object Cake\Mailer\Email
     */
    protected $_cakeEmail;

    /**
     * Mailgun Class Object
     *
     * @var object Mailgun Class Object
     */
    protected $_mgObject;

    /**
     * Mailgun API version
     *
     * @var string
     */
    protected $_apiVersion = 'v3';

    /**
     * Mapping of Mailgun parameters with Email
     *
     * @var array
     */
    protected $_defaultParamsMap = [
        'From' => 'from',
        'Sender' => 'sender',
        'Reply-To' => 'h:Reply-To',
        'Disposition-Notification-To' => 'h:Disposition-Notification-To',
        'Return-Path' => 'h:Return-Path',
        'To' => 'to',
        'Cc' => 'cc',
        'Bcc' => 'bcc',
        'Subject' => 'subject'
    ];

    /**
     * Additional parameters for message
     *
     * @var array
     */
    protected $_additionalParams = [
        'o:tag', // Tag string. See https://documentation.mailgun.com/user_manual.html#tagging
        'o:campaign', //Id of the campaign the message belongs to
        'o:dkim', // Enables/disables DKIM signatures on per-message basis. Possible values: yes or no
        'o:deliverytime', // Desired time of delivery. See https://documentation.mailgun.com/api-intro.html#date-format
        'o:testmode', // Enables sending in test mode. See https://documentation.mailgun.com/user_manual.html#manual-testmode
        'o:tracking', // Toggles tracking on a per-message basis. Possible values: yes or no
        'o:tracking-clicks', // Toggles clicks tracking on a per-message basis. Possible values: yes, no or htmlonly
        'o:tracking-opens', // Toggles opens tracking on a per-message basis. Possible values: yes or no
        'o:require-tls', // Send message over a TLS connection. Possible values: true or false. Default false.
        'o:skip-verification' // Whether the certificate and hostname will be verified or not. Possible values: true or false. Default false.
    ];

    /**
     * Header prefix
     *
     * h:prefix followed by an arbitrary value allows to append a custom
     * MIME header to the message
     *
     * @var string
     */
    protected $_customHeaderPrefix = 'h:';

    /**
     * Variable prefix
     *
     * v:prefix followed by an arbitrary name allows to attach a custom
     * JSON data to the message
     *
     * @var string
     */
    protected $_customVariablePrefix = 'v:';

    /**
     * Extra variable prefix
     *
     * ev:prefix followed by an arbitrary value allows to append an extra
     * variable to the message
     *
     * @var string
     */
    protected $_extraVariablePrefix = 'ev:';

    /**
     * Mailgun parameters
     *
     * @var array
     */
    protected $_params = [];

    /**
     * Email attachments
     *
     * @var array
     */
    protected $_attachments = [];

    /**
     * The response of the last sent email.
     *
     * @var object
     */
    protected $_lastResponse;

    /**
     * Send mail
     *
     * @param Email $email Cake Email
     * @return mixed Mailgun result
     */
    public function send(Email $email)
    {
        $this->_setMgObject();

        $this->_cakeEmail = $email;

        $this->_headers = $this->_getEmailHeaders();
        foreach ($this->_defaultParamsMap as $cakeParam => $mgParam) {
            if (isset($this->_defaultParamsMap[$cakeParam]) && !empty($this->_headers[$cakeParam])) {
                $this->_params[$mgParam] = $this->_headers[$cakeParam];
            } elseif (!empty($this->_headers[$cakeParam])) {
                $this->_params[$this->_customHeaderPrefix . $cakeParam] = $this->_headers[$cakeParam];
            }
        }

        $this->_additionalHeaders = $this->_getAdditionalEmailHeaders();
        if (!empty($this->_additionalHeaders)) {
            foreach ($this->_additionalHeaders as $header => $value) {
                if (in_array($header, $this->_additionalParams) && !empty($value)) {
                    $this->_params[$header] = $value;
                } elseif (0 === strpos($header, $this->_customVariablePrefix) && !empty($value)) {
                    $decoded = json_decode($value);
                    if (!empty($decoded) && json_last_error() == JSON_ERROR_NONE) {
                        $this->_params[$header] = $value;
                    }
                } elseif (0 === strpos($header, $this->_customHeaderPrefix) && !empty($value)) {
                    $this->_params[$header] = $value;
                } elseif (0 === strpos($header, $this->_extraVariablePrefix) && !empty($value)) {
                    $this->_params[str_replace($this->_extraVariablePrefix, '', $header)] = $value;
                } elseif (!empty($value)) {
                    $this->_params[$this->_customHeaderPrefix . $header] = $value;
                }
            }
        }

        $emailFormat = $this->_cakeEmail->getEmailFormat();

        $this->_params['html'] = $this->_cakeEmail->message(Email::MESSAGE_HTML);

        if ('both' == $emailFormat || 'text' == $emailFormat) {
            $this->_params['text'] = $this->_cakeEmail->message(Email::MESSAGE_TEXT);
        }

        $attachments = $this->_processAttachments();
        if (!empty($attachments)) {
            $this->_attachments = $attachments;
        }

        return $this->_sendMessage();
    }

    /**
     * Sends mail using Mailgun
     *
     * @return mixed Mailgun response
     */
    protected function _sendMessage()
    {
        $this->_lastResponse = null;
        $response = $this->_mgObject->sendMessage($this->getConfig('domain'), $this->_params, $this->_attachments);
        $this->_reset();
        $this->_lastResponse = $response;

        return $response;
    }

    /**
     * Returns basic headers.
     *
     * @return array
     */
    protected function _getEmailHeaders()
    {
        return $this->_cakeEmail->getHeaders(['from', 'sender', 'replyTo', 'readReceipt', 'to', 'cc', 'bcc', 'subject', 'returnPath']);
    }

    /**
     * Returns additional headers set via Email::setHeaders().
     *
     * @return array
     */
    protected function _getAdditionalEmailHeaders()
    {
        return $this->_cakeEmail->getHeaders(['_headers']);
    }

    /**
     * Prepares attachments
     *
     * @return array
     */
    protected function _processAttachments()
    {
        $attachments = [];

        foreach ($this->_cakeEmail->getAttachments() as $name => $file) {
            if (!empty($file['contentId'])) {
                $attachments['inline'][] = ['filePath' => '@' . $file['file'], 'remoteName' => $file['contentId']];
            } else {
                $attachments['attachment'][] = ['filePath' => '@' . $file['file'], 'remoteName' => $name];
            }
        }

        return $attachments;
    }

    /**
     * Sets Mailgun object
     *
     * @throws MissingCredentialsException If API key or Sending Domain is missing
     */
    protected function _setMgObject()
    {
        if (empty($this->getConfig('apiKey'))) {
            throw new MissingCredentialsException(['API Key']);
        }

        if (empty($this->getConfig('domain'))) {
            throw new MissingCredentialsException(['sending domain']);
        }

        if (!is_a($this->_mgObject, 'Mailgun')) {
            if (!$this->getConfig('isTest')) {
                $client = new Client();
                $this->_mgObject = new Mailgun($this->getConfig('apiKey'), $client);
            } else {
                $this->_mgObject = new MailgunTest($this->getConfig('apiKey'));
            }
        }

        if (!$this->getConfig('ssl')) {
            $this->_mgObject->setSslEnabled(false);
        }

        if ($this->getConfig('apiVersion')) {
            $this->_mgObject->setApiVersion($this->getConfig('apiVersion'));
        } else {
            $this->_mgObject->setApiVersion($this->_apiVersion);
        }
    }

    /**
     * Resets the variables to free memory
     *
     * @return void
     */
    protected function _reset()
    {
        $this->_mgObject = null;
        $this->_params = [];
        $this->_attachments = [];
    }
}
