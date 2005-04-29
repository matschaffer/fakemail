<?php
    require_once('simpletest/web_tester.php');
    require_once('simpletest/reporter.php');
    
    class MailGreetingTest extends WebTestCase {
    
        function setUp() {
            $command = './fakemail --path=. --host=localhost --port=10025 --background';
            $this->pid = `$command`;
            @unlink('marcus@localhost.1');
        }
        
        function tearDown() {
            $command = 'kill ' . $this->pid;
            `$command`;
            @unlink('marcus@localhost.1');
        }
        
        function testGreetingMailIsSent() {
            $this->get('http://localhost/fakemail/docs/example/mail.php');
            $this->setField('email', 'marcus@localhost');
            $this->clickSubmit('Send', array('port' => 10025));
            $this->assertWantedText('Mail sent to marcus@localhost');
            
            $sent = file_get_contents('marcus@localhost.1');
            list($headers, $content) = split("\r\n\r\n", $sent);
            $this->assertTrue(trim($content) == 'Hi!');
        }
    }
    
    $test = new MailGreetingTest();
    $test->run(new HtmlReporter());
?>