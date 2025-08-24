<?php

use PHPUnit\Framework\TestCase;

require_once 'Database.php';

class DatabaseTest extends TestCase
{

    private $db = null;

    protected function setUp(): void
    {
        $this->db = new Database();
    }

    protected function tearDown(): void
    {
        unset($this->db);
    }

    /**
     * Test that the "really long query" always returns values
     */
    public function testReallyLongReturn()
    {
        $mock = $this->createMock('Database');
        $result = array(
            array(1, 'foo', 'bar test')
        );

        $mock->expects($this->any())
            ->method('reallyLongTime')
            //->with($this->isType('array'))
            ->will($this->returnValue($result));

        $return = $mock->reallyLongTime();
        dump($return);
        $this->assertGreaterThan(0, count($return));
    }


}
