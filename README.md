## Server and Client implementaion of JSON-RPC (php <=> javascript)

This is JSON-RPC implementaion, server in php and client in javascript
based on [version 1.1 of the Specification][1]

[1]: http://json-rpc.org/wd/JSON-RPC-1-1-WD-20060807.html "JSON-RPC 1.1 Specification"

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
    json.multi_service(["foo.php", "bar.php"], function(error) {
        alert(error.message);
    })(function(foo, bar) {

        foo.get_user("<firstName>", "<lasteName>")(function(user) {
            foo.get_content_list(user.id)(function(list) {
                var ul $('ul#users');
                $.each(list, function(i, element) {
                    ul.append('<li>' + element.name + '</li>');
                });
            });
        });

        bar.get_product_list()(function(products) {
            $.each(products, function(product) {
                if (product.status == "obsolate") {
                    bar.remove_product(product.id)(function(success) {
                        if (success) {
                            console.log("product '" + product.name + "' removed");
                        } else {
                            console.log("Error removing product '" + product.name + "'");
                        }
                    });
                }
            });
        });

    });
});
```

## Dependencies

Javascript part use [jQuery library][http://jquery.com/]


## License

 Licensed under [GNU GPL Version 3 license][http://www.gnu.org/copyleft/gpl.html]

 Copyright (c) 2011 [Jakub Jankiewicz][http://jcubic.pl]

