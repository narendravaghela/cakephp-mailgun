# Upgrade from 1.x to 3.x
The release `3.0.0` is a major rewrite of `1.x` with some breaking changes. If you are upgrading to `3.x`, please follow the read the below points and update your codebase.

## Configuration
First, update your transport configuration in `app.php`, and use `Mailgun.Mailgun`.

```php
'EmailTransport' => [
	...
	'mailgun' => [
		'className' => 'Mailgun.Mailgun',
		'apiEndpoint' => 'https://api.mailgun.net/v3', // optional, api endpoint
		'domain' => 'XXXXXXXXXXXXXXXXXX.mailgun.org', // your domain
		'apiKey' => 'XXXXXXXXXXXXXXXXXX' // your api key
	]
]
```
> In 1.x, the className was `MailgunEmail.Mailgun`. Additionaly, you can change the API url as well, leave blank to use the default api endpoint.

#### Load Plugin
Change the plugin name in your `Plugin::load()`.
```php
Plugin::load(‘Mailgun');
```
> In `1.x`, the plugin name was `MailgunEmail`.

## Usage
In `1.x`, for setting custom headers and options, the `addHeaders()` was used.  In `3.x`, we have separate functions to do all these jobs.
- [Custom Headers](https://github.com/narendravaghela/cakephp-mailgun#custom-headers)
- [Additional Options](https://github.com/narendravaghela/cakephp-mailgun#additional-options)
- And more...

Please use appropriate method to set headers, options, recipient variables, etc.

## Problem?
If you face any issue or errors while upgrading, please open an issue [here](https://github.com/narendravaghela/cakephp-mailgun/issues).
