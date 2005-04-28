<?php
    require_once('simpletest/web_tester.php');
    require_once('simpletest/reporter.php');
    
    class MailGreetingTest extends WebTestCase {
    
        function setUp() {
            $command = 'fakemail --path=. --host=localhost --port=10025 --background --log=log';
            $this->pid = `$command`;
            @unlink('temp/marcus@lastcraft.com.1');
        }
        
        function tearDown() {
            $command = 'kill ' . $this->pid;
            `$command`;
            //@unlink('temp/marcus@lastcraft.com.1');
        }
        
        function testGreetingMailIsSent() {
            $this->get('http://localhost/fakemail/docs/example/mail.php');
            $this->setField('email', 'marcus@lastcraft.com');
            $this->clickSubmit('Send', array('port' => 10025));
            $this->assertWantedText('Mail sent to marcus@lastcraft.com');
            $this->showSource();
            
            $sent = file_get_contents('marcus@lastcraft.com.1');
            list($headers, $content) = split("\r\n\r\n", $sent);
            $this->assertTrue(trim($content) == 'Hello');
        }
    }
    
    $test = new MailGreetingTest();
    $test->run(new HtmlReporter());
?>