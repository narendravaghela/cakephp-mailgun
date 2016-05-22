<?php

/**
 * MailgunTransportTest file
 *  
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

namespace MailgunEmail\Test\TestCase\Mailer\Transport;

use Cake\Mailer\Email;
use Cake\TestSuite\TestCase;
use MailgunEmail\Mailer\Transport\MailgunTransport;

class MailgunTransportTest extends TestCase
{

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->MailgunTransport = new MailgunTransport();
        $this->validConfig = [
            'apiKey' => 'My-Super-Awesome-API-Key',
            'domain' => 'test.mailgun.org',
            'ssl' => false,
            'isTest' => true
        ];
        $this->invalidConfig = [
            'apiKey' => '',
            'domain' => ''
        ];
    }

    /**
     * Test configuration
     * 
     * @return void
     */
    public function testInvalidConfig()
    {
        $this->setExpectedException('MailgunEmail\Mailer\Exception\MissingCredentialsException');
        $this->MailgunTransport->config($this->invalidConfig);

        $email = new Email();
        $email->transport($this->MailgunTransport);
        $email->from(['sender@test.mailgun.org' => 'Mailgun Test'])
                ->to('test@test.mailgun.org')
                ->subject('This is test subject')
                ->emailFormat('text')
                ->send('Testing Maingun');
    }

    /**
     * Test required fields
     * 
     * @return void
     */
    public function testMissingRequiredFields()
    {
        $this->setExpectedException('BadMethodCallException');
        $this->MailgunTransport->config($this->validConfig);

        $email = new Email();
        $email->transport($this->MailgunTransport);
        $email->to('test@test.mailgun.org')
                ->subject('This is test subject')
                ->emailFormat('text')
                ->send('Testing Maingun');
    }

    /**
     * Test send
     * 
     * @return void
     */
    public function testSend()
    {
        $this->MailgunTransport->config($this->validConfig);

        $email = new Email();
        $email->transport($this->MailgunTransport);
        $result = $email->from('sender@test.mailgun.org')
                ->to('test@test.mailgun.org')
                ->subject('This is test subject')
                ->emailFormat('text')
                ->send('Testing Maingun');
        $this->assertEquals("test.mailgun.org/messages", $result->http_endpoint_url);
    }

    /**
     * Test attachments
     * 
     * @return void
     */
    public function testAttachments()
    {
        $this->MailgunTransport->config($this->validConfig);

        $email = new Email();
        $email->transport($this->MailgunTransport);
        $result = $email->from('sender@test.mailgun.org')
                ->to('test@test.mailgun.org')
                ->subject('This is test subject')
                ->emailFormat('both')
                ->attachments([
                    'cake_icon.png' => TESTS . DS . 'TestAssets' . DS . 'cake.icon.png',
                    'cake.power.gif' => ['file' => TESTS . DS . 'TestAssets' . DS . 'cake.power.gif'],
                ])
                ->send('Testing Maingun');
        $this->assertEquals("test.mailgun.org/messages", $result->http_endpoint_url);
    }
}
