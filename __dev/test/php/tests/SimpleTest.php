<?php
use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase {
	/** @test **/
	public function my_simple_test() {
		$this->assertEquals(true, true);
	}
}
