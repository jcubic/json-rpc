/*
 *  JSON-RPC Client implementaion in Javascript
 *  Copyright (C) 2009 Jakub Jankiewicz <http://jcubic.pl>
 *
 *  Released under the MIT license
 *
 */

var jsonrpc = {
    
  call: function(url, method, params) {
    var data = JSON.stringify({
        "version": 1.1,
        "method": method,
        "params": params,
        "id": this.getId(1, 10000)
      });
    this.request(url, data);
  },

  getId: function (min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
  },
 
  request: function(url, data) {
         
      var xhr = new XMLHttpRequest();
      xhr.withCredentials = true;

      xhr.addEventListener("readystatechange", function () {
        if (this.readyState === 4) {
          console.log(this.responseText);
        }
      });

      xhr.open("POST", url);
      xhr.setRequestHeader("content-type", "application/json");
      xhr.setRequestHeader("cache-control", "no-cache");

      xhr.send(data); 
  }
   
    
}
