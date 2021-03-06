<?php
/**
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @version //autogentag//
 * @filesource
 * @package SignalSlot
 * @subpackage Tests
 */

require_once( "test_classes.php" );

/**
 * @package SignalSlot
 * @subpackage Tests
 */
class ezcSignalStaticConnectionsTest extends ezcTestCase
{
    private $giver;
    private $receiver;

    protected function setUp()
    {
        $this->giver = new TheGiver();
        $this->receiver = new TheReceiver();
        TheReceiver::$globalFunctionRun = false;
        TheReceiver::$staticFunctionRun = false;
    }

    function tearDown()
    {
        ezcSignalStaticConnections::getInstance()->connections = array();
    }

    public function testSingleConnectionGlobalFunction()
    {
        ezcSignalStaticConnections::getInstance()->connect( 'TheGiver', 'signal', 'slotFunction' );
        $this->giver->signals->emit( "signal" );
        $this->assertEquals( "brain damage", TheReceiver::$globalFunctionRun );
    }

    public function testSingleConnectionStaticFunction()
    {
        ezcSignalStaticConnections::getInstance()->connect( 'TheGiver', 'signal', array( "TheReceiver", "slotStatic" ) );
        $this->giver->signals->emit( "signal" );
        $this->assertEquals( "have a cigar", TheReceiver::$staticFunctionRun );
    }

    public function testSingleConnectionMemberFunction()
    {
        ezcSignalStaticConnections::getInstance()->connect( 'TheGiver', 'signal', array( $this->receiver, "slotNoParams1" ) );
        $this->giver->signals->emit( "signal" );
        $this->assertEquals( array( "slotNoParams1" ), $this->receiver->stack );
    }

    public function testTwoSameSignalsInDifferentClasses()
    {
        $giver2 = new TheGiver();
        ezcSignalStaticConnections::getInstance()->connect( 'TheGiver', 'signal', array( $this->receiver, "slotNoParams1" ) );
        $this->giver->signals->emit( "signal" );
        $giver2->signals->emit( "signal" );
        $this->assertEquals( array( "slotNoParams1", "slotNoParams1" ), $this->receiver->stack );
    }

    public function testTwoSameSignalNameButDifferentIdentifier()
    {
        $giver2 = new TheGiver( "TheGiver2" );
        ezcSignalStaticConnections::getInstance()->connect( 'TheGiver', 'signal', array( $this->receiver, "slotNoParams1" ) );
        $this->giver->signals->emit( "signal" );
        $giver2->signals->emit( "signal" );
        $this->assertEquals( array( "slotNoParams1" ), $this->receiver->stack );
    }

    public function testAdvancedPriorityStaticConnectionsOnly()
    {
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver","signal",
                                                            array( $this->receiver, "slotNoParams2" ), 1001 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal",
                                                            array( $this->receiver, "slotNoParams5" ), 9999 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal",
                                                            array( $this->receiver, "slotNoParams3" ) );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal",
                                                            array( $this->receiver, "slotNoParams1" ), 1 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal",
                                                            array( $this->receiver, "slotNoParams4" ), 999 );

        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal2",
                                                            array( $this->receiver, "slotNoParams1" ), 1001 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal2",
                                                            array( $this->receiver, "slotNoParams2" ), 9999 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal2",
                                                            array( $this->receiver, "slotNoParams4" ) );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal2",
                                                            array( $this->receiver, "slotNoParams3" ), 1 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal2",
                                                            array( $this->receiver, "slotNoParams5" ), 999 );

        $this->giver->signals->emit( "signal" );
        $this->giver->signals->emit( "signal2" );
        $this->assertEquals( array( "slotNoParams1", "slotNoParams4", "slotNoParams3", "slotNoParams2", "slotNoParams5",
                                    "slotNoParams3", "slotNoParams5", "slotNoParams4", "slotNoParams1", "slotNoParams2"),
                             $this->receiver->stack );
    }

    /*
     * Sort critirea:
     * 1. Priority
     * 2. Dynamic over static
     * 3. Connection order
     */
    public function testAdvancedPriorityStaticAndNormalConnectionsMixed()
    {
        $giver2 = new TheGiver();
        $this->giver->signals->connect( "signal", array( $this->receiver, "slotNoParams2" ) );
        $this->giver->signals->connect( "signal", array( $this->receiver, "slotNoParams1" ), 998 );
        $this->giver->signals->connect( "signal", array( $this->receiver, "slotNoParams3" ), 999 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver","signal",
                                                            array( $this->receiver, "slotNoParams2" ), 1001 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal",
                                                            array( $this->receiver, "slotNoParams5" ), 9999 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal",
                                                            array( $this->receiver, "slotNoParams3" ) );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal",
                                                            array( $this->receiver, "slotNoParams1" ), 1 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal",
                                                            array( $this->receiver, "slotNoParams4" ), 999 );

        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal2",
                                                            array( $this->receiver, "slotNoParams1" ), 1001 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal2",
                                                            array( $this->receiver, "slotNoParams2" ), 9999 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal2",
                                                            array( $this->receiver, "slotNoParams4" ) );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal2",
                                                            array( $this->receiver, "slotNoParams3" ), 1 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal2",
                                                            array( $this->receiver, "slotNoParams5" ), 999 );

        $this->giver->signals->emit( "signal" );
        $giver2->signals->emit( "signal" );
        $this->giver->signals->emit( "signal2" );
        $this->assertEquals( array( "slotNoParams1", "slotNoParams1", "slotNoParams3" ,"slotNoParams4", "slotNoParams2", "slotNoParams3", "slotNoParams2", "slotNoParams5",
                                    "slotNoParams1", "slotNoParams4", "slotNoParams3", "slotNoParams2", "slotNoParams5",
                                    "slotNoParams3", "slotNoParams5", "slotNoParams4", "slotNoParams1", "slotNoParams2"),
                             $this->receiver->stack );
    }

    public function testAdvancedDisconnectNoPriority()
    {
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams2" ), 5000 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ) );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams1" ), 10 );

        ezcSignalStaticConnections::getInstance()->disconnect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ) );
        $this->giver->signals->emit( "signal" );
        $this->assertEquals( array( "slotNoParams1", "slotNoParams2" ), $this->receiver->stack );
    }

    public function testAdvancedDisconnectNoPrioritySeveralConnections()
    {
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams2" ), 5000 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ) );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ), 1 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams1" ), 10 );


        ezcSignalStaticConnections::getInstance()->disconnect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ) );
        $this->giver->signals->emit( "signal" );
        $this->assertEquals( array( "slotNoParams3", "slotNoParams1", "slotNoParams2" ), $this->receiver->stack );
    }

    public function testAdvancedDisconnectNoPrioritySeveralConnections2()
    {
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams2" ), 5000 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ) );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ), 5001 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams1" ), 10 );

        ezcSignalStaticConnections::getInstance()->disconnect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ) );
        $this->giver->signals->emit( "signal" );
        $this->assertEquals( array( "slotNoParams1", "slotNoParams3", "slotNoParams2" ), $this->receiver->stack );
    }


    public function testAdvancedDisconnectPriority()
    {
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams2" ), 5000 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ) );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams1" ), 10 );

        ezcSignalStaticConnections::getInstance()->disconnect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ), 1000 );
        $this->giver->signals->emit( "signal" );
        $this->assertEquals( array( "slotNoParams1", "slotNoParams2" ), $this->receiver->stack );
    }

    public function testAdvancedDisconnectPrioritySeveralConnections()
    {
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams2" ), 5000 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ) );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ), 1 );
        ezcSignalStaticConnections::getInstance()->connect( "TheGiver", "signal", array( $this->receiver, "slotNoParams1" ), 10 );


        ezcSignalStaticConnections::getInstance()->disconnect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ) );
        $this->giver->signals->emit( "signal" );
        $this->assertEquals( array( "slotNoParams3", "slotNoParams1", "slotNoParams2" ), $this->receiver->stack );
    }

    public function testDisconnectEmpty()
    {
        ezcSignalStaticConnections::getInstance()->disconnect( "TheGiver", "signal", array( $this->receiver, "slotNoParams3" ), 1000 );
        $this->assertEquals( 0, count( ezcSignalStaticConnections::getInstance()->connections ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcSignalStaticConnectionsTest" );
    }
}
?>
