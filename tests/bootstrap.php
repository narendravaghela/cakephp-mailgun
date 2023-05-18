<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for Mailgun.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */

use Cake\Mailer\TransportFactory;

$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new Exception("Cannot find the root of the application, unable to run tests");
};
$root = $findRoot(__FILE__);
unset($findRoot);

chdir($root);

define('TESTS', dirname(__DIR__) . DS . 'tests');

if (file_exists($root . '/config/bootstrap.php')) {
    require $root . '/config/bootstrap.php';

    return;
}
require $root . '/vendor/cakephp/cakephp/tests/bootstrap.php';

TransportFactory::setConfig('mailgun', [
    'className' => 'Mailgun.Mailgun',
    'apiEndpoint' => 'https://api.mailgun.net/v3', // optional, api endpoint
    'domain' => 'XXXXXXXXXXXXXXXXXX.mailgun.org', // your domain
    'apiKey' => 'XXXXXXXXXXXXXXXXXX'
]);