<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

include_once "./CoreLogic.php";
include_once "./GeneratedCode/GeneratedCardDictionaries.php";

final class DiscloseMechanicTest extends TestCase
{
    public function setUp(): void
    {
      global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
      global $myHand, $theirHand, $mainHand, $defHand;
      global $myStateBuiltFor;

      $currentPlayer = 1;
      $mainPlayer = 1;
      $mainPlayerGamestateStillBuilt = true;
      $myStateBuiltFor = true;
    }

    public function tearDown(): void
    {
    }

    public function testCanDiscloseExact(): void
    {
        global $currentPlayer, $myHand, $mainHand;
        $myHand = ["8601222247", "8601222247"];//Secretive Sage x2
        $mainHand = $myHand;
        $this->assertTrue(PlayerCanDiscloseAspects($currentPlayer, ["Vigilance", "Vigilance"]));
    }

    public function testCanDiscloseWhenMore(): void
    {
        global $currentPlayer, $myHand, $mainHand;
        $myHand = ["7323186775", "7323186775"];//Itinerant Warrior x2
        $mainHand = $myHand;
        $this->assertTrue(PlayerCanDiscloseAspects($currentPlayer, ["Vigilance", "Heroism", "Heroism"]));
    }

    public function testCannotDiscloseWhenLess(): void
    {
        global $currentPlayer, $myHand, $mainHand;
        $myHand = ["8601222247", "8601222247"];//Secretive Sage x2
        $mainHand = $myHand;
        $this->assertFalse(PlayerCanDiscloseAspects($currentPlayer, ["Vigilance", "Vigilance", "Heroism"]));
    }
}
