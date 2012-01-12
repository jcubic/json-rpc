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

?>
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
                    bar.remove_product(product.id)(function(result) {
                        console.log("product '" + product.name + "' removed");
                    });
                }
            });
        });
    });
});
```

## Dependencies

Javascript part use jQuery <http://jquery.com/>


## License

 Licensed under GNU GPL Version 3 license <http://www.gnu.org/copyleft/gpl.html>

 Copyright (c) 2011 Jakub Jankiewicz <http://jcubic.pl>

