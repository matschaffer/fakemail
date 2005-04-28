<?php
    //$Id: runtests.php,v 1.1 2005/04/28 09:45:20 pachanga Exp $
    if(!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', 'simpletest');
    }

    require_once(SIMPLE_TEST . '/unit_tester.php');
    require_once(SIMPLE_TEST . '/reporter.php');
    require_once(SIMPLE_TEST . '/mock_objects.php');

    class AllFakemailTests extends GroupTest {
        function AllFakemailTests() {
            $this->GroupTest('All tests for fakemail');
            $this->addTestFile('fakemail_test.php');
        }
    }

    $test =& new AllFakemailTests();
    if (SimpleReporter::inCli()) {
        exit ($test->run(new TextReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());

?>
