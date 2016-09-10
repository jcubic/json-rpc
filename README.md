## Server and Client implementaion of JSON-RPC (php <=> javascript)

This is JSON-RPC implementaion, server in php and client in javascript
based on [version 1.1 of the Specification][1]

##Server 
```<?php
   require('../json-rpc.php');
   class SampleClass {
     public function index($name) {
      return "Hello".$name;
       }
    }

     handle_json_rpc(new SampleClass());
```
##Client
      ```  jsonrpc.call('/example/server.php','index',['your-name'], function(response){

                alert(response.result);
                console.log(response);
        });```

##Use
To run sample example provided 
Run a server in the root folder. example with php development server php -S localhost:6060
## License

 Released under the [MIT license][3]

 Copyright (c) 2011 [Jakub Jankiewicz][4]


[1]: http://json-rpc.org/wd/JSON-RPC-1-1-WD-20060807.html "JSON-RPC 1.1 Specification"
[2]: https://opensource.org/licenses/MIT "The MIT License (MIT)"
[3]: http://jcubic.pl "Jakub Jankiewicz"

