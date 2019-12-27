<?php
declare(strict_types=1);

namespace Mailgun\Test\TestCase\Mailer;

use Cake\TestSuite\TestCase;
use DateInterval;
use DateTime;
use Mailgun\Mailer\MailgunMailer;
use Mailgun\Plugin;

class MailgunTraitTest extends TestCase
{
    /**
     * @var MailgunMailer
     */
    public $TestMailer;

    public function setUp(): void
    {
        parent::setUp();

        $this->TestMailer = new MailgunMailer();
    }

    public function testDeliverBy(): void
    {
        $time = new \DateTime();
        $result = $this->TestMailer->deliverBy($time);
        $this->assertInstanceOf('Cake\Mailer\Mailer', $result);

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Deliver-By']);
        $expected = $time->format(Plugin::TIMEFORMAT);

        $this->assertEquals($expected, $headers['X-Mailgun-Deliver-By']);
    }

    public function testDeliverByException(): void
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');
        $this->expectExceptionMessage('Delivery date can only be max of 3 days in the future.');

        $time = (new DateTime())->add(new DateInterval('P4D'));
        $this->TestMailer->deliverBy($time);
    }

    public function testEnableDkimFalse(): void
    {
        $this->TestMailer->enableDkim(false);

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Dkim']);
        $expected = 'no';

        $this->assertEquals($expected, $headers['X-Mailgun-Dkim']);
    }

    public function testEnableDkimTrue(): void
    {
        $this->TestMailer->enableDkim();

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Dkim']);
        $expected = 'yes';

        $this->assertEquals($expected, $headers['X-Mailgun-Dkim']);
    }

    public function testMailgunVars(): void
    {
        $vars = [
            'var1' => true,
            'var2' => 'string',
        ];

        $this->TestMailer->setMailgunVars($vars);

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Variables']);
        $this->assertEquals($vars, $headers['X-Mailgun-Variables']);
    }

    public function testRecipientVars(): void
    {
        $vars = [
            'email@example.com' => [
                'var1' => true,
                'var2' => 'string',
            ],
        ];

        $this->TestMailer->setRecipientVars($vars);

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Recipient-Variables']);
        $expected = json_encode($vars);
        $this->assertEquals($expected, $headers['X-Mailgun-Recipient-Variables']);
    }

    public function testRequireTlsFalse(): void
    {
        $this->TestMailer->requireTls();

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Require-TLS']);
        $expected = 'false';

        $this->assertEquals($expected, $headers['X-Mailgun-Require-TLS']);
    }

    public function testRequireTlsTrue(): void
    {
        $this->TestMailer->requireTls(true);

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Require-TLS']);
        $expected = 'true';

        $this->assertEquals($expected, $headers['X-Mailgun-Require-TLS']);
    }

    public function testSkipVerificationFalse(): void
    {
        $this->TestMailer->skipVerification();

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Skip-Verification']);
        $expected = 'false';

        $this->assertEquals($expected, $headers['X-Mailgun-Skip-Verification']);
    }

    public function testSkipVerificationTrue(): void
    {
        $this->TestMailer->skipVerification(true);

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Skip-Verification']);
        $expected = 'true';

        $this->assertEquals($expected, $headers['X-Mailgun-Skip-Verification']);
    }

    public function testTagsStringMoreThanThree(): void
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');
        $this->expectExceptionMessage('You can only set a max of 3 tags.');

        $this->TestMailer->setTags('tag1,tag2,tag3,tag4');
    }

    public function testTagString(): void
    {
        $this->TestMailer->setTags('tag1,tag2,tag3');
        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Tag']);
        $expected = json_encode(['tag1', 'tag2', 'tag3']);

        $this->assertEquals($expected, $headers['X-Mailgun-Tag']);
    }

    public function testTestModeFalse(): void
    {
        $this->TestMailer->testMode(false);

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Drop-Message']);
        $expected = 'no';

        $this->assertEquals($expected, $headers['X-Mailgun-Drop-Message']);
    }

    public function testTestModeTrue(): void
    {
        $this->TestMailer->testMode();

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Drop-Message']);
        $expected = 'yes';

        $this->assertEquals($expected, $headers['X-Mailgun-Drop-Message']);
    }

    public function testTrackClicksFalse(): void
    {
        $this->TestMailer->trackClicks(false);

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Track-Clicks']);
        $expected = 'no';

        $this->assertEquals($expected, $headers['X-Mailgun-Track-Clicks']);
    }

    public function testTrackClicksHtmlOnly(): void
    {
        $this->TestMailer->trackClicks();

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Track-Clicks']);
        $expected = 'htmlonly';

        $this->assertEquals($expected, $headers['X-Mailgun-Track-Clicks']);
    }

    public function testTrackClicksTrue(): void
    {
        $this->TestMailer->trackClicks(true);

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Track-Clicks']);
        $expected = 'yes';

        $this->assertEquals($expected, $headers['X-Mailgun-Track-Clicks']);
    }

    public function testTrackFalse(): void
    {
        $this->TestMailer->enableTracking(false);

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Track']);
        $expected = 'no';

        $this->assertEquals($expected, $headers['X-Mailgun-Track']);
    }

    public function testTrackOpenFalse(): void
    {
        $this->TestMailer->trackOpens(false);

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Track-Opens']);
        $expected = 'no';

        $this->assertEquals($expected, $headers['X-Mailgun-Track-Opens']);
    }

    public function testTrackOpensTrue(): void
    {
        $this->TestMailer->trackOpens(true);

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Track-Opens']);
        $expected = 'yes';

        $this->assertEquals($expected, $headers['X-Mailgun-Track-Opens']);
    }

    public function testTrackTrue(): void
    {
        $this->TestMailer->enableTracking();

        $headers = $this->TestMailer->getMessage()->getHeaders(['X-Mailgun-Track']);
        $expected = 'yes';

        $this->assertEquals($expected, $headers['X-Mailgun-Track']);
    }
}
