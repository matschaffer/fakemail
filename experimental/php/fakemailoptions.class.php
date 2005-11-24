<?php
  require_once("external/consolegetargs.php");

  class FakeMailOptions
  {
    var $host;
    var $port;
    var $log;
    var $path;
    var $verbosity;
    var $stop;
    var $background;

    /**
     * Return the config array.
     *
     * The config array is the set of rules for command line
     * arguments. For more details please read the comments
     * in Getargs.php
     *
     * @static
     * @access public
     * @param  none
     * @return &array
     */
    function FakeMailOptions($argsArray = NULL)
    {
      $config = $this->getConfigArray();
      if (is_array($argsArray))
      {
        $args = &Console_Getargs::factory($config, $argsArray);
      } else
      {
        $args = &Console_Getargs::factory($config);
      }

      // Check for errors.
      if (PEAR::isError($args))
      {
        if ($args->getCode() === CONSOLE_GETARGS_ERROR_USER)
        {
          // User put illegal values on the command line.
          echo Console_Getargs::getHelp($config, NULL, $args->getMessage())."\n";
        } else if ($args->getCode() === CONSOLE_GETARGS_HELP)
        {
          // User needs help.
          echo Console_Getargs::getHelp($config)."\n";
        }
        exit;
      } else
      {
        // Read opts
        $this->verbosity = intval($args->getValue('verbosity'));
        $this->log = $args->getValue('log');
        $this->host = $args->getValue('host');
        $this->port = intval($args->getValue('port'));
        $this->path = $args->getValue('path');
        $this->stop = $args->getValue('stop');
        $this->background = $args->getValue('background');
      }
    }


    /**
     * Return the config array.
     *
     * The config array is the set of rules for command line
     * arguments. For more details please read the comments
     * in Getargs.php
     *
     * @static
     * @access public
     * @param  none
     * @return &array
     */
    function &getConfigArray()
    {
      $configArray = array();

      //verbosity
      $configArray['verbosity'] = array('short' => 'v',
                                    'min'   => 1,
                                    'max'   => 1,
                                    'desc'  => 'Be more verbose with logging (0 - 3)',
                                    'default' => '0'
                                    );

      //log
      $configArray['log'] = array('short' => 'l',
                                    'min'   => 1,
                                    'max'   => 1,
                                    'desc'  => 'Optional file to append messages to',
                                    'default' => ''
                                    );
      //host
      $configArray['host'] = array( 'short'   => 'h',
                                  'min'     => 1,
                                  'max'     => 1,
                                  'desc'    => 'Host address',
                                  'default' => 'localhost'
                                 );
      //port
      $configArray['port'] = array( 'short'   => 'p',
                                    'min'     => 1,
                                    'max'     => 1,
                                    'desc'    => 'Port number',
                                    'default' => 9090
                                   );
      //path
      $configArray['path'] = array('short'   => 'm',
                                        'min'     => 1,
                                        'max'     => 1,
                                        'desc'    => 'Path to write mails to',
                                        'default' => dirname(__FILE__).'/data'
                                        );

      //background
      $configArray['background'] = array( 'short'   => 'b',
                                    'min'     => 0,
                                    'max'     => 0,
                                    'desc'    => 'Start server in background (prints the pid to stdout)'
                                   );
      //stop
      $configArray['stop'] = array( 'short'   => 's',
                                    'min'     => 0,
                                    'max'     => 0,
                                    'desc'    => 'Shutdown server'
                                   );
      // Show the help message.
      // (Not really needed unless you want help to show up in the
      //  list of options in the help menu.)
      $configArray['help'] = array('short' => 'h',
                                   'max'   => 0,
                                   'desc'  => 'Show this help.'
                                   );
      return $configArray;
    }
  }
?>
