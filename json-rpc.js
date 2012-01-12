/*
 *  JSON-RPC Client implementaion in Javascript
 *  Copyright (C) 2009 Jakub Jankiewicz <http://jcubic.pl> 
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

var json = (function() {
    function rpc(url, id, method, params, success, error) {
        var request = JSON.stringify({
            'jsonrpc': '2.0', 'method': method,
            'params': params, 'id': id});
        return $.ajax({
            url: url,
            data: request,
            success: success,
            error: error,
            contentType: 'application/json',
            dataType: 'json',
            async: true,
            cache: false,
            //timeout: 1,
            type: 'POST'});
    };

    return {
        multi_service: function(uris, error) {
            var len = uris.length;
            var make_service = this.service;
            return function(continuation) {
                var count = 0;
                var serviceses = [];
                $.each(object, function(k, v) {
                    make_service(v, error)(function(service) {
                        serviceses.push(service);
                        if (++count == len) {
                            continuation.apply(null, serviceses);
                        }
                    });
                });
            };
        },
        service: function(uri, error) {
            var id = 1;
            function rpc_wrapper(method) {
                return function(/* args */) {
                    var args = Array.prototype.slice.call(arguments);
                    return function(continuation) {
                        rpc(uri, id++, method, args, function(resp) {
                            if (resp.error) {
                                error(resp.error);
                            } else {
                                continuation(resp.result);
                            }
                        }, function(jxhr, status, thrown) {
                            error({
                                message: 'AJAX Eroror: "' + thrown + '"',
                                code: 1000
                            });
                        });
                    };
                };
            }
            return function(continuation) {
                rpc_wrapper('list_methods')()(function(list) {
                    var service = {};
                    $.each(list, function(i, name) {
                        service[name] = rpc_wrapper(name);
                    });
                    continuation(service);
                });
            };
        }
    };
})();
