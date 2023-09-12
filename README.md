# php_api
A simple but customizable API written in PHP.

## You can start using this on your webserver:
```
root@ubuntu:~# cd /var/www/html
root@ubuntu:/var/www/html/# git clone https://github.com/Darknetzz/php_api.git
```
And that's it!
All you need to do now is configure it to your likings.

## File summary
| File | Description |
| --- | --- |
| api_endpoints.php | This is where you specify your endpoints. |
| api_settings.php  | You can change some default settings here.|
| api_keys.php      | Put your securely generated API keys here. Pro tip: (Use a generator!)[https://server.roste.org/rand/#rsgen] |
| api_aliases.php   | This is where you specify aliases for your endpoints. That means an endpoint can have several names. |
| api_base.php      | The most fundamental functions. Don't change this file unless you know what you are doing. |
