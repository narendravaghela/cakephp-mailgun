<?php
declare(strict_types=1);

namespace Mailgun\Test\TestCase\Mailer;

use Cake\Core\Configure;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;
use DateInterval;
use DateTime;
use Mailgun\Mailer\MailgunEmail;
use Mailgun\Plugin;

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

    public function testTagsArray(): void
    {
        $tags = ['tag1', 'tag2', 'tag3'];
        $this->Email->setTags($tags);
        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Tag']);
        $this->assertEquals(json_encode($tags), $headers['X-Mailgun-Tag']);
    }

    public function testTagsArrayMoreThanThree(): void
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');
        $this->expectExceptionMessage('You can only set a max of 3 tags.');

        $this->Email->setTags(['tag1', 'tag2', 'tag3', 'tag4']);
    }

    public function testTagString(): void
    {
        $this->Email->setTags('tag1,tag2,tag3');
        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Tag']);
        $expected = json_encode(['tag1', 'tag2', 'tag3']);

        $this->assertEquals($expected, $headers['X-Mailgun-Tag']);
    }

    public function testTagsStringMoreThanThree(): void
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');
        $this->expectExceptionMessage('You can only set a max of 3 tags.');

        $this->Email->setTags('tag1,tag2,tag3,tag4');
    }

    public function testEnableDkimTrue(): void
    {
        $this->Email->enableDkim();

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Dkim']);
        $expected = 'yes';

        $this->assertEquals($expected, $headers['X-Mailgun-Dkim']);
    }

    public function testEnableDkimFalse(): void
    {
        $this->Email->enableDkim(false);

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Dkim']);
        $expected = 'no';

        $this->assertEquals($expected, $headers['X-Mailgun-Dkim']);
    }

    public function testDeliverBy(): void
    {
        $time = new DateTime();
        $this->Email->deliverBy($time);

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Deliver-By']);
        $expected = $time->format(Plugin::TIMEFORMAT);

        $this->assertEquals($expected, $headers['X-Mailgun-Deliver-By']);
    }

    public function testDeliverByFourDaysInFuture(): void
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');
        $this->expectExceptionMessage('Delivery date can only be max of 3 days in the future.');

        $time = (new DateTime())->add(new DateInterval('P4D'));
        $this->Email->deliverBy($time);
    }

    public function testTestModeTrue(): void
    {
        $this->Email->testMode();

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Drop-Message']);
        $expected = 'yes';

        $this->assertEquals($expected, $headers['X-Mailgun-Drop-Message']);
    }

    public function testTestModeFalse(): void
    {
        $this->Email->testMode(false);

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Drop-Message']);
        $expected = 'no';

        $this->assertEquals($expected, $headers['X-Mailgun-Drop-Message']);
    }

    public function testTrackTrue(): void
    {
        $this->Email->enableTracking();

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Track']);
        $expected = 'yes';

        $this->assertEquals($expected, $headers['X-Mailgun-Track']);
    }

    public function testTrackFalse(): void
    {
        $this->Email->enableTracking(false);

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Track']);
        $expected = 'no';

        $this->assertEquals($expected, $headers['X-Mailgun-Track']);
    }

    public function testTrackClicksHtmlOnly(): void
    {
        $this->Email->trackClicks();

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Track-Clicks']);
        $expected = 'htmlonly';

        $this->assertEquals($expected, $headers['X-Mailgun-Track-Clicks']);
    }

    public function testTrackClicksTrue(): void
    {
        $this->Email->trackClicks(true);

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Track-Clicks']);
        $expected = 'yes';

        $this->assertEquals($expected, $headers['X-Mailgun-Track-Clicks']);
    }

    public function testTrackClicksFalse(): void
    {
        $this->Email->trackClicks(false);

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Track-Clicks']);
        $expected = 'no';

        $this->assertEquals($expected, $headers['X-Mailgun-Track-Clicks']);
    }

    public function testTrackOpensTrue(): void
    {
        $this->Email->trackOpens(true);

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Track-Opens']);
        $expected = 'yes';

        $this->assertEquals($expected, $headers['X-Mailgun-Track-Opens']);
    }

    public function testTrackOpenFalse(): void
    {
        $this->Email->trackOpens(false);

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Track-Opens']);
        $expected = 'no';

        $this->assertEquals($expected, $headers['X-Mailgun-Track-Opens']);
    }

    public function testRequireTlsFalse(): void
    {
        $this->Email->requireTls();

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Require-TLS']);
        $expected = 'false';

        $this->assertEquals($expected, $headers['X-Mailgun-Require-TLS']);
    }

    public function testRequireTlsTrue(): void
    {
        $this->Email->requireTls(true);

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Require-TLS']);
        $expected = 'true';

        $this->assertEquals($expected, $headers['X-Mailgun-Require-TLS']);
    }

    public function testSkipVerificationFalse(): void
    {
        $this->Email->skipVerification();

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Skip-Verification']);
        $expected = 'false';

        $this->assertEquals($expected, $headers['X-Mailgun-Skip-Verification']);
    }

    public function testSkipVerificationTrue(): void
    {
        $this->Email->skipVerification(true);

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Skip-Verification']);
        $expected = 'true';

        $this->assertEquals($expected, $headers['X-Mailgun-Skip-Verification']);
    }

    public function testRecipientVars(): void
    {
        $vars = [
            'email@example.com' => [
                'var1' => true,
                'var2' => 'string',
            ],
        ];

        $this->Email->setRecipientVars($vars);

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Recipient-Variables']);
        $expected = json_encode($vars);
        $this->assertEquals($expected, $headers['X-Mailgun-Recipient-Variables']);
    }

    public function testMailgunVars(): void
    {
        $vars = [
            'var1' => true,
            'var2' => 'string',
        ];

        $this->Email->setMailgunVars($vars);

        $headers = $this->Email->getMessage()->getHeaders(['X-Mailgun-Variables']);
        $this->assertEquals($vars, $headers['X-Mailgun-Variables']);
    }
}
