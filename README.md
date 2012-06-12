## Server and Client implementaion of JSON-RPC (php <=> javascript)

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

$(function() {
    rpc({
        url: "foo.php",
        error: function(error) {
            alert(error.message);
        }
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
});
```

## Dependencies

Javascript part use [jQuery library][2]


## License

 Licensed under [GNU GPL Version 3 license][3]

 Copyright (c) 2011 [Jakub Jankiewicz][4]


[1]: http://json-rpc.org/wd/JSON-RPC-1-1-WD-20060807.html "JSON-RPC 1.1 Specification"
[2]: http://jquery.com/ "jQuery library"
[3]: http://www.gnu.org/copyleft/gpl.html "GNU GPL Version 3 license"
[4]: http://jcubic.pl "Jakub Jankiewicz"

