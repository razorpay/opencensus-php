<?php

namespace RZP\Tests\Unit\Upi;

use RZP\Models\Payment;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Vpa as VpaTrait;

class VpaTraitTest extends TestCase
{
    use VpaTrait;

    /**
     * @var Upi mindgate shared Terminal
     */
    protected $mindgateTerminal;

    /**
     * @var Upi sbi  shared Terminal
     */
    protected $sbiTerminal;

    /**
     * @var Upi icici shared Terminal
     */
    protected $iciciTerminal;

    /**
     * @var Upi mindgate direct Terminal
     */
    protected $mindgateDirectTerminal;

    /**
     * Number of gateways used to validate vpa
     */
    const NUMBER_OF_GATEWAYS = 3;

    /**
     * @var array
     */
    protected $terminalIds;

    protected function setUp(): void
    {
        parent::setUp();

        // Create 1 icici, 1 sbi and 2 mindgate terminals
        $this->sbiTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_sbi_terminal');

        $this->mindgateTerminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->iciciTerminal = $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $this->mindgateDirectTerminal = $this->fixtures->create('terminal:direct_settlement_upi_mindgate_terminal');

        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->enableMethod(Account::DEMO_ACCOUNT, Method::UPI);

        // Create the array of terminal ids used to validate vpa
        $this->terminalIds = array(
            $this->sbiTerminal->getId(),
            $this->mindgateTerminal->getId(),
            $this->mindgateDirectTerminal->getId(),
            $this->iciciTerminal->getId()
        );
    }

    /**
     * Test filtering when all the terminals are enabled
     */
    public function testTerminalFilteringForValidateVpaAllEnabled()
    {
        $terminals = $this->filterTerminalsForValidateVpa($this->terminalIds);

        $this->assertCount(self::NUMBER_OF_GATEWAYS, $terminals);

        $this->assertEquals([0, 1, 2], array_keys($terminals->toArray()));

        $this->assertContains($this->sbiTerminal->getId(), $terminals->getIds());

        $this->assertContains($this->iciciTerminal->getId(), $terminals->getIds());

        $mindgateTerminals = $terminals->filter(function ($terminal) {
            return ($terminal->getGateway() === Payment\Gateway::UPI_MINDGATE);
        });

        $this->assertNotNull($mindgateTerminals);

        $this->assertCount(1, $mindgateTerminals);
    }

    /**
     * Test filtering with one Mindgate terminal disabled
     */
    public function testTerminalFilteringForValidateVpaOneMindgateDisabled()
    {
        // assert that the count of test terminals is 4
        $this->assertCount(4, $this->terminalIds);

        // disable mindgate shared terminal
        $this->fixtures->edit('terminal', $this->mindgateTerminal->getId(), ['enabled' => false]);

        $terminals = $this->filterTerminalsForValidateVpa($this->terminalIds);

        $this->assertCount(self::NUMBER_OF_GATEWAYS, $terminals);

        $this->assertContains($this->sbiTerminal->getId(), $terminals->getIds());

        $this->assertContains($this->iciciTerminal->getId(), $terminals->getIds());

        $mindgateTerminals = $terminals->filter(function ($terminal) {
            return ($terminal->getGateway() === Payment\Gateway::UPI_MINDGATE);
        });

        $this->assertNotNull($mindgateTerminals);

        $this->assertCount(1, $mindgateTerminals);

        $this->assertContains($this->mindgateDirectTerminal->getId(), $mindgateTerminals->getIds());
    }

    /**
     * Test filtering with Sbi terminal disabled (covers the case for icici terminal disabled as well)
     */
    public function testTerminalFilteringForValidateVpaSbiDisabled()
    {
        // disable sbi terminal
        $this->fixtures->edit('terminal', $this->sbiTerminal->getId(), ['enabled' => false]);

        $terminals = $this->filterTerminalsForValidateVpa($this->terminalIds);

        $this->assertCount(self::NUMBER_OF_GATEWAYS - 1, $terminals);

        $this->assertNotContains($this->sbiTerminal->getId(), $terminals->getIds());

        $this->assertContains($this->iciciTerminal->getId(), $terminals->getIds());

        $mindgateTerminals = $terminals->filter(function ($terminal) {
            return ($terminal->getGateway() === Payment\Gateway::UPI_MINDGATE);
        });

        $this->assertNotNull($mindgateTerminals);

        $this->assertCount(1, $mindgateTerminals);
    }

    /**
     * Tests the filtering of terminals in given order
     */
    public function testTerminalSortingForValidateVpa()
    {
        // assert that the count of test terminals is 4
        $this->assertCount(4, $this->terminalIds);

        // Swap the position of icici and sbi terminal
        [$this->terminalIds[0], $this->terminalIds[3]] = [$this->terminalIds[3], $this->terminalIds[0]];

        $terminals = $this->filterTerminalsForValidateVpa($this->terminalIds);

        $this->assertCount(self::NUMBER_OF_GATEWAYS, $terminals);

        $finalTerminals = array_values($terminals->getIds());

        // Check if Sbi terminal is at the last
        $this->assertEquals($this->sbiTerminal->getId(), $finalTerminals[2]);

        // Check if the icici is first
        $this->assertEquals($this->iciciTerminal->getId(), $finalTerminals[0]);
    }
}
