<?php
/*
  JSON-RPC Server implemenation
  Copyright (C) 2009 Jakub Jankiewicz <http://jcubic.pl>

  Released under the MIT license
*/

/*
  USAGE: create one class with public methods and call handle_json_rpc function
  with instance of this class

  <?php
   require('../json-rpc.php');
   class SampleClass {
     public function index($name) {
      return "Hello".$name;
       }
    }

     handle_json_rpc(new SampleClass());

 
  you can provide documentations for methods
  by adding static field:

  class Server {
  ...
  public static $test_documentation = "doc string";
  }
*/
// ----------------------------------------------------------------------------
function handle_errors()
{
    set_error_handler('error_handler');
    ob_start();
}
// ----------------------------------------------------------------------------
function error_handler($err, $message, $file, $line)
{
    global $stop;
    $stop = true;
    $content = explode("\n", file_get_contents($file));
    header('Content-Type: application/json');
    $id = extract_id(); // don't need to parse
    $error = array(
       "code" => 100,
       "message" => "Server error",
       "error" => array(
          "name" => "PHPErorr",
          "code" => $err,
          "message" => $message,
          "file" => $file,
          "at" => $line,
          "line" => $content[$line-1]));
    ob_end_clean();
    echo response(null, $id, $error);
    exit();
}
// ----------------------------------------------------------------------------

class JsonRpcExeption extends Exception
{
    function __construct($code, $message)
    {
        $this->code = $code;
        Exception::__construct($message);
    }
    function code()
    {
        return $this->code;
    }
}

// ----------------------------------------------------------------------------
function json_error()
{
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            return 'No error has occurred';
        case JSON_ERROR_DEPTH:
            return 'The maximum stack depth has been exceeded';
        case JSON_ERROR_CTRL_CHAR:
            return 'Control character error, possibly incorrectly encoded';
        case JSON_ERROR_SYNTAX:
            return 'Syntax error';
        case JSON_ERROR_UTF8:
            return 'Malformed UTF-8 characters, possibly incorrectly encoded';
    }
}

// ----------------------------------------------------------------------------
function get_raw_post_data()
{
    if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
        return $GLOBALS['HTTP_RAW_POST_DATA'];
    } else {
        return file_get_contents('php://input');
    }
}

// ----------------------------------------------------------------------------
// check if object has field
function has_field($object, $field)
{
    //return in_array($field, array_keys(get_object_vars($object)));
    return array_key_exists($field, get_object_vars($object));
}

// ----------------------------------------------------------------------------
// return object field if exist otherwise return default value
function get_field($object, $field, $default)
{
    $array = get_object_vars($object);
    if (isset($array[$field])) {
        return $array[$field];
    } else {
        return $default;
    }
}


// ----------------------------------------------------------------------------
//create json-rpc response
function response($result, $id, $error)
{
    if ($error) {
        $error['name'] = 'JSONRPCError';
    }
    return json_encode(array("jsonrpc" => "1.1",
                             'result' => $result,
                             'id' => $id,
                             'error'=> $error));
}

// ----------------------------------------------------------------------------
// try to extract id from broken json
function extract_id()
{
    $regex = '/[\'"]id[\'"] *: *([0-9]*)/';
    $raw_data = get_raw_post_data();
    if (preg_match($regex, $raw_data, $m)) {
        return $m[1];
    } else {
        return null;
    }
}
// ----------------------------------------------------------------------------
function currentURL()
{
    $pageURL = 'http';
    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
        $pageURL .= "s";
    }
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}
// ----------------------------------------------------------------------------
function service_description($object)
{
    $class_name = get_class($object);
    $methods = get_class_methods($class_name);
    $service = array("sdversion" => "1.0",
                     "name" => "DemoService",
                     "address" => currentURL(),
                     "id" => "urn:md5:" . md5(currentURL()));
    $static = get_class_vars($class_name);
    foreach ($methods as $method_name) {
        $proc = array("name" => $method_name);
        $method = new ReflectionMethod($class_name, $method_name);
        $params = array();
        foreach ($method->getParameters() as $param) {
            $params[] = $param->name;
        }
        $proc['params'] = $params;
        $help_str_name = $method_name . "_documentation";
        if (array_key_exists($help_str_name, $static)) {
            $proc['help'] = $static[$help_str_name];
        }
        $service['procs'][] = $proc;
    }
    $service['procs'][] = array(
        'name' => 'help'
    );
    return $service;
}

// ----------------------------------------------------------------------------
function get_json_request()
{
    $request = get_raw_post_data();
    if ($request == "") {
        throw new JsonRpcExeption(101, "Parse Error: no data");
    }
    $encoding = mb_detect_encoding($request, 'auto');
    //convert to unicode
    if ($encoding != 'UTF-8') {
        $request = iconv($encoding, 'UTF-8', $request);
    }
    $request = json_decode($request);
    if ($request == null) { // parse error
        $error = json_error();
        throw new JsonRpcExeption(101, "Parse Error: $error");
    }
    return $request;
}
// ----------------------------------------------------------------------------
function canCall($args_length, $class, $method)
{
    $method_object = new ReflectionMethod($class, $method);
    $num_expect = $method_object->getNumberOfParameters();
    $num_expect2 = $method_object->getNumberOfRequiredParameters();
    return $args_length == $num_expect || $args_length == $num_expect2;
}
// ----------------------------------------------------------------------------
function handle_json_rpc($object)
{
    try {
        $input = get_json_request();

        header('Content-Type: application/json');

        $method = get_field($input, 'method', null);
        $params = get_field($input, 'params', null);
        $id = get_field($input, 'id', null);

        // json rpc error
        if (!($method && $id)) {
            if (!$id) {
                $id = extract_id();
            }
            if (!$method) {
                $error = "no method";
            } elseif (!$id) {
                $error = "no id";
            } else {
                $error = "unknown reason";
            }
            throw new JsonRpcExeption(103, "Invalid Request: $error");
            //": " . $GLOBALS['HTTP_RAW_POST_DATA']));
        }

        // fix params (if params is null set it to empty array)
        if (!$params) {
            $params = array();
        }
        // if params is object change it to array
        if (is_object($params)) {
            if (count(get_object_vars($params)) == 0) {
                $params = array();
            } else {
                $params = get_object_vars($params);
            }
        }
        // call Service Method
        $class = get_class($object);
        $methods = get_class_methods($class);
        $num_got = count($params);
        if (strcmp($method, "system.describe") == 0) {
            echo json_encode(service_description($object));
        } else {
            $exist = in_array($method, $methods);
            if ($exist) {
                $method_object = new ReflectionMethod($class, $method);
                $num_expect = $method_object->getNumberOfParameters();
                $num_expect2 = $method_object->getNumberOfRequiredParameters();
                $can_call = $num_got == $num_expect || $num_got == $num_expect2;
            } else {
                $can_call = false;
            }
            if ($method == 'help' && (!$exist || !$can_call)) {
                if (count($params) > 0) {
                    if (!in_array($params[0], $methods)) {
                        $msg = 'There is no ' . $params[0] . ' method';
                        throw new JsonRpcExeption(108, $msg);
                    } else {
                        $static = get_class_vars($class);
                        $help_str_name = $params[0] . "_documentation";
                        //throw new Exception(implode(", ", $static));
                        if (array_key_exists($help_str_name, $static)) {
                            echo response($static[$help_str_name], $id, null);
                        } else {
                            $msg = $method . " method has no documentation";
                            throw new JsonRpcExeption(107, $msg);
                        }
                    }
                } else {
                    $url = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
                    $msg = 'PHP JSON-RPC - in "' . $url . "\"\n";
                    $msg .= "class \"$class\" has methods: " .
                        implode(", ", array_slice($methods, 0, -1)) .
                        " and " .  $methods[count($methods)-1] . ".";
                    echo response($msg, $id, null);
                }
            } elseif (!$exist) {
                if (in_array("__call", $methods)) {
                    $result = call_user_func_array(array($object, $method), $params);
                    echo response($result, $id, null);
                } else {
                    // __call will be called for any method that's missing
                    $msg = "Procedure `" . $method . "' not found";
                    throw new JsonRpcExeption(104, $msg);
                }
            } elseif (!$can_call) {
                $msg = "Wrong number of parameters in `$method' method. Got " .
                       "$num_got expect $num_expect";
                throw new JsonRpcExeption(105, $msg);
            } else {
                //throw new Exception('x -> ' . json_encode($params));
                $result = call_user_func_array(array($object, $method), $params);
                echo response($result, $id, null);
            }
        }
    } catch (JsonRpcExeption $e) {
        // exteption with error code
        $msg = $e->getMessage();
        $code = $e->code();
        if ($code = 101) { // parse error;
            $id = extract_id();
        }
        echo response(null, $id, array("code"=>$code, "message"=>$msg));
    } catch (Exception $e) {
        //catch all exeption from user code
        $msg = $e->getMessage();
        echo response(null, $id, array("code"=>200, "message"=>$msg));
    }
    ob_end_flush();
}
