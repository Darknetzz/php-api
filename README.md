# php_api
A simple but customizable API written in PHP.

## Installing

You can start using this on your webserver by simply cloning this repository to your webroot folder:
```bash
$ cd /var/www/html
$ git clone https://github.com/Darknetzz/php_api.git
```

You have now installed the API to https://<YOUR_SERVER>/php_api

## Configuring

All you need to do now is configure it to your likings, in order to do this, you need to take a look at the included files.

### api_settings.php

### API Keys

> :warning: **Warning**: Please do not reuse API keys found anywhere! Generate your own keys at [roste.org](https://roste.org/rand/#rsgen).

The first thing you should do is create an API key you can use.

Open up `api_keys.php` and add your generated and secure key in the file like so:
````php
addAPIKey(
    name: "MasterKey",
    key: "nrTv7xL6qyoOhWH7VBoh0Fs9JwChcoBNLhj1Us7l7zQKENBT0N8cZwDwB48YPdRL",
    options: [
        "allowedEndpoints" => ["testEndpoint", "anotherEndpoint"], 
        "noTimeOut"        => true           , 
        "notify"           => false          , 
    ]
);
````

#### Available option parameters
| NAME                | DEFAULT VALUE | DESCRIPTION                                                                                             |
| :------------------ | :------------ | :------------------------------------------------------------------------------------------------------ |
| allowedEndpoints    | ["*"]         | Endpoints this key has access to. If there is a * in the array the key will be unrestricted.            |
| disallowedEndpoints | []            | Endpoints this key specifically doesn't have access to, will override allowedEndpoints                  |
| noTimeOut           | false         | Specify if this key can bypass the timeout                                                              |
| timeout             | COOLDOWN_TIME | Time in seconds this key has to wait between API calls (COOLDOWN_TIME is specified in api_settings.php) |
| notify              | true          | Whether or not to notify the owner of this API when an endpoint is used.                                |

### Creating your first endpoint
To create an endpoint that you can talk to, open up the file `api_endpoints.php`.

Here is an example of an endpoint that returns the user's IP address.
````php
function api_ip() {
    $ip = (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
    return ["ip" => $ip];
}
````

### Aliases
Take a look at `api_aliases.php`, it should be quite self explanatory.

## Using the API
To query the API, you can use tools like cURL.

````bash
`$ curl -X GET -H "Content-Type: application/json" https://<YOUR_SERVER>/php_api/?apikey=nrTv7xL6qyoOhWH7VBoh0Fs9JwChcoBNLhj1Us7l7zQKENBT0N8cZwDwB48YPdRL&endpoint=ip
````

## File summary
| File | Description |
| --- | --- |
| api_endpoints.php | This is where you specify your endpoints. |
| api_settings.php  | You can change some default settings here.|
| api_keys.php      | Put your securely generated API keys here. Pro tip: [Use a generator!](https://server.roste.org/rand/#rsgen) |
| api_aliases.php   | This is where you specify aliases for your endpoints. That means an endpoint can have several names. |
| api_base.php      | The most fundamental functions. Don't change this file unless you know what you are doing. |
