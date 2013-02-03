<?php

require_once 'vendor/autoload.php';
require_once 'lib/PHPUnit/Contextual/TestCase.php';
require_once 'vendor/phpunit/phpunit/PHPUnit/Framework/Assert/Functions.php';

class A_Sample_Test extends PHPUnit_Contextual_TestCase{
	
	public function spec(){

		$this->given("you have two numbers", function(){

			$this->set_up(function(){
				$this->a = 1;
				$this->b = 2;
			});

			$this->then('adding them results in their sum', function(){
				assertEquals(3, $this->a + $this->b);
			});

			$this->then('subtracting them results in their difference', function()
			{
				assertEquals(-1, $this->a - $this->b);
			});

			$this->then('changing a field in a test should reset', function()
			{
				$this->a = 5;
			});

			$this->then('continuing the previous test to show teardown works', function()
			{
				assertEquals(1, $this->a);
			});

			$this->given("a third number", function(){

				$this->set_up(function(){
					$this->c = 3;
				});

				$this->then("adding all three numbers should result in their sum", function(){
					assertEquals(6, $this->a + $this->b + $this->c);
				});

				$this->tear_down(function(){
					$this->c = null;
				});
			});

			$this->tear_down(function(){
				$this->a = null;
				$this->b = null;
			});
		});
	}
}


PHPUnit_TextUI_TestRunner::run(new A_Sample_Test());