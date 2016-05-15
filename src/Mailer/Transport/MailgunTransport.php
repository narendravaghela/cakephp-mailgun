<?php

/**
 * Mailgun Transport
 *
 * Copyright 2016, Narendra Vaghela
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author		Narendra Vaghela
 * @copyright           Copyright (c) Narendra Vaghela (http://www.narendravaghela.com)
 * @since		1.0.0
 * @license		http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace MailgunEmail\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;
use Mailgun\Mailgun;
use Http\Adapter\Guzzle6\Client;

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
     * @var array
     */
    protected $_lastResponse = [];

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
        $this->_lastResponse = [];
        $response = $this->_mgObject->sendMessage($this->config('domain'), $this->_mailgunParams);
        return $response;
    }

    protected function _buildMailgunMessage($email)
    {
        $message = [];
        $this->_headers = $this->_prepareMessageHeaders($email);
        $message['from'] = $this->_headers['From'];
        $message['to'] = $this->_headers['To'];
        $message['subject'] = $this->_headers['Subject'];
        $message['html'] = $email->message();
        return $message;
    }

    /**
     * Prepares the message headers.
     *
     * @param \Cake\Mailer\Email $email Email instance
     * @return array
     */
    protected function _prepareMessageHeaders($email)
    {
        return $email->getHeaders(['from', 'sender', 'replyTo', 'readReceipt', 'to', 'cc', 'subject', 'returnPath']);
    }

    protected function _setMgObject()
    {
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
