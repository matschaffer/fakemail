<?php
    //$Id: fakemail_test.php,v 1.1 2005/04/28 09:45:20 pachanga Exp $
    require_once(dirname(__FILE__) . '/../fakemail.php');

    class TestOfFakeMailDaemon extends UnitTestCase {
        function testSimpleLifeCycle() {
            $server = new FakeMailDaemon();

            $this->assertNull($server->getPID());

            $server->start();

            $pid = $server->getPID();
            $line = $this->_getProcessStatusLineByPID($pid);
            $this->assertWantedPattern("~^\s+{$pid}\s+~", $line);

            $server->stop();
            $this->_makeSureItStopped($pid);

            $this->assertNull($server->getPID());
        }

        function testPerlScriptDefaultOptions() {
            $server = new FakeMailDaemon();

            $server->start();

            $pid = $server->getPID();
            $line = $this->_getProcessStatusLineByPID($pid);

            $server->stop();
            $this->_makeSureItStopped($pid);

            $this->assertTrue(preg_match('~perl(.*)$~', $line, $matches));

            //is it possible to get all process command line options in cygwin?
            if(substr(php_uname(), 0, 7 ) == 'Windows') {
                return;
            }

            $this->assertWantedPattern(preg_quote(FAKE_MAIL_SCRIPT), $matches[1]);
            $this->assertWantedPattern('~--background~', $matches[1]);
            $this->assertWantedPattern('~--path=' . preg_quote(FAKE_MAIL_DUMP_PATH) . '~', $matches[1]);
            $this->assertWantedPattern('~--port=' . FAKE_MAIL_PORT . '~', $matches[1]);
            $this->assertWantedPattern('~--host=' . FAKE_MAIL_HOST . '~', $matches[1]);
            $this->assertNoUnwantedPattern('~--log=\S+~', $matches[1]);
        }

        function testPerlScriptUserOptions() {
            $server = new FakeMailDaemon($fakemail = dirname(__FILE__) . '/../../fakemail',
                                         $mail_path = 'somewhere',
                                         $port = 2525,
                                         $host = '127.0.0.1');

            mkdir($mail_path);

            $server->useLog($log = 'mail.log');
            $server->start();

            $pid = $server->getPID();
            $line = $this->_getProcessStatusLineByPID($pid);

            $server->stop();
            $this->_makeSureItStopped($pid);

            rmdir($mail_path);

            //maybe that's too much internal knowledge?
            $this->assertTrue(file_exists($log . '.' . $pid));
            unlink($log . '.' . $pid);

            $this->assertTrue(preg_match('~perl(.*)$~', $line, $matches));

            //is it possible to get all process command line options in cygwin?
            if(substr(php_uname(), 0, 7 ) == 'Windows') {
                return;
            }

            $this->assertWantedPattern(preg_quote($fakemail), $matches[1]);
            $this->assertWantedPattern('~--background~', $matches[1]);
            $this->assertWantedPattern('~--path=' . preg_quote($mail_path) . '~', $matches[1]);
            $this->assertWantedPattern('~--port=' . $port . '~', $matches[1]);
            $this->assertWantedPattern('~--host=' . $host . '~', $matches[1]);
            $this->assertWantedPattern('~--log='. preg_quote($log) . '~', $matches[1]);
        }

        function testGetRecipientZeroMailCount() {
            $server = new FakeMailDaemon();
            $this->assertEqual($server->getRecipientMailCount('somebody@dot.com'), 0);
        }

        function testGetRecipientMailCount() {
            $mail_path = dirname(__FILE__) . '/';
            $recipient = 'somebody@dot.com';

            touch($mail_path . $recipient . '.1');
            touch($mail_path . $recipient . '.2');
            touch($mail_path . 'garbage' . '.1');

            $server = new FakeMailDaemon(null, $mail_path);
            $this->assertEqual($server->getRecipientMailCount($recipient), 2);

            $server->removeAccessedRecipientsMail();

            $this->assertFalse(file_exists($mail_path . $recipient . '.1'));
            $this->assertFalse(file_exists($mail_path . $recipient . '.2'));

            $this->assertTrue(file_exists($mail_path . 'garbage' . '.1'));
            unlink($mail_path . 'garbage' . '.1');
        }

        function testGetRecipientEmptyMailContents() {
            $server = new FakeMailDaemon();
            $this->assertEqual($server->getRecipientMailContents('somebody@dot.com'), array());
        }

        function testGetRecipientMailContents() {
            $mail_path = dirname(__FILE__) . '/';
            $recipient = 'somebody@dot.com';

            $this->_writeToFile($mail_path . $recipient . '.1', $content1 = 'foo');
            $this->_writeToFile($mail_path . $recipient . '.2', $content2 = 'bar');
            $this->_writeToFile($mail_path . 'garbage' . '.1', $garbage = 'whatever');

            $server = new FakeMailDaemon(null, $mail_path);
            $this->assertEqual($server->getRecipientMailContents($recipient),
                               array($content1, $content2));

            $server->removeAccessedRecipientsMail();

            $this->assertFalse(file_exists($mail_path . $recipient . '.1'));
            $this->assertFalse(file_exists($mail_path . $recipient . '.2'));

            $this->assertTrue(file_exists($mail_path . 'garbage' . '.1'));
            unlink($mail_path . 'garbage' . '.1');
        }

        function _makeSureItStopped($pid) {
            $this->assertFalse($this->_getProcessStatusLineByPID($pid));
        }

        function _getProcessStatusLineByPID($pid) {
            $cmd = 'ps aux | grep ' . $pid;
            exec($cmd, $out);
            return isset($out[0]) ? $out[0] : false;
        }

        function _writeToFile($file, $content)
        {
            $f = fopen($file, 'w');
            fwrite($f, $content);
            fclose($f);
        }

    }

?>
