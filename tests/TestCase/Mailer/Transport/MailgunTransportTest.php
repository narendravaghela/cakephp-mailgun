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
            'apiVersion' => 'v3',
            'ssl' => false,
            'isTest' => true
        ];
        $this->invalidConfig = [
            'apiKey' => '',
            'domain' => ''
        ];
    }

    /**
     * Test missing api key exception
     *
     * @return void
     */
    public function testMissingApiKey()
    {
        $this->expectException('MailgunEmail\Mailer\Exception\MissingCredentialsException');
        $this->MailgunTransport->setConfig([
            'apiKey' => 'My-Super-Awesome-API-Key',
            'domain' => ''
        ]);

        $email = new Email();
        $email->setTransport($this->MailgunTransport);
        $email->setFrom(['sender@test.mailgun.org' => 'Mailgun Test'])
            ->setTo('test@test.mailgun.org')
            ->setSubject('This is test subject')
            ->setEmailFormat('text')
            ->send('Testing Maingun');
    }

    /**
     * Test missing domain exception
     *
     * @return void
     */
    public function testMissingDomain()
    {
        $this->expectException('MailgunEmail\Mailer\Exception\MissingCredentialsException');
        $this->MailgunTransport->setConfig([
            'apiKey' => '',
            'domain' => ''
        ]);

        $email = new Email();
        $email->setTransport($this->MailgunTransport);
        $email->setFrom(['sender@test.mailgun.org' => 'Mailgun Test'])
            ->setTo('test@test.mailgun.org')
            ->setSubject('This is test subject')
            ->setEmailFormat('text')
            ->send('Testing Maingun');
    }

    /**
     * Test required fields
     *
     * @return void
     */
    public function testMissingRequiredFields()
    {
        $this->expectException('BadMethodCallException');
        $this->MailgunTransport->setConfig($this->validConfig);

        $email = new Email();
        $email->setTransport($this->MailgunTransport);
        $email->setTo('test@test.mailgun.org')
            ->setSubject('This is test subject')
            ->setEmailFormat('text')
            ->send('Testing Maingun');
    }

    /**
     * Test send
     *
     * @return void
     */
    public function testSend()
    {
        $this->MailgunTransport->setConfig($this->validConfig);

        $email = new Email();
        $email->setTransport($this->MailgunTransport);
        $result = $email->setFrom('sender@test.mailgun.org')
            ->setTo('test@test.mailgun.org')
            ->setSubject('This is test subject')
            ->setEmailFormat('text')
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
        $mailgunTransport = $this->getMockBuilder('MailgunEmail\Mailer\Transport\MailgunTransport')
            ->setMethods(['_reset'])
            ->getMock();
        $mailgunTransport->setConfig($this->validConfig);

        $email = new Email();
        $email->setTransport($mailgunTransport);
        $result = $email->setFrom('sender@test.mailgun.org')
            ->setTo('test@test.mailgun.org')
            ->setSubject('This is test subject')
            ->setEmailFormat('both')
            ->setAttachments([
                'cake_icon.png' => TESTS . DS . 'TestAssets' . DS . 'cake.icon.png',
                'cake.power.gif' => ['file' => TESTS . DS . 'TestAssets' . DS . 'cake.power.gif', 'contentId' => 'CakePower'],
            ])
            ->send('Testing Maingun');
        $this->assertEquals("test.mailgun.org/messages", $result->http_endpoint_url);

        $method = new \ReflectionMethod($mailgunTransport, '_processAttachments');
        $method->setAccessible(true);

        $attachments = $method->invoke($mailgunTransport);
        $expected = [
            'attachment' => [
                ['filePath' => '@' . TESTS . DS . 'TestAssets' . DS . 'cake.icon.png', 'remoteName' => 'cake_icon.png'],
            ],
            'inline' => [
                ['filePath' => '@' . TESTS . DS . 'TestAssets' . DS . 'cake.power.gif', 'remoteName' => 'CakePower']
            ]
        ];
        $this->assertEquals($expected, $attachments);
    }

    /**
     * Test additional headers
     *
     * @return void
     */
    public function testAdditionalHeaders()
    {
        $mailgunTransport = $this->getMockBuilder('MailgunEmail\Mailer\Transport\MailgunTransport')
            ->setMethods(['_reset'])
            ->getMock();
        $mailgunTransport->setConfig($this->validConfig);

        $email = new Email();
        $email->setTransport($mailgunTransport);
        $result = $email->setFrom('sender@test.mailgun.org')
            ->setTo('test@test.mailgun.org')
            ->setSubject('This is test subject')
            ->addHeaders(['o:tag' => 'testing', 'o:tracking' => 'yes'])
            ->addHeaders(['v:custom-data' => json_encode(['foo' => 'bar'])])
            ->send('Testing Maingun');
        $this->assertEquals("test.mailgun.org/messages", $result->http_endpoint_url);

        $method = new \ReflectionMethod($mailgunTransport, '_getAdditionalEmailHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($mailgunTransport);
        $this->assertArrayHasKey('o:tag', $headers);
        $this->assertArrayHasKey('o:tracking', $headers);
        $this->assertArrayHasKey('v:custom-data', $headers);
    }
}
