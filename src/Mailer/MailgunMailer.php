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
 * @since     5.0.0
 */

namespace Mailgun\Mailer;

use Cake\Mailer\Mailer;

class MailgunMailer extends Mailer
{
    use MailgunTrait;
}
