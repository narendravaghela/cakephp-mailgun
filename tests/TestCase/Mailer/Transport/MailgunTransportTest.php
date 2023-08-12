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
 * @since     3.0.0
 */

namespace Mailgun\Test\TestCase\Mailer\Transport;

use Cake\Core\Configure;
use Cake\Http\Client\Response;
use Cake\Mailer\Message;
use Cake\TestSuite\TestCase;
use Mailgun\Mailer\Transport\MailgunTransport;
use PHPUnit\Framework\MockObject\MockObject;

class MailgunTransportTest extends TestCase
{
    public MailgunTransport|MockObject $MailgunTransport;

    public function setUp(): void
    {
        parent::setUp();

        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail'])->getMock();

        Configure::write('DebugKit.panels', ['DebugKit.Mail' => false]);
    }

    public function testAdditionalEmailAddresses()
    {
        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail', '_reset'])->getMock();
        $this->MailgunTransport->expects($this->once())->method('_reset');
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();

        $message->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->addCC(['ccbar@example.com', 'ccjohn@example.com' => 'John'])
            ->addBcc(['bccbar@example.com', 'bccjohn@example.com' => 'John'])
            ->setReplyTo(['replyto@example.com' => 'John'])
            ->setSender(['sender@example.com' => 'John'])
            ->setEmailFormat('both')
            ->setSubject('Email from CakePHP Mailgun plugin');
        $this->MailgunTransport->send($message);

        $reqData = $this->MailgunTransport->getRequestData();
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;

        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="from"', $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="to"', $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="cc"', $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="bcc"', $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="h:Reply-To"', $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="h:Sender"', $reqDataString);
        $this->assertTextContains('from@example.com <from@example.com>', $reqDataString);
        $this->assertTextContains('to@example.com <to@example.com>', $reqDataString);
        $this->assertTextContains('ccbar@example.com <ccbar@example.com>', $reqDataString);
        $this->assertTextContains('John <ccjohn@example.com>', $reqDataString);
        $this->assertTextContains('bccbar@example.com <bccbar@example.com>', $reqDataString);
        $this->assertTextContains('John <bccjohn@example.com>', $reqDataString);
        $this->assertTextContains('John <replyto@example.com>', $reqDataString);
        $this->assertTextContains('John <sender@example.com>', $reqDataString);
    }

    public function testAttachments()
    {
        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail', '_reset'])->getMock();
        $this->MailgunTransport->expects($this->once())->method('_reset');
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();
        $message->setFrom('test@example.com')
            ->setTo('to@example.com')
            ->setAttachments([
                'logo.png' => ['file' => TESTS . DS . 'assets' . DS . 'logo.png', 'contentId' => 'logo.png'],
                'cake.power.gif' => ['file' => TESTS . DS . 'assets' . DS . 'cake.power.gif'],
            ])
            ->setSubject('Email from CakePHP Mailgun plugin');
        $this->MailgunTransport->send($message);

        $reqData = $this->MailgunTransport->getRequestData();
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;
        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertStringEndsWith("--$boundary--", rtrim($reqDataString));
    }

    public function testCustomHeaders()
    {
        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail', '_reset'])->getMock();
        $this->MailgunTransport->expects($this->once())->method('_reset');
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();
        $res = $message->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->setHeaders(['h:X-MyHeader' => 'YouGotIt'])
            ->setSubject('Email from CakePHP Mailgun plugin');
        $this->MailgunTransport->send($message);

        $reqData = $this->MailgunTransport->getRequestData();
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;

        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="h:X-MyHeader"', $reqDataString);
        $this->assertTextContains('YouGotIt', $reqDataString);
    }

    public function testRawBinaryAttachments()
    {
        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail', '_reset'])->getMock();
        $this->MailgunTransport->expects($this->once())->method('_reset');
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();
        $message->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->setAttachments([
                'myfile.txt' => ['data' => 'c29tZSB0ZXh0', 'mimetype' => 'text/plain'], // c29tZSB0ZXh0 = base64_encode('some text')
            ])
            ->setSubject('Email from CakePHP Mailgun plugin');
        $this->MailgunTransport->send($message);

        $reqData = $this->MailgunTransport->getRequestData();
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;
        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertStringEndsWith("--$boundary--", rtrim($reqDataString));
    }

    public function testSendBatchEmail()
    {
        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail', '_reset'])->getMock();
        $this->MailgunTransport->expects($this->once())->method('_reset');
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();

        $message->setFrom(['from@example.com' => 'CakePHP Mailgun Email'])
            ->setTo('to@example.com')
            ->addTo(['bar@example.com', 'john@example.com' => 'John'])
            ->setEmailFormat('both')
            ->setSubject('Email from CakePHP Mailgun plugin');

        $this->MailgunTransport->send($message);

        $reqData = $this->MailgunTransport->getRequestData();
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

    public function testSendEmail(): void
    {
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();
        $message->setFrom('test@example.com');
        $this->MailgunTransport->send($message);
    }

    public function testSendEmailBcc(): void
    {
        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail', '_reset'])->getMock();
        $this->MailgunTransport->expects($this->once())->method('_reset');
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();
        $message->setFrom(['test@example.com' => 'Test Email'])
            ->setBcc(['cc@example.com' => 'CC Email']);
        $this->MailgunTransport->send($message);
    }

    public function testSendEmailCc(): void
    {
        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail', '_reset'])->getMock();
        $this->MailgunTransport->expects($this->once())->method('_reset');
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();
        $message->setFrom(['test@example.com' => 'Test Email'])
            ->setCc(['cc@example.com' => 'CC Email']);
        $this->MailgunTransport->send($message);
    }

    public function testSendEmailFrom(): void
    {
        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail', '_reset'])->getMock();
        $this->MailgunTransport->expects($this->once())->method('_reset');
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();
        $message->setFrom(['test@example.com' => 'Test Email']);
        $this->MailgunTransport->send($message);
    }

    public function testSendEmailNoApiKey(): void
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');
        $this->expectExceptionMessage('Api Key for Mailgun could not found.');
        $message = new Message();
        $this->MailgunTransport->send($message);
    }

    public function testSendEmailNoDomain(): void
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');
        $this->expectExceptionMessage('Domain for Mailgun could not found.');
        $this->MailgunTransport->setConfig(['apiKey' => '123']);
        $message = new Message();
        $this->MailgunTransport->send($message);
    }

    public function testSendEmailNoFrom(): void
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');
        $this->expectExceptionMessage('Missing from email address.');
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();
        $this->MailgunTransport->send($message);
    }

    public function testSendEmailTransport(): void
    {
        $data = [
            'id' => '<5a6387fa5a0b46c79489c9b998b101c4@Mac-mini.local>',
            'message' => 'Queued. Thank you.',
        ];
        $this->MailgunTransport = new MailgunTransport();
        $mock = $this->getMockBuilder('Cake\Http\Client')->onlyMethods(['post'])->getMock();
        $response = (new Response([], json_encode($data)))->withStatus(200);
        $mock->expects($this->once())->method('post')->willReturn($response);
        $this->MailgunTransport->Client = $mock;
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();
        $message->setFrom('test@example.com');
        $result = $this->MailgunTransport->send($message);
        $this->assertSame($data['id'], $result['apiResponse']['id']);
        $this->assertSame($data['message'], $result['apiResponse']['message']);
    }

    public function testSendEmailTransportFailure(): void
    {
        $this->expectExceptionMessage('Forbidden');
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');
        $this->MailgunTransport = new MailgunTransport();
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();
        $message->setFrom('test@example.com');
        $this->MailgunTransport->send($message);

        $this->assertNull($this->MailgunTransport->getRequestData());
    }

    public function testSetCustomMessageData()
    {
        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail', '_reset'])->getMock();
        $this->MailgunTransport->expects($this->once())->method('_reset');
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();

        $message->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->addHeaders(['X-Mailgun-Variables' => ['my-custom-data' => '{"my_message_id": 123}']])
            ->setSubject('Email from CakePHP Mailgun plugin');
        $this->MailgunTransport->send($message);

        $reqData = $this->MailgunTransport->getRequestData();
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;

        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="v:my-custom-data"', $reqDataString);
        $this->assertTextContains('{"my_message_id": 123}', $reqDataString);
    }

    public function testSetCustomMessageDataArray()
    {
        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail', '_reset'])->getMock();
        $this->MailgunTransport->expects($this->once())->method('_reset');
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();

        $customMessageData = ['foo' => 'bar', 'john' => 'doe'];

        $message->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->addHeaders(['X-Mailgun-Variables' => ['custom-data-array' => $customMessageData]])
            ->setSubject('Email from CakePHP Mailgun plugin');
        $this->MailgunTransport->send($message);

        $reqData = $this->MailgunTransport->getRequestData();
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;

        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="v:custom-data-array"', $reqDataString);
        $this->assertTextContains(json_encode($customMessageData), $reqDataString);
    }

    public function testSetOption()
    {
        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail', '_reset'])->getMock();
        $this->MailgunTransport->expects($this->once())->method('_reset');
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();

        $message->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->setSubject('Email from CakePHP Mailgun plugin')
            ->addHeaders(['X-Mailgun-Drop-Message' => 'yes', 'X-Mailgun-Tag' => json_encode(['newsletter', 'welcome email'])]);
        $this->MailgunTransport->send($message);

        $reqData = $this->MailgunTransport->getRequestData();
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;

        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="o:testmode"', $reqDataString);
        $this->assertTextContains('yes', $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="o:tag[0]"', $reqDataString);
        $this->assertTextContains('newsletter', $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="o:tag[1]"', $reqDataString);
        $this->assertTextContains('welcome email', $reqDataString);
    }

    public function testSetRecipientVars()
    {
        $this->MailgunTransport = $this->getMockBuilder('Mailgun\Mailer\Transport\MailgunTransport')->onlyMethods(['_sendEmail', '_reset'])->getMock();
        $this->MailgunTransport->expects($this->once())->method('_reset');
        $this->MailgunTransport->expects($this->once())->method('_sendEmail')->willReturn([]);
        $this->MailgunTransport->setConfig(['apiKey' => '123', 'domain' => 'example.com']);
        $message = new Message();

        $recipientData = [
            'foo@example.com' => ['name' => 'Foo Bar'],
            'john@example.com' => ['name' => 'John Doe'],
        ];
        $message->addHeaders(['X-Mailgun-Recipient-Variables' => json_encode($recipientData)]);

        $message->setFrom('from@example.com')
            ->setTo('foo@example.com')
            ->addTo('john@example.com')
            ->setSubject('Email from CakePHP Mailgun plugin');
        $this->MailgunTransport->send($message);

        $reqData = $this->MailgunTransport->getRequestData();
        $boundary = $reqData->boundary();
        $reqDataString = (string)$reqData;

        $this->assertNotEmpty($reqDataString);
        $this->assertStringStartsWith("--$boundary", $reqDataString);
        $this->assertTextContains('Content-Disposition: form-data; name="recipient-variables"', $reqDataString);
        $this->assertTextContains(json_encode($recipientData), $reqDataString);
    }
}
