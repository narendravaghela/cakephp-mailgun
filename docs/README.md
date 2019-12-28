# CakePHP Mailgun Plugin Documentation
## Version Map
See [Version Map](https://github.com/narendravaghela/cakephp-mailgun/wiki)

## Installation
You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

```sh
$ composer require narendravaghela/cakephp-mailgun
```

[Load the plugin](https://book.cakephp.org/4.0/en/plugins.html#loading-a-plugin) in your `src/Application.php`'s boostrap() using:
```sh
$ bin/cake plugin load Mailgun
```

## Configuration

Set your Mailgun Api key and domain in `EmailTransport` settings in `app_local.php`

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
This plugin is compromised of 4 separate classes. 

`Mailgun\Mailer\MailgunTransport` Transport that converts the CakePHP message and sends it via the Mailgun API.

`Mailgun\Mailer\MailgunTrait` Trait that adds convenience methods for setting additional options that you can use in your custom mailer.

`Mailgun\Mailer\MailgunMailer` Mailer that adds the MailgunTrait to a `Cake\Mailer\Mailer` class. You can either extend this class or use the MailgunTrait.

`Mailgun\Mailer\MailgunEmail` **DEPRECATED** Email that adds the MailgunTrait to a `Cake\Mailer\Email` class. 

### Basic Usage
Once you've configured your transport you can begin sending emails by calling `Cake\Mailer\Mailer::setTransport('mailgun')` or use the provided MailgunEmail or MailgunMailer. To set additional options when using Mailgun you may manually set the headers with `Cake\Mailer\Mailer::addHeaders(['X-Mailgun-Tag' => ['welcome', 'newuser'])`

### Advanced Usage
If you have a custom `Cake\Mailer\Mailer` and you'd like convenience methods such as setTestMode(), deliveryBy(), etc add `use Mailgun\Mailer\MailgunTrait;` to the top of your class. Don't forget to update your Mailer's transport to use `'mailgun'`

### Additional Reading
[CakePHP Mailer 4.x](https://book.cakephp.org/4/en/core-libraries/email.html)

[Mailgun User Manual](https://documentation.mailgun.com/en/latest/user_manual.html)

## Documentation

### Custom Headers
You can pass your own headers. It must be prefixed with "X-". Use the default `MailgunMailer::addHeaders` method like,

```php
$email = new MailgunMailer();
$email->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->addHeaders([
        'X-Custom' => 'headervalue',
        'X-MyHeader' => 'myvalue'
    ])
    ->setSubject('Email from CakePHP Mailgun plugin')
    ->deliver('Message from CakePHP Mailgun plugin');
```

### Attachments
Set your attachments using `MailgunMailer::setAttachments` method. Mailgun API supports a max of 25MBs of attachments per message.

```php
$email = new MailgunMailer();
$email->setFrom(['you@example.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->setAttachments([
        'cake_icon1.png' => Configure::read('App.imageBaseUrl') . 'cake.icon.png',
        'cake_icon2.png' => ['file' => Configure::read('App.imageBaseUrl') . 'cake.icon.png', 'contentId' => 'cake.icon.png'],
        'myfile.txt' => ['data' => 'c29tZSB0ZXh0', 'mimetype' => 'text/plain'], // c29tZSB0ZXh0 = base64_encode('some text')
        WWW_ROOT . 'favicon.ico'
    ])
    ->setSubject('Email from CakePHP Mailgun plugin')
    ->deliver('Message from CakePHP Mailgun plugin');
```

> To send inline attachment, use `contentId` parameter while setting attachment. And then you can reference it in your HTML like `<img src="cid:cake.icon.png">`

### CakePHP Templates
You can use the your CakePHP's layout and template as your email's HTML body.

```php
$email = new MailgunMailer();
$email->setFrom(['you@example.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->viewBuilder()
        ->setLayout('newsletter') // in src/Template/Layout/Email/html/newsletter.ctp
        ->setTemplate('mailgun_email'); // in src/Template/Email/html/mailgun_email.ctp

$email->send();
```

### Batch Sending
Mailgun provides an option to send email to a group of recipients through a single API call. Simple, add multiple recipients using `Email::setTo()` like,

```php
$email = new MailgunMailer();
$email->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->addTo(['bar@example.com', 'john@example.com']) // alternate way to add multiple
    ->setSubject('Email from CakePHP Mailgun plugin')
    ->deliver('Message from CakePHP Mailgun plugin');
```

#### Recipient Variables
In case of sending batch emails, also use Recipient Variables. Otherwise, all recipients’ email addresses will show up in the to field for each recipient. To do so, you need to call `MailgunTrait->setRecipientVars()` method.
This also allows you to replace email content with recipient specific data. E.g. you would like to say recipient's name in the email body.

```php
$recipientVars = [
	'foo@example.com' => ['name' => 'Foo', 'city' => 'London'],
	'bar@example.com' => ['name' => 'Bar', 'city' => 'Peris'],
	'john@example.com' => ['name' => 'John', 'city' => 'Toronto']
];

$email = new MailgunMailer();
$email->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->addTo(['bar@example.com', 'john@example.com'])
    ->setRecipientVars($recipientVars)
    ->setSubject('Hello %recipient.name%, welcome to %recipient.city%!')
    ->deliver('Message from CakePHP Mailgun plugin');
```
> The keys of recipient variables must be the email address of recipients. Once set, you can use the %recipient.varname% in subject or body.

### Custom Message Data
You can attach some data to message. The data can be used in any webhook events related to the email. Use `MailgunTrait->setMailgunVars()` method and pass the required data. Read [this](https://documentation.mailgun.com/en/latest/user_manual.html#attaching-data-to-messages) for more details.

```php
$email = new MailgunMailer();
$email->setMailgunVars('my-custom-data', ["my_message_id" => 123])
    ->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->addTo(['bar@example.com', 'john@example.com'])
    ->setSubject('Hello %recipient.name%, welcome to %recipient.city%!')
    ->deliver('Message from CakePHP Mailgun plugin');
```

> The data must be an `Array`.

### Additional Options
You can also set a few more options in you email request like tagging, delivery time, test mode etc. For this, you need to use the `MailgunTrait->testMode()` method. Read [this](https://documentation.mailgun.com/en/latest/api-sending.html#sending) for detailed information.

#### Tagging
Tags are more in Mailgun's tracking features. You can assign multiple tags to email. Use `MailgunTrait->setTags()` as option name.

```php
$email = new MailgunMailer();
$email->setTags('monthly newsletter');
$email->setTags(['newsletter', 'monthly newsletter']); // if multiple
$email->setFrom(['you@yourdomain.com' => 'CakePHP Mailgun'])
    ->setTo('foo@example.com')
    ->addTo(['bar@example.com', 'john@example.com'])
    ->setSubject('Hello %recipient.name%, welcome to %recipient.city%!')
    ->deliver('Message from CakePHP Mailgun plugin');
```

#### DKIM signature
You can enable/disable DKIM signature validation. Use `MailgunTrait->enableDkim()` 

```php
$email = new MailgunMailer();
$email->enableDkim(true);
```
> Pass `true` or `false`. Default `true`.

#### Delivery Time
You can set the desired time of email message delivety. Use `MailgunTrait->deliverBy()`.

```php
$email = new MailgunMailer();
$email->deliverBy(new \DateTime(strtotime('+1 day')));
```

> Note: Messages can be scheduled for a maximum of 3 days in the future as per Mailgun documentation. Pass a valid unix timestamp as a value.

#### Test Mode
Enables sending in test mode. Use `MailgunTrait->testMode()`

```php
$email = new MailgunMailer();
$email->testMode(true);
```
> Pass `true` or `false`. Default `false`.

#### Tracking Clicks
Enables/Disables click tracking on a per-message basis. Use `MailgunTrait->trackClicks`.

```php
$email = new MailgunMailer();
$email->trackClicks(false);
```
> Pass `true` or `false` or `null` to track clicks on HTML only email. Default `true`.

#### Tracking Opens
Enables/Disables click tracking on a per-message basis. Use `MailgunTrait->trackOpens`.

```php
$email = new MailgunMailer();
$email->trackOpens(false);
```
> Pass `true` or `false`. Default `true`.

#### Require TLS
Sets the email sending over TLS connection. Use `MailgunTrait->requireTls()`.

```php
$email = new MailgunMailerl();
$email->requireTls(true);
```
> Pass `true` or `false`. Default `false`.

#### Skip Verification
Enables/Disable the hostname and certificate verification to establish TLS connection. Use `MailgunTrait->skipVerification()`

```php
$email = new MailgunMailer();
$email->skipVerification(true);
```
> Pass `true` or `false`. Default `false`.

## Problem?
If you face any issue or errors while upgrading, please open an issue [here](https://github.com/narendravaghela/cakephp-mailgun/issues).
