# Upgrade from 4.x to 5.x
The release `5.0.0` is a major update of `4.x` with some breaking changes. If you are upgrading to `5.x`, please follow the read the below points and update your codebase.

## Configuration
First, move your transport configuration from `app.php` to `app_local.php`.

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

## Usage
In `4.x`, MailgunEmail provided convenience methods to change the email. As of CakePHP 4.0 using `Cake\Mailer\Email` is deprecated, so we've added a new MailgunTrait that works on `Cake\Mailer\Email` and `Cake\Mailer\Mailer`. And a default `Mailgun\Mailer\MailgunMailer` to support upgrading CakePHP 3.x code to CakePHP 4.x code.

- [Custom Headers](https://github.com/narendravaghela/cakephp-mailgun/tree/master/docs#custom-headers)
- [Additional Options](https://github.com/narendravaghela/cakephp-mailgun/tree/master/docs#additional-options)
- And more...

Please use appropriate method to set headers, options, recipient variables, etc.

## Problem?
If you face any issue or errors while upgrading, please open an issue [here](https://github.com/narendravaghela/cakephp-mailgun/issues).
