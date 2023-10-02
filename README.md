# php_api
A simple but customizable API written in PHP. You can configure this API to do anything you can do with PHP.

## Prerequisites
- A webserver running PHP. (Recommended version is 8.1 or above, versions from 7.3 and above should work but is untested).
- A good understanding of the PHP language.
- Basic understanding of API / HTTP request handling.

## Installing

You can start using this on your webserver by simply cloning this repository to your webroot folder:
```bash
$ cd /var/www/html
$ git clone https://github.com/Darknetzz/php_api.git
```

You have now installed the API to https://<YOUR_SERVER>/php_api

## Configuring

All you need to do now is configure it to your likings, in order to do this, you need to take a look at the included files.

### File summary
| File | Description |
| --- | --- |
| [api_endpoints.php](#api-endpoints) | This is where you specify your endpoints. |
| [api_settings.php](#api-settings)  | You can change some default settings here.|
| [api_keys.php](#api-keys)      | Put your securely generated API keys here. Pro tip: [Use a generator!](https://server.roste.org/rand/#rsgen) |
| [api_aliases.php](#api-endpoint-aliases)   | This is where you specify aliases for your endpoints. That means an endpoint can have several names. |
| [api_base.php](#api-base)      | The most fundamental functions. Don't change this file unless you know what you are doing. |

### API Settings

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
| log_write           | true          | Whether or not to write requests with this API key to a log file of your choosing.                      |

### API Endpoints
To create an endpoint that you can talk to, open up the file `api_endpoints.php`.

Here is an example of an endpoint that returns the user's IP address.
````php
function api_ip() {
    $ip = (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
    return ["ip" => $ip];
}
````

### API Endpoint Aliases
Take a look at `api_aliases.php`, it should be quite self explanatory.

### API Base
This file is the fundament for this API. You should not have to edit this file to customize the API sufficiently.
But if you must, here are the functions and their purpose:
| FUNCTION                                 | PURPOSE                                     | PARAMETERS                                                    |
| err                                      | This function will return an error          | string $text<br>int $statusCode = 500<br>bool $fatal = true   |
| var_assert                               | Will assert variable (with optional value)  | mixed &$var<br>mixed $assertVal = false<br>bool $lazy = false |
| userIP                                   | Should return the user's IP.                |                                                               |

## Using the API
To query the API, you can use tools like cURL.

````bash
`$ curl -X GET -H "Content-Type: application/json" https://<YOUR_SERVER>/php_api/?apikey=nrTv7xL6qyoOhWH7VBoh0Fs9JwChcoBNLhj1Us7l7zQKENBT0N8cZwDwB48YPdRL&endpoint=ip
````

## What's next?
I work on this project from time to time with no definitive goal in mind, except for improving what already is. For me this is strictly recreational, although I would happily accept contributions on this project.
