<?php

namespace RZP\Tests\Unit\Models\Payment;

use RZP\Tests\Functional\TestCase;

class NotesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = new \RZP\Models\Payment\Entity;
    }

    public function testNotesSequentialArray()
    {
        $payment =  $this->payment;
        $this->payment->setNotes([1, 2, 3]);
        $notes = $payment->getNotes();
        $this->assertEquals('1', $notes->{'0'});
    }

    public function testNotesNormalArray()
    {
        $payment = $this->payment;
        $payment->setNotes([
            'opencart_order_id' => 'opencart_123'
        ]);
        $notes = $payment->getNotes();
        $this->assertEquals('opencart_123', $notes->opencart_order_id);
    }

    public function testNotesUnicode()
    {
        $payment = $this->payment;
        $payment->setNotes([
            'opencart_order_id' => 'пустынных_Sîne'
        ]);
        $notes = $payment->getNotes();
        $this->assertEquals('пустынных_Sîne', $notes->opencart_order_id);
    }


    public function testNotesUnicodeKey()
    {
        $payment = $this->payment;
        $payment->setNotes([
            'пустынных' => 'пустынных_Sîne'
        ]);
        $notes = $payment->getNotes();
        $this->assertEquals('пустынных_Sîne', $notes->{'пустынных'});
    }

    public function testArrayKeys()
    {
        $payment = $this->payment;
        $payment->setNotes([
            'пустынных' => 'пустынных_Sîne'
        ]);

        $notes = $payment->getNotes();
        $this->assertEquals(['пустынных'], $notes->getKeys());
    }

    public function testEmptyNotes()
    {
        $payment = $this->payment;
        $payment->setNotes([]);
        $notes = $payment->getNotes();
        $this->assertEquals(true, empty($notes->getArrayCopy()));
    }
}
