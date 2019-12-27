<?php
declare(strict_types=1);

/**
 * Mailgun Plugin for CakePHP 4
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
 * @deprecated 5.0.0 This class will be removed in CakePHP 5.0 and Mailgun 6.0. Use MailgunTrait on your custom Mailer class
 */

namespace Mailgun\Mailer;

use Cake\Mailer\Email as CoreEmail;

class MailgunEmail extends CoreEmail
{
    use MailgunTrait;

    /**
     * Constructor
     *
     * @param array $config Configuration options.
     */
    public function __construct($config = null)
    {
        parent::__construct($config);

        $this->setProfile(['transport' => 'mailgun']);
    }
}
