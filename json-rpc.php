<?php
/*
  JSON-RPC Server implemenation
  Copyright (C) 2009 Jakub Jankiewicz <http://jcubic.pl> 

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
  USAGE: create one class with public methods and call handle_json_rpc function
  with instance of this class
           
  <?php
  require('json_rpc.php');
  class Server {
    public function test($message) {
      return "you send " . $message;
    }
                   
  }

  handle_json_rpc(new Server());
  ?>

  you can provide documentations for methods
  by adding static field:

  class Server {
  ...
  public static $test_documentation = "doc string";
  }
*/

class JsonRpcExeption extends Exception {
    function __construct($code, $message) {
        $this->code = $code;
        Exception::__construct($message);
    }
    function code() {
        return $this->code;
    }
}

// ----------------------------------------------------------------------------
function json_error() {
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
// check if object has field
function has_field($object, $field) {
    //return in_array($field, array_keys(get_object_vars($object)));
    return array_key_exists($field, get_object_vars($object));
}

// ----------------------------------------------------------------------------
// return object field if exist otherwise return default value
function get_field($object, $field, $default) {
    $array = get_object_vars($object);
    if (isset($array[$field])) {
        return $array[$field];
    } else {
        return $default;
    }
}


// ----------------------------------------------------------------------------
//create json-rpc response
function response($result, $id, $error) {
    if ($error) {
        $error['name'] = 'JSONRPCError';
    }
    return json_encode(array("jsonrpc" => "2.0",
                             'result' => $result,
                             'id' => $id,
                             'error'=> $error));
}

// ----------------------------------------------------------------------------
// try to extract id from broken json
function extract_id() {
    $regex = '/[\'"]id[\'"] *: *([0-9]*)/';
    if (preg_match($regex, $GLOBALS['HTTP_RAW_POST_DATA'], $m)) {
        return $m[1];
    } else {
        return null;
    }
}
// ----------------------------------------------------------------------------
function service_description($object) {
    $class = get_class($object);
    $methods = get_class_methods($class);
    $service = array("sdversion" => "1.0",
                     "name" => "DemoService",
                     "address" => $_SERVER['PHP_SELF'],
                     "id" => "urn:md5:" . md5($_SERVER['PHP_SELF']));
    $static = get_class_vars($class);
    foreach ($methods as $method) {
        $proc = array("name" => $method);
        $help_str_name = $method . "_documentation";
        if (array_key_exists($help_str_name, $static)) {
            $proc['help'] = $static[$help_str_name];
        }
        
        $service['procs'][] = $proc;
    }
    return $service;
}

// ----------------------------------------------------------------------------
function error_handler($err, $message, $file, $line) {
    $content = file($file);
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
    echo response(null, $id, $error);
    exit;
}

// ----------------------------------------------------------------------------
function get_json_request() {
    $request = $GLOBALS['HTTP_RAW_POST_DATA'];
    /*
    if ($request == '') {
        $input = file_get_contents('php://input');
    }
    */
	if ($request == "") {
        throw new JsonRpcExeption(101, "Parse Error: no data");
    }
    $encoding = mb_detect_encoding($request, 'auto');
    //convert to unicode
    if ($encoding != 'UTF-8') {
        $request = iconv($encoding, 'UTF-8', $request);
    }
    $request = json_decode($request);
    if ($request == NULL) { // parse error
        $error = json_error();
        throw new JsonRpcExeption(101, "Parse Error: $error");
    }
    return $request;
}

// ----------------------------------------------------------------------------
function handle_json_rpc($object) {
    ini_set('display_errors', 1);
    ini_set('track_errors', 1);
    error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
    set_error_handler('error_handler');
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
            } else if (!$id) {
                $error = "no id";
            } else {
                $error = "unknown reason";
            }
            throw new JsonRpcExeption(103,  "Invalid Request: $error");
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
        if (strcmp($method, "system.describe") == 0) {
            echo json_encode(service_description($object));
        } else if (!in_array($method, $methods)) {
            $msg = "Procedure `" . $method . "' not found";
            throw new JsonRpcExeption(104, $msg);
        } else {
            $method_object = new ReflectionMethod($class, $method);
            $num_got = count($params);
            $num_expect = $method_object->getNumberOfParameters();
            if ($num_got != $num_expect) {
                $msg = "Wrong number of parameters. Got $num_got expect $num_expect";
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
}

?>
