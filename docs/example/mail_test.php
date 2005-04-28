<?php
    require_once('simpletest/web_tester.php');
    require_once('simpletest/reporter.php');
    
    class MailGreetingTest extends WebTestCase {
    
        function setUp() {
            $command = 'fakemail --path=temp --host=localhost --port=10025';
            $this->pid = `$command`;
            @unlink('temp/marcus@lastcraft.com.1');
        }
        
        function tearDown() {
            $command = 'kill ' . $this->pid;
            `$command`;
            @unlink('temp/marcus@lastcraft.com.1');
        }
        
        function testGreetingMailIsSent() {
            $this->get('http://my-host/mail.php');
            $this->setField('email', 'marcus@lastcraft.com');
            $this->clickSubmit('Send', array('port' => 10025));
            $this->assertWantedText('mail sent to marcus@lastcraft.com');
            
            $sent = file_get_contents('temp/marcus@lastcraft.com.1');
            list($headers, $content) = split("\r\n\r\n", $sent);
            $this->assertTrue(trim($content) == 'Hello');
        }
    }
    
    $test = new MailGreetingTest();
    $test->run(new HtmlReporter());
?>