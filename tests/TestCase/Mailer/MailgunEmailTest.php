<?php
declare(strict_types=1);

namespace Mailgun\Test\TestCase\Mailer;

use Cake\Core\Configure;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;
use Mailgun\Mailer\MailgunEmail;

class MailgunEmailTest extends TestCase
{
    /**
     * @var MailgunEmail
     */
    public $Email;

    public function setUp(): void
    {
        parent::setUp();

        Configure::write('DebugKit.panels', ['DebugKit.Mail' => false]);
        TransportFactory::drop('mailgun');
        TransportFactory::setConfig('mailgun', ['className' => 'Mailgun.Mailgun', 'apiKey' => 'xxxxxxx-test-xxxxxxx', 'domain' => 'xxxxxxx-test.mailgun.org']);
        $this->Email = new MailgunEmail();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Email);

        parent::tearDown();
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf('Mailgun\Mailer\Transport\MailgunTransport', $this->Email->getTransport());
    }
}
