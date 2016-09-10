<?php
  require('../json-rpc.php');
class SampleClass
{
    public function index($name)
    {
        return "Hello ".$name;
    }
}

  handle_json_rpc(new SampleClass());
