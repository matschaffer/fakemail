<?php
  require_once("fakemailserver.class.php");
  require_once("fakemailoptions.class.php");

  $options = new FakeMailOptions();
  $server = new FakeMailServer($options->host, $options->port, $options->path,
                               $options->log, $options->verbosity);

  ## Handle Stop
  if ($options->stop == true)
  {
    $server->stop();
    exit();
  }

  ## Handle Background
  if ($options->background == true)
  {
    if (!function_exists('pcntl_fork'))
    {
      echo "No support for fork!\n";
      exit();
    }

    function sig_handler($signo)
    {
      global $server;
      switch($signo)
      {
          case SIGTERM:
              $server->stop();
              exit;
              break;
          case SIGHUP:
              $server->stop();
              $server->start();
              break;
      }
    }

    declare(ticks=1);
    $pid = pcntl_fork();
    if ($pid == -1)
    {
      echo "Could not fork.\n";
      exit();
    } else if ($pid)
    {
      echo "$pid\n";
      exit();
    }
    if (!posix_setsid())
    {
      echo "Could not detach from terminal\n";
      exit();
    }
    pcntl_signal(SIGTERM, "sig_handler");
    pcntl_signal(SIGHUP, "sig_handler");
  }

  ## Start Server
  $server->setMaxClients(10);
  $server->start();
?>