<?php

class ImportTest extends PHPUnit_Framework_TestCase
{

    public $filename;

    public function setUp()
    {
        $this->filename = dirname(__DIR__) . '/../test2.ics';
    }

    public function testImportCreateInstance()
    {
        $import = new \RoundPartner\ICal\Import\Import(false);
        $this->assertInstanceOf('\RoundPartner\ICal\Import\Import', $import);
    }

    public function testGetItems()
    {
        $import = new \RoundPartner\ICal\Import\Import($this->filename);
        $items = $import->getItems();
        $this->assertContainsOnlyInstancesOf('\ICal\EventObject', $items);
    }

    public function testGetSummaries()
    {
        $import = new \RoundPartner\ICal\Import\Import($this->filename);
        $summaries = $import->getSummaries();
        $this->assertInternalType('array', $summaries);
    }

    public function testGetCustomers()
    {
        $import = new \RoundPartner\ICal\Import\Import($this->filename);
        $customers = $import->getCustomers();
        $this->assertInstanceOf('\RoundPartner\ICal\Import\Customers', $customers);
    }
}
