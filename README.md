## Server and Client implementaion of JSON-RPC (php <=> javascript)

This is JSON-RPC implementaion, server in php and client in javascript

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
```


Javascript library should be use with this php implementation because it have
built in method "list_methods", that will return all class methods.

## Client

single service

```javascript

$(function() {
    var rpc = json.service("foo.php", function(error) {
        alert(error.message);
    });

    rpc(function(foo) {
        // now here you can access methods from Foo class
        foo.ping("Hello")(function(response) {
            alert(response);
        });
    });

});
```

multi service

```javascript

$(function() {
    var service = json.multi_service(["foo.php", "bar.php"], function(error) {
        alert(error.message);
    });

    service(function(foo, bar) {
        foo.get_user("<firstName>", "<lasteName>")(function(user) {
            bar.get_content_list(user.id)(function(list) {
                var ul $('ul');
                $.each(list, function(i, element) {
                    ul.append('<li>' + element.name + '</li>');
                });
            });
        });
    });
});
```

## Dependencies

Javascript library use jQuery <http://jquery.com/>


## License

This is free software. You may distribute it under the terms of the

Poetic License. http://genaud.net/2005/10/poetic-license/

Copyright (c) 2012 Jakub Jankiewicz
