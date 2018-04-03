# Mailgun plugin for CakePHP 3

[![Build Status](https://travis-ci.org/narendravaghela/cakephp-mailgun.svg?branch=master)](https://travis-ci.org/narendravaghela/cakephp-mailgun)
[![codecov.io](https://codecov.io/github/narendravaghela/cakephp-mailgun/coverage.svg?branch=master)](https://codecov.io/github/narendravaghela/cakephp-mailgun?branch=master)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Latest Stable Version](https://poser.pugx.org/narendravaghela/cakephp-mailgun/v/stable)](https://packagist.org/packages/narendravaghela/cakephp-mailgun)
[![Total Downloads](https://poser.pugx.org/narendravaghela/cakephp-mailgun/downloads)](https://packagist.org/packages/narendravaghela/cakephp-mailgun)

This plugin provides email delivery using [Mailgun](https://mailgun.com). See this [post](https://medium.com/@narendravaghela/cakephp-send-transactional-emails-using-mailgun-52d25a51a817) for detailed examples.

## Requirements

This plugin has the following requirements:

* CakePHP 3.0.0 or greater.
* PHP 5.4.16 or greater.

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

```
composer require narendravaghela/cakephp-mailgun
```

After installation, [Load the plugin](http://book.cakephp.org/3.0/en/plugins.html#loading-a-plugin)
```php
Plugin::load('MailgunEmail');
```
Or, you can load the plugin using the shell command
```sh
$ bin/cake plugin load MailgunEmail
```

## Setup

Set your Mailgun credentials in `EmailTransport` settings in app.php

```php
'EmailTransport' => [
...
  'mailgun' => [
      'className' => 'MailgunEmail.Mailgun',
      'apiKey' => 'key-123456789123456789', // your api key
      'domain' => 'test.mailgun.org' // your sending domain
  ]
]
```

If you face an SSL certificate error, please follow below steps:

1. Open http://curl.haxx.se/ca/cacert.pem  
2. Copy the entire page and save it as a "cacert.pem"  
3. Open your php.ini file and insert or update the following line: curl.cainfo = "[pathtofile]\cacert.pem"  

And create new delivery profile for mailgun in `Email` settings.

```php
'Email' => [
    'default' => [
        'transport' => 'default',
        'from' => 'you@localhost',
        //'charset' => 'utf-8',
        //'headerCharset' => 'utf-8',
    ],
    'mailgun' => [
        'transport' => 'mailgun'
    ]
]
```

## Usage

You can now simply use the CakePHP `Email` to send an email via Mailgun.

```php
$email = new Email('mailgun');
$result = $email->from(['foo@example.com' => 'Example Site'])
  ->to('bar@example.com')
  ->subject('Welcome to CakePHP')
  ->template('welcome')
  ->viewVars(['foo' => 'Bar'])
  ->emailFormat('both')
  ->addHeaders(['o:tag' => 'testing'])
  ->addHeaders(['o:deliverytime' => strtotime('+1 Min')])
  ->addHeaders(['v:my-custom-data' => json_encode(['foo' => 'bar'])])
  ->readReceipt('admin@example.com')
  ->returnPath('bounce@example.com')
  ->attachments([
      'cake_icon1.png' => Configure::read('App.imageBaseUrl') . 'cake.icon.png',
      'cake_icon2.png' => ['file' => Configure::read('App.imageBaseUrl') . 'cake.icon.png'],
      WWW_ROOT . 'favicon.ico'
  ])
  ->send('Your email content here');
```

That is it.

## Reporting Issues

If you have a problem with this plugin or any bug, please open an issue on [GitHub](https://github.com/narendravaghela/cakephp-mailgun/issues).
