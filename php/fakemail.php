<?php
    /**
     *	simple PHP class that incapsulates fakemail server stop, start and some utility routines
     *	@package	FakeMail
     *	@version	$Id: fakemail.php,v 1.1 2005/03/18 09:46:14 pachanga Exp $
     */

    @define('FAKE_MAIL_SCRIPT', dirname(__FILE__) . '/../fakemail');
    @define('FAKE_MAIL_PORT', 25);
    @define('FAKE_MAIL_HOST', 'localhost');
    @define('FAKE_MAIL_DUMP_PATH', dirname(__FILE__) . '/');

    class FakeMailDaemon {
        var $pid = null;

        var $fakemail = null;
        var $mail_path = null;
        var $port = null;
        var $host = null;
        var $log_path  = null;

        var $accessed_recipients = array();

        function FakeMailDaemon($fakemail = null, $mail_path = null, $port = null, $host = null) {
            $this->fakemail = is_null($fakemail) ?  FAKE_MAIL_SCRIPT : $fakemail;
            $this->mail_path = is_null($mail_path) ?  FAKE_MAIL_DUMP_PATH : $mail_path;
            $this->port = is_null($port) ?  FAKE_MAIL_PORT : $port;
            $this->host = is_null($host) ?  FAKE_MAIL_HOST : $host;
        }

        function useLog($log_path = null) {
            $this->log_path = is_null($log_path) ?  $this->mail_path . '/fakemail.log' : $log_path;
        }

        function start() {
            if(!file_exists($this->fakemail)) {
                die('fakemail script "'. $this->fakemail .'" not found');
            }

            if(!file_exists($this->mail_path) || !is_dir($this->mail_path)) {
                die('Directory for fake mails "'. $this->mail_path .'" not found' );
            }

            $cmd = "perl ". $this->fakemail ." --background --path={$this->mail_path} --port={$this->port} --host={$this->host}";

            if($this->log_path) {
                $cmd .= " --log={$this->log_path}";
            }

            $this->pid = exec($cmd, $out);

            if(!$this->pid) {
                die('fakemail script has not started for some reason, here is the command line: ' . $cmd);
            }
        }

        function stop() {
            if($this->pid) {
                exec("kill {$this->pid}");
            }
        }

        function clearLog() {
            unlink($this->log_path);
        }

        function removeRecipientMail($recipient) {
            $names = $this->_getRecipientFileNames($recipient);

            foreach($names as $name) {
                unlink($this->mail_path .'/'. $name);
            }
        }

        function removeAccessedRecipientsMail() {
            foreach(array_keys($this->accessed_recipients) as $recipient) {
                $this->removeRecipientMail($recipient);
            }
        }

        function getRecipientMailCount($recipient) {
            $this->_markRecipientAccessed($recipient);

            return count($this->_getRecipientFileNames($recipient));
        }

        function getRecipientMailContents($recipient) {
            $this->_markRecipientAccessed($recipient);

            $contents = array();
            $names = $this->_getRecipientFileNames($recipient);
            foreach($names as $name) {
                $contents[] = file_get_contents($this->mail_path .'/'. $name);
            }
            return $contents;
        }

        function _markRecipientAccessed($recipient) {
            $this->accessed_recipients[$recipient] = 1;
        }

        function _getRecipientFileNames($recipient) {
            $saved_working_dir = getcwd();
            $recipient_files = array();

            if (is_dir($this->mail_path)) {

                chdir($this->mail_path);
                $handle = opendir('.');

                while (($file = readdir($handle)) !== false) {
                    if ($file == "." || $file == ".." || $file == '.svn' || is_dir($file)) {
                       continue;
                    }

                    if (is_file($file) && strpos($file, $recipient .'.') !== false) {
                        $recipient_files[] = $file;
                    }
                }
                closedir($handle);
            }

            chdir($saved_working_dir);
            array_multisort($recipient_files, SORT_ASC, SORT_STRING);
            return $recipient_files;
        }
    }
?>