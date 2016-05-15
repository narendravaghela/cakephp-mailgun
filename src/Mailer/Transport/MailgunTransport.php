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
use Mailgun\Mailgun;
use MailgunEmail\Mailer\Exception\MissingCredentialsException;

/**
 * Mailgun Transport class
 *
 * Send email using Mailgun
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
        'domain' => 'example.com',
        'ssl' => true,
    ];

    /**
     * Mailgun Class Object
     *
     * @var object Mailgun Class Object
     */
    protected $_mgObject;

    /**
     * The response of the last sent email.
     *
     * @var object
     */
    protected $_lastResponse;

    /**
     * CakeEmail headers
     *
     * @var array
     */
    protected $_headers;

    /**
     * Send mail
     *
     * @param Email $email Cake Email
     * @return array
     */
    public function send(Email $email)
    {
        $this->_setMgObject();
        $this->_mailgunParams = $this->_buildMailgunMessage($email);
        $this->_sendMessage();
    }

    protected function _sendMessage()
    {
        $this->_lastResponse = null;
        $response = $this->_mgObject->sendMessage($this->config('domain'), $this->_mailgunParams);
        $this->_lastResponse = $response;
        return $response;
    }

    protected function _buildMailgunMessage($email)
    {
        xdebug_break();
        $message = [];
        $this->_headers = $this->_prepareMessageHeaders($email);
        $message['from'] = $this->_headers['From'];
        $message['to'] = $this->_headers['To'];
        $message['subject'] = $this->_headers['Subject'];
        $message['html'] = $email->message(Email::MESSAGE_HTML);
        $message['text'] = $email->message(Email::MESSAGE_TEXT);
        return $message;
    }

    /**
     * Prepares the message headers.
     *
     * @param Email $email Email instance
     * @return array
     */
    protected function _prepareMessageHeaders($email)
    {
        return $email->getHeaders(['from', 'sender', 'replyTo', 'readReceipt', 'to', 'cc', 'subject', 'returnPath']);
    }

    protected function _setMgObject()
    {
        if (empty($this->config('apiKey'))) {
            throw new MissingCredentialsException(['API Key']);
        }
        if (empty($this->config('apiKey'))) {
            throw new MissingCredentialsException(['sending domain']);
        }
        if (!is_a($this->_mgObject, 'Mailgun')) {
            $client = new Client();
            $this->_mgObject = new Mailgun($this->config('apiKey'), $client);
        }
        if (!$this->config('ssl')) {
            $this->_mgObject->setSslEnabled(false);
        }
        $this->_mgObject->setApiVersion('v3');
    }
}
