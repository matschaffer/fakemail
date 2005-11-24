<?PHP

  require_once("external/patserver.class.php");

  /**
  * @version  0.1
  */
  class FakeMailServer extends patServer
  {
    var $users = array();
    var $lineFeed = "\n";
    var $recipients = array();
    var $logfile = '';
    var $mailpath = '';
    var $verbosity = 0;

    /**
    * server is started
    *
    * @access private
    */
    function FakeMailServer($domain = "localhost", $port = 9090, $mailpath = '', $logfile = '', $verbosity = 0)
    {
      $this->patServer($domain, $port);
      $this->logfile = $logfile;
      $this->mailpath = $mailpath;
      $this->verbosity = $verbosity;
      $this->debug = ($verbosity > 2);
      $this->debugDest = ($logfile != '') ? $logfile : 'stdout';
    }


    /**
    * send a log message
    *
    * @access private
    * @param  string
    */
    function sendLogMessage($msg, $verbosity = 1)
    {
      if ($verbosity > $this->verbosity)
      {
        return false;
      }
      $msg = date("Y-m-d H:i:s", time())." ".$msg."\n";
      if( $this->logfile == "")
      {
        echo $msg;
        flush();
      } else
      {
        error_log($msg, 3, $this->logfile);
      }
      return true;
    }


    /**
    * write the mail body to file
    *
    * @access private
    * @param  integer $clientId id of the client that sent the command
    */
    function writeMail($clientId)
    {
      if (count($this->users[$clientId]['to']) == 0)
      {
        return false;
      }
      foreach($this->users[$clientId]['to'] as $to)
      {
        $filename = $to;
        $filename = str_replace('<', '', $filename);
        $filename = str_replace('>', '', $filename);
        if (isset($this->recipients[$to]))
        {
          $filename.= ".".$this->recipients[$to];
        } else
        {
          $filename.= ".1";
        }
        if ($this->mailpath != '')
        {
          $filename = $this->mailpath.'/'.$filename;
        }
        if (file_exists($filename))
        {
          unlink($filename);
        }
        $this->sendLogMessage("Writing mail for ".$to.' to '.basename($filename), 2);
        $fh = fopen($filename, "w");
        fwrite($fh, $this->users[$clientId]['body']);
        fclose($fh);
      }
      return true;
    }


    /**
    * shutdown server
    *
    * @access private
    */
    function stop()
    {
      $this->sendLogMessage("Connecting to server ".$this->domain, 2);
      $errno = 0;
      $errstr = '';
      $connection = @fsockopen($this->domain,
                              $this->port,
                              $errno,
                              $errstr,
                              4);
      if(empty($connection))
      {
      $this->sendLogMessage("Connecting to server ".$this->domain." failed!", 2);
        return false;
      }
      if(substr(PHP_OS, 0, 3) != "WIN")
      {
        socket_set_timeout($connection, 4, 0);
      }
      while($str = fgets($connection, 515))
      {
        # if the 4th character is a space then we are done reading
        # so just break the loop
        if(substr($str,3,1) == " ")
        {
          break;
        }
      }
      fputs($connection, "HALT\n");
      fclose($connection);
      $this->sendLogMessage("Issued HALT to server ".$this->domain, 2);
      return true;
    }


    /**
    * server is started
    *
    * @access private
    */
    function onStart()
    {
      $this->sendLogMessage("FakeMail started at {$this->domain}:{$this->port}.");
    }


    /**
    * data is received
    *
    * @access private
    * @param  integer $clientId id of client that sent the data
    * @param  string  $data   data that was sent
    */
    function onReceiveData( $clientId, $data )
    {
      if (!$this->users[$clientId]['data'])
      {
        $data = trim($data);
        $command = substr($data, 0, 4);
        $params = explode(":", substr($data, 5, 1000));
        $this->handleCommand($clientId, $command, $params);
      } else
      {
        $this->users[$clientId]['body'].= $data;
        $end = (substr(trim($data), -2) == "\n.") || (trim($data) == '.');
        if ($end)
        {
          $this->users[$clientId]['body'] = trim(substr(trim($this->users[$clientId]['body']), 0, -1))."\n";
          $this->users[$clientId]['data'] = false;
          $this->sendData($clientId, "250 OK (Message queued)".$this->lineFeed);
        }
      }
    }


    /**
    * connection closed
    *
    * @access private
    * @param  integer $clientId id of the client that closed connection
    */
    function onClose($clientId)
    {
      $client = $this->getClientInfo($clientId);
      $this->sendLogMessage("Client disconnected from {$client['host']}:{$client['port']}.");
      unset($this->users[$clientId]);
    }


    /**
    * connection established
    *
    * @access private
    * @param  integer $clientId id of the client that established connection
    */
    function onConnect($clientId)
    {
    $client = $this->getClientInfo($clientId);
      $this->sendLogMessage("Client connected from {$client['host']}:{$client['port']}.");
      $this->sendData($clientId, "220 READY (PHP FakeMail Service ready)".$this->lineFeed);
      $this->users[$clientId]['from'] = '';
      $this->users[$clientId]['to'] = array();
      $this->users[$clientId]['body'] = '';
      $this->users[$clientId]['data'] = false;
    }


    /**
    * server is shut down
    *
    * @access private
    */
    function afterShutdown()
    {
      $this->sendLogMessage("FakeMail stopped.");
    }


    /**
    * connection was refused (too many clients)
    *
    * @access private
    * @param  integer $clientId id of the client that wasn't allowed to connect
    */
    function onConnectionRefused( $clientId )
    {
      $client = $this->getClientInfo($clientId);
      $this->sendLogMessage("Connection refused for {$client['host']}:{$client['port']}.");
    }


    /**
    * handle a command
    *
    * @access private
    * @param  integer $clientId id of the client that sent the command
    * @param  string  $command  name of the command
    * @param  string  $params   list of all params
    */
    function handleCommand($clientId, $command, $params)
    {
      $command = strtoupper($command);
      $client = $this->getClientInfo($clientId);
      $this->sendLogMessage("Handling command {$command} for {$client['host']}:{$client['port']}.", 2);
      switch($command)
      {
        case  "QUIT":
          $this->sendData($clientId, "221 QUIT (FakeMail Service closing transmission channel)".$this->lineFeed);
          $this->writeMail($clientId);
          $this->closeConnection($clientId);
          break;

        case  "HALT":
          $this->sendData($clientId, "250 OK (FakeMail Service Shutdown)".$this->lineFeed);
          $this->shutdown();
          die();
          break;

        case  "HELO":
        case  "EHLO":
          $info = $this->getClientInfo($clientId);
          $this->sendData($clientId, '250 OK (Welcome '.$info['host'].")".$this->lineFeed);
          break;

        case  "MAIL":
          if (strcasecmp($params[0], 'From') == 0)
          {
            $this->sendData($clientId, '250 OK (From: '.trim($params[1]).")".$this->lineFeed);
            $this->users[$clientId]['from'] = trim($params[1]);
          } else
          {
            $this->sendData($clientId, "500 SYNTAX ERROR (Unknown command {$command} {$params[0]})".$this->lineFeed);
          }
          break;

        case  "RCPT":
          if (strcasecmp($params[0], 'To') == 0)
          {
            $this->sendData($clientId, '250 OK (To '.trim($params[1]).")".$this->lineFeed);
            $this->users[$clientId]['to'][] = trim($params[1]);
            if (!isset($this->recipients[trim($params[1])]))
            {
              $this->recipients[trim($params[1])] = 1;
            } else
            {
              $this->recipients[trim($params[1])]++;
            }
          } else
          {
            $this->sendData($clientId, "500 SYNTAX ERROR (Unknown command {$command} {$params[0]})".$this->lineFeed);
          }
          break;

        case  "DATA":
          $this->sendData($clientId, "354 DATA (Start mail input; end with <CRLF>.<CRLF>)".$this->lineFeed);
          $this->users[$clientId]['data'] = true;
          break;

        case  "RSET":
          $this->sendData($clientId, "250 OK (Reseting Maildata)".$this->lineFeed);
          $this->users[$clientId]['from'] = '';
          $this->users[$clientId]['to'] = array();
          $this->users[$clientId]['body'] = '';
          $this->users[$clientId]['data'] = false;
          break;

        default:
          $this->sendData($clientId, "500 SYNTAX ERROR (Unknown command ".$command.")".$this->lineFeed);
          break;
      }
    }
  }
?>