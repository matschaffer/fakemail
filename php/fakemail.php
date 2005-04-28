<?php
    /**
     *	simple PHP class that incapsulates fakemail server stop, start and some utility routines
     *	@package	FakeMail
     *	@version	$Id: fakemail.php,v 1.2 2005/04/28 09:45:20 pachanga Exp $
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

        /**
         *    Constructs FakeMailDaemon
         *    @param string $fakemail   Path to fakemail perl script, FAKE_MAIL_SCRIPT constant
         *                              used if not set
         *    @param string $mail_path  Path to directory where received mail should be stored,
         *                              FAKE_MAIL_DUMP_PATH constant used if not set
         *    @param string $port       Port which FakeMailDaemon should listen to,
         *                              FAKE_MAIL_PORT constant used if not set
         *    @param string $host       Host of FakeMailDaemon,
         *                              FAKE_MAIL_HOST constant used if not set
         *    @access public
         */
        function FakeMailDaemon($fakemail = null, $mail_path = null, $port = null, $host = null) {
            $this->fakemail = is_null($fakemail) ?  FAKE_MAIL_SCRIPT : $fakemail;
            $this->mail_path = is_null($mail_path) ?  FAKE_MAIL_DUMP_PATH : $mail_path;
            $this->port = is_null($port) ?  FAKE_MAIL_PORT : $port;
            $this->host = is_null($host) ?  FAKE_MAIL_HOST : $host;
        }

        /**
         *    Returns pid of fakemail perl process
         *    @return int   Null if not started
         *    @access public
         */
        function getPID() {
            return $this->pid;
        }

        /**
         *    Enables logging of all system events to the specified file
         *    @param string $log_path   Path to log file, if not specifed uses fakemail.log
         *                              placed in received mail directory. Please note that fakemail
         *                              also appends pid to the specified log file path.
         *                              This method should be called before starting FakeMailDaemon
         *    @access public
         */
        function useLog($log_path = null) {
            $this->log_path = is_null($log_path) ?  $this->mail_path . '/fakemail.log' : $log_path;
        }

        /**
         *    Starts the fakemail background process
         *    @access public
         */
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

        /**
         *    Stops the fakemail background process
         *    @access public
         */
        function stop() {
            if($this->pid) {
                exec("kill {$this->pid}");
                $this->pid = null;
            }
        }

        /**
         *    Removes all mail for the specified recipient
         *    @param string $recipient  mail address of a recipient
         *    @access public
         */
        function removeRecipientMail($recipient) {
            $names = $this->_getRecipientFileNames($recipient);

            foreach($names as $name) {
                @unlink($this->mail_path .'/'. $name);
            }
        }

        /**
         *    Removes all accessed mail, convenient for use in xUnit tearDown() method
         *    @see getRecipientMailCount(), getRecipientMailContents()
         *    @access public
         */
        function removeAccessedRecipientsMail() {
            foreach(array_keys($this->accessed_recipients) as $recipient) {
                $this->removeRecipientMail($recipient);
            }
        }

        /**
         *    Retrieves an amount of mail files for the specified recipient, marks
         *    all mail for the specified recipient as accessed
         *    @see removeAccessedRecipientsMail()
         *    @return int   amount of mail files
         *    @param string $recipient mail address of a recipient
         *    @access public
         */
        function getRecipientMailCount($recipient) {
            $this->_markRecipientAccessed($recipient);

            return count($this->_getRecipientFileNames($recipient));
        }

        /**
         *    Retrieves an array of all mail contents for the specified recipient, marks
         *    all mail for the specified recipient as accessed
         *    @see removeAccessedRecipientsMail()
         *    @return mixed   array of contents of mail files
         *    @param string $recipient mail address of a recipient
         *    @access public
         */
        function getRecipientMailContents($recipient) {
            $this->_markRecipientAccessed($recipient);

            $contents = array();
            $names = $this->_getRecipientFileNames($recipient);
            foreach($names as $name) {
                $contents[] = file_get_contents($this->mail_path .'/'. $name);
            }
            return $contents;
        }

        /**
         *    Marks all mail for the specified recipient as accessed
         *    @see removeAccessedRecipientsMail()
         *    @param string $recipient mail address of a recipient
         *    @access protected
         */
        function _markRecipientAccessed($recipient) {
            $this->accessed_recipients[$recipient] = 1;
        }

        /**
         *    Retrives names of all mail files for the specified recipient in alphabetic order
         *    @param string $recipient mail address of a recipient
         *    @return mixed   array of names of mail files
         *    @access protected
         */
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
                    if (is_file($file) && strpos($file, $recipient . '.') !== false) {
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