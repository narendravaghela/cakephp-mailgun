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

    public function testApiExceptions()
    {
        $this->expectException('Mailgun\Mailer\Exception\MailgunApiException');

        $this->_sendEmail(false);
    }

    public function testInvalidApiKey()
    {
        $res = $this->_sendEmail();
        $apiResponse = $res['apiResponse'];

        $this->assertNull($apiResponse);
    }

    protected function _sendEmail($useValidConfig = true)
    {
        if ($useValidConfig) {
            $this->_setEmailConfig();
        } else {
            $this->_setBlankEmailConfig();
        }

        $email = new Email();
        $email->setProfile(['transport' => 'mailgun']);
        $res = $email->setFrom(['from@example.com' => 'CakePHP Mailgun Email'])
            ->setSender(['from@example.com' => 'CakePHP Mailgun Email'])
            ->setTo('to@example.com')
            ->setEmailFormat('both')
            ->setSubject('Email from CakePHP Mailgun plugin')
            ->send('Hello there, <br> This is an email from CakePHP Mailgun Email plugin.');

        return $res;
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
