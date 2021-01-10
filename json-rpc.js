/**
 *  JSON-RPC Client implementaion in Javascript
 *  Copyright (C) 2009-2021 Jakub T. Jankiewicz <https://jcubic.pl/me>
 *
 *  Released under the MIT license
 *
 */

var rpc = (function() {
    function ajax(request) {
        var xhr = new XMLHttpRequest();
        xhr.open(request.type || request.method, request.url, true);
        xhr.setRequestHeader('Accept', request.accepts);
        xhr.setRequestHeader('Content-type', request.contentType);
        xhr.onreadystatechange = function() {
            if (xhr.readyState > 3) {
                if (xhr.status == 200) {
                    request.success(xhr.responseText, xhr.statusText, xhr);
                } else {
                    request.error(xhr, xhr.statusText);
                }
                xhr = null;
            }
        };
        xhr.send(request.data);
        return xhr;
    }

    function rpc(url, id, method, params, success, error, debug) {
        var request  = {
            'version': '1.1', 'method': method,
            'params': params, 'id': id
        };
        var data = JSON.stringify(request);
        if (debug && debug.constructor === Function) {
            debug(data, 'request');
        }
        return ajax({
            url: url,
            data: data,
            success: function(response, status, jxhr) {
                if (debug && debug.constructor === Function) {
                    debug(response, 'response');
                }
                try {
                    response = JSON.parse(response);
                } catch(e) {
                    response = response.replace(/<[^>]+>/g, '').
                        replace(/^[\n\s]+|[\n\s]+$/g, '');
                    error(jxhr, status, response);
                    return;
                }
                success(response);
            },
            error: error,
            type: 'POST'
        });
    }
    return function(options) {
        var id = 1;
        function ajax_error(jxhr) {
            var message;
            if (jxhr.status == 0) {
                message = 'Network Error';
            } else {
                message = jxhr.status;
            }
            message = 'AJAX Error: "' + message + '"';
            if (options.error) {
                options.error({
                    message: message,
                    code: 300
                });
            } else {
                throw message;
            }
        }
        function rpc_wrapper(method) {
            return function(/* args */) {
                var args = Array.prototype.slice.call(arguments);
                function call(continuation) {
                    rpc(options.url, id++, method, args, function(resp) {
                        if (!resp) {
                            var message = "No response from method `" +
                                method + "'";
                            if (options.error) {
                                options.error({
                                    code: 301,
                                    message: message
                                });
                            } else {
                                throw message;
                            }
                        } else {
                            continuation(resp.error, resp.result);
                        }
                    }, ajax_error, options.debug);
                }
                if (options.promisify) {
                    return new Promise(function(resolve, reject) {
                        call(function(error, data) {
                            if (error) {
                                reject(error);
                            } else {
                                resolve(data);
                            }
                        });
                    });
                } else {
                    return call;
                }
            };
        }
        function make_service(response) {
            var service = {};
            response.procs.forEach(function(proc) {
                service[proc.name] = rpc_wrapper(proc.name);
                service[proc.name].toString = function() {
                    return "#<rpc-method: `" + proc.name + "'>";
                };
            });
            return service;
        }
        function call(continuation) {
            rpc(options.url, id++, 'system.describe', null, function(response) {
                var message;
                if (!response) {
                    if (options.error) {
                        message = "No response from `system.describe' method";
                        options.error({
                            code: 301,
                            message: message
                        });
                    } else {
                        throw message;
                    }
                } else {
                    continuation(response);
                }
            }, ajax_error, options.debug);
        }
        if (options.promisify) {
            return new Promise(function(resolve, reject) {
                call(function(response) {
                    if (response.error) {
                        reject(response.error);
                    } else {
                        resolve(make_service(response));
                    }
                });
            });
        } else {
            return function(continuation) {
                call(function(response) {
                    if (response.error) {
                        if (options.error) {
                            options.error(response.error);
                        } else {
                            throw response.error.message;
                        }
                    } else {
                        continuation(make_service(response), response);
                    }
                });
            };
        }
    };
})();
