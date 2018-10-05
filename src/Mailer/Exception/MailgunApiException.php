<?php
/**
 * Mailgun Plugin for CakePHP 3
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.md
 * Redistributions of files must retain the above copyright notice.
 *
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      https://github.com/narendravaghela/cakephp-mailgun
 * @since     1.0.0
 */

namespace Mailgun\Mailer\Exception;

use Cake\Core\Exception\Exception;

/**
 * Mailgun Api exception
 *
 * - used when an api key cannot be found.
 */
class MailgunApiException extends Exception
{

    /**
     * {@inheritDoc}
     */
    protected $_messageTemplate = '%s';
}
