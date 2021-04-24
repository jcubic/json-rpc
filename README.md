## [Server and Client implementaion of JSON-RPC (php <=> javascript)](https://github.com/jcubic/json-rpc/)

This is JSON-RPC implementaion, server in php and client in javascript
based on [version 1.1 of the Specification][1]

## Server

```php
<?php
require('json-rpc.php');

class Foo {
    function ping($str) {
        return "pong '$str'";
    }
}

handle_json_rpc(new Foo());

?>
```


## Client

```javascript
rpc({
    url: "foo.php",
    error: function(error) {
        alert(error.message);
    },
    // errorOnAbort: true,
    debug: function(json, which) {
        console.log(which + ': ' + JSON.stringify(json));
    }
})(function(foo) {
    // now here you can access methods from Foo class
    foo.ping("Hello")(function(response) {
        alert(response);
    });
});
```

if you prefer to use promises uou can use option `promisify: true`

```javascript
rpc({
    url: 'servce.php'.
    promisify: true
}).then(function(service) {
    service.ping("hello").then(function(response) {
       alert(resonse);
    });
});
```

## Requirement

* mbstring php module

## License

Released under the [MIT license][3]<br/>
Copyright (c) 2011-2021 [Jakub T. Jankiewicz][3]


[1]: http://json-rpc.org/wd/JSON-RPC-1-1-WD-20060807.html "JSON-RPC 1.1 Specification"
[2]: https://opensource.org/licenses/MIT "The MIT License (MIT)"
[3]: https://jcubic.pl/me "Jakub T. Jankiewicz"

