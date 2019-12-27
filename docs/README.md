# CakePHP Mailgun Plugin Documentation

## FYI
These documents need updating for CakePHP 4.0 as we now have a Email, Mailer, and Trait available

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

```ssh
composer require narendravaghela/cakephp-mailgun
```

[Load the plugin](https://book.cakephp.org/4.0/en/plugins.html#loading-a-plugin) in your `src/Application.php`'s boostrap() using:
```sh
$ bin/cake plugin load Mailgun
```

## Configuration

Set your Mailgun Api key and domain in `EmailTransport` settings in app.php or app_local.php

```php
'EmailTransport' => [
  'mailgun' => [
       'className' => 'Mailgun.Mailgun',
       'apiEndpoint' => 'https://api.mailgun.net/v3', // optional, api endpoint
       'domain' => 'XXXXXXXXXXXXXXXXXX.mailgun.org', // your domain
       'apiKey' => 'XXXXXXXXXXXXXXXXXX' // your api key
   ]
]
```

> Optional: You can set the API Endpoint as well if you are using non-default url by setting `apiEndpoint`.

And create new delivery profile in `Email` settings.

```php
'Email' => [
    'default' => [
        'transport' => 'default',
        'from' => 'you@localhost',
    ],
    'mailgun' => [
        'transport' => 'mailgun'
    ]
]
```

## Usage

You can now simply use the CakePHP's `Email` to send an email via Mailgun.

```php
$email = new MailgunEmail();
$email->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->addCc('john@example.com')
    ->setSubject('Email from CakePHP Mailgun plugin')
    ->send('Message from CakePHP Mailgun plugin');
```

That is it.

## Advance Use
You can also use more options to customise the email message.

### Custom Headers
You can pass your own headers. It must be prefixed with "X-". Use the default `Email::setHeaders` method like,

```php
$email = new MailgunEmail();
$email->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->setHeaders([
        'X-Custom' => 'headervalue',
        'X-MyHeader' => 'myvalue'
    ])
    ->setSubject('Email from CakePHP Mailgun plugin')
    ->send('Message from CakePHP Mailgun plugin');
```

### Attachments
Set your attachments using `Email::setAttachments` method.

```php
$email = new MailgunEmail();
$email->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->setAttachments([
        'cake_icon1.png' => Configure::read('App.imageBaseUrl') . 'cake.icon.png',
        'cake_icon2.png' => ['file' => Configure::read('App.imageBaseUrl') . 'cake.icon.png', 'contentId' => 'cake.icon.png'],
        'myfile.txt' => ['data' => 'c29tZSB0ZXh0', 'mimetype' => 'text/plain'], // c29tZSB0ZXh0 = base64_encode('some text')
        WWW_ROOT . 'favicon.ico'
    ])
    ->setSubject('Email from CakePHP Mailgun plugin')
    ->send('Message from CakePHP Mailgun plugin');
```

> To send inline attachment, use `contentId` parameter while setting attachment. And then you can reference it in your HTML like `<img src="cid:cake.icon.png">`

### Template
You can use the your CakePHP's layout and template as your email's HTML body.

```php
$email = new MailgunEmail();
$email->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
	->setTo('foo@example.com')
	->setLayout('newsletter') // in src/Template/Layout/Email/html/newsletter.ctp
	->setTemplate('mailgun_email') // in src/Template/Email/html/mailgun_email.ctp
	->send();
```
> Mailgun does not provide template kind of thing :)

### Batch Sending
Mailgun provides an option to send email to a group of recipients through a single API call. Simple, add multiple recipients using `Email::setTo()` like,

```php
$email = new MailgunEmail();
$email->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->addTo(['bar@example.com', 'john@example.com']) // alternate way to add multiple
    ->setSubject('Email from CakePHP Mailgun plugin')
    ->send('Message from CakePHP Mailgun plugin');
```

#### Recipient Variables
In case of sending batch emails, also use Recipient Variables. Otherwise, all recipients’ email addresses will show up in the to field for each recipient. To do so, you need to call `MailgunEmail->setRecipientVars()` method.
This also allows you to replace email content with recipient specific data. E.g. you would like to say recipient's name in the email body.

```php
$recipientVars = [
	'foo@example.com' => ['name' => 'Foo', 'city' => 'London'],
	'bar@example.com' => ['name' => 'Bar', 'city' => 'Peris'],
	'john@example.com' => ['name' => 'John', 'city' => 'Toronto']
];

$email = new MailgunEmail();
$email->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->addTo(['bar@example.com', 'john@example.com'])
    ->setRecipientVars($recipientVars)
    ->setSubject('Hello %recipient.name%, welcome to %recipient.city%!')
    ->send('Message from CakePHP Mailgun plugin');
```
> The keys of recipient variables must be the email address of recipients. Once set, you can use the %recipient.varname% in subject or body.

### Custom Message Data
You can attache some data to message. The data can be used in any webhook events related to the email. Use `setMailgunVars()` method and pass the required data. Read [this](https://documentation.mailgun.com/en/latest/user_manual.html#attaching-data-to-messages) for more details.

```php
$email = new MailgunEmail();
$email->setMailgunVars('my-custom-data', ["my_message_id" => 123])
    ->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->addTo(['bar@example.com', 'john@example.com'])
    ->setSubject('Hello %recipient.name%, welcome to %recipient.city%!')
    ->send('Message from CakePHP Mailgun plugin');
```

> The data must be an `Array`.

### Additional Options
You can also set a few more options in you email request like tagging, delivery time, test mode etc. For this, you need to use the `MailgunEmail->testMode()` method. Read [this](https://documentation.mailgun.com/en/latest/api-sending.html#sending) for detailed information.

#### Tagging
Tags are more in Mailgun's tracking features. You can assign multiple tags to email. Use `MailgunEmail->setTags()` as option name.

```php
$email = new MailgunEmail();
$email->setTags('monthly newsletter');
$email->setTags(['newsletter', 'monthly newsletter']); // if multiple
$email->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->addTo(['bar@example.com', 'john@example.com'])
    ->setSubject('Hello %recipient.name%, welcome to %recipient.city%!')
    ->send('Message from CakePHP Mailgun plugin');
```

#### DKIM signature
You can enable/disable DKIM signature validation. Use `MailgunEmail->enableDkim()` 

```php
$email = new MailgunEmail();
$email->enableDkim();
```
> Pass `true` or `false`. Default `true`.

#### Delivery Time
You can set the desired time of email message delivety. Use `MailgunEmail->deliverBy()`.

```php
$email = new MailgunEmail();
$email->deliverBy(new \DateTime(strtotime('+1 day')));
```

> Note: Messages can be scheduled for a maximum of 3 days in the future as per Mailgun documentation. Pass a valid unix timestamp as a value.

#### Test Mode
Enables sending in test mode. Use `MailgunEmail->testMode()`

```php
$email = new MailgunEmail();
$email->testMode();
```
> Pass `true` or `false`. Default `false`.

#### Tracking Clicks
Enables/Disables click tracking on a per-message basis. Use `MailgunEmail->trackClicks`.

```php
$email = new MailgunEmail();
$email->trackClicks();
```
> Pass `true` or `false`. Default `true`.

#### Tracking Opens
Enables/Disables click tracking on a per-message basis. Use `MailgunEmail->trackOpens`.

```php
$email = new MailgunEmail();
$email->trackClicks();
```
> Pass `true` or `false`. Default `true`.

#### Require TLS
Sets the email sending over TLS connection. Use `MailgunEmail->requireTls()`.

```php
$email = new MailgunEmail();
$email->requireTls(true);
```
> Pass `true` or `false`. Default `false`.

#### Skip Verification
Enables/Disable the hostname and certificate verification to establish TLS connection. Use `MailgunEmail->skipVerification()`

```php
$email = new MailgunEmail();
$email->skipVerification();
```
> Pass `true` or `false`. Default `false`.
