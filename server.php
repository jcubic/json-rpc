<?php
  require('json-rpc.php');
  class Server {
    public function test($message) {
      return "you send " . $message;
    }
  }

  handle_json_rpc(new Server());
  ?>
