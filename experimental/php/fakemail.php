<?php
  require_once("fakemailserver.class.php");
  require_once("fakemailoptions.class.php");
  require_once("fakemail.class.php");

  $options = new FakeMailOptions();
  $server = new FakeMailServer($options->host, $options->port, $options->path,
                               $options->log, $options->debug);

  if ($options->stop == true)
  {
    $server->stop();
  } else
  {
    $server->setMaxClients(10);
    $server->start();
  }
?>