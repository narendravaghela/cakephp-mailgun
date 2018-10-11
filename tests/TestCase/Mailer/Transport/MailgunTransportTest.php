<?php
/**
 * Mailgun Plugin for CakePHP 3
 * Copyright (c) Narendra Vaghela (http://www.narendravaghela.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.md
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Narendra Vaghela (http://www.narendravaghela.com)
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      https://github.com/narendravaghela/cakephp-mailgun
 * @since     3.0.0
 */

namespace Mailgun\Test\TestCase\Mailer\Transport;

use Cake\Core\Configure;
use Cake\Mailer\Email;
use Cake\TestSuite\TestCase;

class MailgunTransportTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();

        Configure::write('DebugKit.panels', ['DebugKit.Mail' => false]);
    }

    public function testSendEmail()
    {
        $res = $this->_sendEmail();
        $reqData = $res['reqData'];
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;

        $this->assertNotEmpty($reqData);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="subject"', $reqDataString);
        $this->assertTextContains('Email from CakePHP Mailgun plugin', $reqDataString);
        $this->assertStringEndsWith("--$boundary--", rtrim($reqDataString));
    }

    public function testSendBatchEmail()
    {
        $this->_setEmailConfig();

        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);
        $res = $email->setFrom(['from@example.com' => 'CakePHP Mailgun Email'])
            ->setTo('to@example.com')
            ->addTo(['bar@example.com', 'john@example.com' => 'John'])
            ->setEmailFormat('both')
            ->setSubject('Email from CakePHP Mailgun plugin')
            ->send('Hello there, <br> This is an email from CakePHP Mailgun Email plugin.');

        $emailInstance = $email->getTransport();
        $requestData = $emailInstance->getRequestData();
        $this->assertEmpty((string)$requestData);

        $reqData = $res['reqData'];
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;

        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="from"', $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="to"', $reqDataString);
        $this->assertTextContains('CakePHP Mailgun Email <from@example.com>', $reqDataString);
        $this->assertTextContains('to@example.com <to@example.com>', $reqDataString);
        $this->assertTextContains('bar@example.com <bar@example.com>', $reqDataString);
        $this->assertTextContains('John <john@example.com>', $reqDataString);
        $this->assertStringEndsWith("--$boundary--", rtrim($reqDataString));
    }

    public function testAdditionalEmailAddresses()
    {
        $this->_setEmailConfig();

        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);
        $res = $email->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->addCC(['ccbar@example.com', 'ccjohn@example.com' => 'John'])
            ->addBcc(['bccbar@example.com', 'bccjohn@example.com' => 'John'])
            ->setEmailFormat('both')
            ->setSubject('Email from CakePHP Mailgun plugin')
            ->send('Hello there, <br> This is an email from CakePHP Mailgun Email plugin.');

        $reqData = $res['reqData'];
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;

        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="from"', $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="to"', $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="cc"', $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="bcc"', $reqDataString);
        $this->assertTextContains('from@example.com <from@example.com>', $reqDataString);
        $this->assertTextContains('to@example.com <to@example.com>', $reqDataString);
        $this->assertTextContains('ccbar@example.com <ccbar@example.com>', $reqDataString);
        $this->assertTextContains('John <ccjohn@example.com>', $reqDataString);
        $this->assertTextContains('bccbar@example.com <bccbar@example.com>', $reqDataString);
        $this->assertTextContains('John <bccjohn@example.com>', $reqDataString);
    }

    public function testAttachments()
    {
        $this->_setEmailConfig();
        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);
        $res = $email->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->setAttachments([
                'logo.png' => ['file' => TESTS . DS . 'assets' . DS . 'logo.png', 'contentId' => 'logo.png'],
                'cake.power.gif' => ['file' => TESTS . DS . 'assets' . DS . 'cake.power.gif']
            ])
            ->setSubject('Email from CakePHP Mailgun plugin')
            ->send('Hello there, <br> This is an email from CakePHP Mailgun Email plugin.');

        $reqData = $res['reqData'];
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;
        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertStringEndsWith("--$boundary--", rtrim($reqDataString));
    }

    public function testRawBinaryAttachments()
    {
        $this->_setEmailConfig();
        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);
        $res = $email->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->setAttachments([
                'myfile.txt' => ['data' => 'c29tZSB0ZXh0', 'mimetype' => 'text/plain'], // c29tZSB0ZXh0 = base64_encode('some text')
            ])
            ->setSubject('Email from CakePHP Mailgun plugin')
            ->send('Hello there, <br> This is an email from CakePHP Mailgun Email plugin.');

        $reqData = $res['reqData'];
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;
        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertStringEndsWith("--$boundary--", rtrim($reqDataString));
    }

    public function testSetOption()
    {
        $this->_setEmailConfig();
        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);

        $emailInstance = $email->getTransport();
        $emailInstance->setOption('testmode', 'yes');
        $emailInstance->setOption('tag', ['newsletter', 'welcome email']);

        $res = $email->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->setSubject('Email from CakePHP Mailgun plugin')
            ->send();

        $reqData = $res['reqData'];
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;

        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="o:testmode"', $reqDataString);
        $this->assertTextContains('yes', $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="o:tag"', $reqDataString);
        $this->assertTextContains('newsletter', $reqDataString);
        $this->assertTextContains('welcome email', $reqDataString);
    }

    public function testInvalidOptionException()
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');

        $this->_setEmailConfig();
        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);

        $emailInstance = $email->getTransport();
        $emailInstance->setOption('foo', 'bar');
    }

    public function testInvalidOptionValueException()
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');

        $this->_setEmailConfig();
        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);

        $emailInstance = $email->getTransport();
        $emailInstance->setOption('testmode', $email);
    }

    public function testSetCustomMessageData()
    {
        $this->_setEmailConfig();
        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);

        $customMessageData = ['foo' => 'bar', 'john' => 'doe'];
        $emailInstance = $email->getTransport();
        $emailInstance->setCustomMessageData('custom-data-array', $customMessageData);
        $emailInstance->setCustomMessageData('my-custom-data', '{"my_message_id": 123}');

        $res = $email->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->setSubject('Email from CakePHP Mailgun plugin')
            ->send();

        $reqData = $res['reqData'];
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;

        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="v:custom-data-array"', $reqDataString);
        $this->assertTextContains(json_encode($customMessageData), $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="v:my-custom-data"', $reqDataString);
        $this->assertTextContains('{"my_message_id": 123}', $reqDataString);
    }

    public function testSetCustomMessageDataException()
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');

        $this->_setEmailConfig();
        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);

        $emailInstance = $email->getTransport();
        $emailInstance->setCustomMessageData('my-custom-data', 'invalid data string');
    }

    public function testSetRecipientVars()
    {
        $this->_setEmailConfig();
        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);

        $recipientData = [
            'foo@example.com' => ['name' => 'Foo Bar'],
            'john@example.com' => ['name' => 'John Doe'],
        ];
        $emailInstance = $email->getTransport();
        $emailInstance->setRecipientVars($recipientData);
        $emailInstance->setRecipientVars(json_encode($recipientData));

        $res = $email->setFrom('from@example.com')
            ->setTo('foo@example.com')
            ->addTo('john@example.com')
            ->setSubject('Email from CakePHP Mailgun plugin')
            ->send();

        $reqData = $res['reqData'];
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;

        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="recipient-variables"', $reqDataString);
        $this->assertTextContains(json_encode($recipientData), $reqDataString);
    }

    public function testSetRecipientVarsException()
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');

        $this->_setEmailConfig();
        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);

        $emailInstance = $email->getTransport();
        $emailInstance->setRecipientVars('string data');
    }

    public function testApiExceptions()
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');

        $this->_setBlankApiEmailConfig();
        $this->_sendEmail(false);
    }

    public function testInvalidApiKey()
    {
        $this->_setBlankApiEmailConfig();
        $res = $this->_sendEmail();
        $apiResponse = $res['apiResponse'];

        $this->assertNull($apiResponse);
    }

    public function testInvalidDomainKey()
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');

        $this->_setBlankDomainEmailConfig();
        $this->_sendEmail(false);
    }

    protected function _sendEmail($useDefault = true)
    {
        if ($useDefault) {
            $this->_setEmailConfig();
        }

        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);
        $res = $email->setFrom(['from@example.com' => 'CakePHP Mailgun Email'])
            ->setTo('to@example.com')
            ->setEmailFormat('both')
            ->setSubject('Email from CakePHP Mailgun plugin')
            ->send('Hello there, <br> This is an email from CakePHP Mailgun Email plugin.');

        return $res;
    }

    protected function _setBlankApiEmailConfig()
    {
        Email::dropTransport('mailgun');
        Email::setConfigTransport('mailgun', ['className' => 'Mailgun.Mailgun', 'apiKey' => '', 'domain' => 'xxxxxxx-test.mailgun.org']);
    }

    protected function _setBlankDomainEmailConfig()
    {
        Email::dropTransport('mailgun');
        Email::setConfigTransport('mailgun', ['className' => 'Mailgun.Mailgun', 'apiKey' => 'xxxxxxx-test-xxxxxxx', 'domain' => '']);
    }

    protected function _setBlankEmailConfig()
    {
        Email::dropTransport('mailgun');
        Email::setConfigTransport('mailgun', ['className' => 'Mailgun.Mailgun', 'apiKey' => '', 'domain' => '']);
    }

    protected function _setEmailConfig()
    {
        Email::dropTransport('mailgun');
        Email::setConfigTransport('mailgun', ['className' => 'Mailgun.Mailgun', 'apiKey' => 'xxxxxxx-test-xxxxxxx', 'domain' => 'xxxxxxx-test.mailgun.org']);
    }
}
