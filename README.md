# php_api
A simple but customizable API written in PHP. You can configure this API to do anything you can do with PHP.

- [php\_api](#php_api)
  - [☑️ Prerequisites](#️-prerequisites)
  - [Installing](#installing)
  - [⚙️ Configuring](#️-configuring)
    - [📄 File summary](#-file-summary)
    - [⚙️ API Settings](#️-api-settings)
    - [🔑 API Keys](#-api-keys)
      - [API Keys -\> Available option parameters](#api-keys---available-option-parameters)
    - [API Endpoints](#api-endpoints)
      - [Get user IP](#get-user-ip)
      - [Endpoint with parameters](#endpoint-with-parameters)
        - [Example requests with responses:](#example-requests-with-responses)
      - [Generating a string](#generating-a-string)
        - [Example requests with responses:](#example-requests-with-responses-1)
    - [🟰 API Endpoint Aliases](#-api-endpoint-aliases)
    - [🧱 API Base](#-api-base)
  - [🧑‍💻 Using the API](#-using-the-api)
    - [cURL (bash)](#curl-bash)
    - [PHP:](#php)
  - [🙋‍♂️ What's next?](#️-whats-next)

---

## ☑️ Prerequisites
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

## ⚙️ Configuring
All you need to do now is configure it to your likings, in order to do this, you need to take a look at the included files.

### 📄 File summary
| File | Description |
| --- | --- |
| [api_endpoints.php](#api-endpoints) | This is where you specify your endpoints. |
| [api_settings.php](#api-settings)  | You can change some default settings here.|
| [api_keys.php](#api-keys)      | Put your securely generated API keys here. Pro tip: [Use a generator!](https://server.roste.org/rand/#rsgen) |
| [api_aliases.php](#api-endpoint-aliases)   | This is where you specify aliases for your endpoints. That means an endpoint can have several names. |
| [api_base.php](#api-base)      | The most fundamental functions. Don't change this file unless you know what you are doing. |

---

### ⚙️ API Settings
`api_settings.php`

This is where most of the actual configuration is done.
In this file you will see a lot of options:
| CONSTANT            | DESCRIPTION                                                                                         | DEFAULT            |
| :------------------ | :-------------------------------------------------------------------------------------------------- | :----------------- |
| ENABLE_CUSTOM_INDEX | Whether or not to enable a custom index.php if no endpoint or parameters are given.                 | `false`            |
| CUSTOM_INDEX        | If ENABLE_CUSTOM_INDEX is true, the user will be redirected to this page. Can be URL or local file. | `custom_index.php` |

---

### 🔑 API Keys
`api_keys.php`

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

#### API Keys -> Available option parameters
| NAME                | DEFAULT VALUE | DESCRIPTION                                                                                             |
| :------------------ | :------------ | :------------------------------------------------------------------------------------------------------ |
| allowedEndpoints    | ["*"]         | Endpoints this key has access to. If there is a * in the array the key will be unrestricted.            |
| disallowedEndpoints | []            | Endpoints this key specifically doesn't have access to, will override allowedEndpoints                  |
| noTimeOut           | false         | Specify if this key can bypass the timeout                                                              |
| timeout             | COOLDOWN_TIME | Time in seconds this key has to wait between API calls (COOLDOWN_TIME is specified in api_settings.php) |
| notify              | true          | Whether or not to notify the owner of this API when an endpoint is used.                                |
| log_write           | true          | Whether or not to write requests with this API key to a log file of your choosing.                      |

---

### API Endpoints
`api_endpoints.php`

To create an endpoint that you can talk to, open up the file `api_endpoints.php`.
Here are some example endpoints you can configure.

#### Get user IP
Here is an example of an endpoint that returns the user's IP address.
````php
function api_ip() {
    $ip = (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
    return ["ip" => $ip];
}
````

#### Endpoint with parameters
The first parameter `$input` is required in this endpoint, but if the parameter has a default value, like `$append` in this example,
it will be optional.
````php
function api_echo(string $input, string $append = "Optional parameter") {
    return ["This can be anything." => "You typed $input. But the second parameter is $append."];
}
````

##### Example requests with responses:
    **/api/?endpoint=echo:**

    ````json
    {"httpCode":500,"status":"ERROR","data":"Alright now you are confusing me... I need 1 parameters for this function to work, but for some reason you gave me only 0."}
    ````

    **/api/?endpoint=echo&input=test**

    ````json
    {"httpCode":200,"status":"OK","data":{"response":{"This can be anything.":"You typed test. But the second parameter is Optional parameter."}}}
    ````

    **/api/?endpoint=echo&input=test&append=help**

    ````json
    {"httpCode":200,"status":"OK","data":{"response":{"This can be anything.":"You typed test. But the second parameter is help."}}}
    ````

#### Generating a string
This endpoint will return a randomly generated string of `$len` length.
````php
function api_genstring(int $len = 32) : array {
    $chars = array_merge(range('a', 'z'),range('A', 'Z'),range('0', '9'));
    $string = "";
    for ($i = 0; $i < $len; $i++) {
        $rand = mt_rand(0, count($chars)-1);
        $string .= $chars[$rand];
    }
    return ["string" => $string];
}
````

##### Example requests with responses:
    **/api/?endpoint=genstring**

    ````json
    {"httpCode":200,"status":"OK","data":{"response":{"string":"3Pyir18QabZz5udOX8tkbQQwxY07nB5K"}}}
    ````

### 🟰 API Endpoint Aliases
`api_aliases.php`

Here you can put your aliases, if you have any.

The structure must be as follows:
````php
$aliases = [
        # Main function           # An array of aliases
        "api_main_function"    => ["api_alias_function_1", "api_alias_function_2"],
        "api_another_function" => ["api_another_function_alias", "api_af_short"],
];
````

These aliases will work for both "internal"/"base" functions and endpoints.


### 🧱 API Base
`api_base.php`

This file is the fundament for this API. You should not have to edit this file to customize the API sufficiently.
But if you must, here are the functions and their purpose:
| FUNCTION                                 | PURPOSE                                     | PARAMETERS                                                    |
| ---------------------------------------- | ------------------------------------------- | ------------------------------------------------------------- |
| err                                      | This function will return an error          | string $text<br>int $statusCode = 500<br>bool $fatal = true   |
| var_assert                               | Will assert variable (with optional value)  | mixed &$var<br>mixed $assertVal = false<br>bool $lazy = false |
| userIP                                   | Should return the user's IP.                |                                                               |

## 🧑‍💻 Using the API
To query the API, you can use tools like cURL.

### cURL (bash)
````bash
$ curl -X GET -H "Content-Type: application/json" https://<YOUR_SERVER>/php_api/?apikey=nrTv7xL6qyoOhWH7VBoh0Fs9JwChcoBNLhj1Us7l7zQKENBT0N8cZwDwB48YPdRL&endpoint=ip
````

### PHP:
````php
function queryAPI(string $endpoint, array $params = []) {
        $url = 'https://<YOUR_SERVER>/php_api/?endpoint='.$endpoint;
        $uri = buildURL($url, $params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_close($ch);
        $response = json_decode(curl_exec($ch), true);
        return $response;
}

$generateString = queryAPI('genstring');
echo $generateString;
````

## 🙋‍♂️ What's next?
I work on this project from time to time with no definitive goal in mind, except for improving what already is. For me this is strictly recreational, although I would happily accept contributions on this project.