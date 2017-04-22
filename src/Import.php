<?php

namespace RoundPartner\ICal\Import;

use ICal\EventObject;
use ICal\ICal;

class Import
{

    /**
     * @var ICal
     */
    private $ical;

    /**
     * @var Customers
     */
    private $customers;

    public function __construct($filename)
    {
        $this->ical = new ICal($filename);
        $this->customers = new Customers();
    }

    /**
     * @return EventObjects[]
     */
    public function getItems()
    {
        return $this->ical->events();
    }

    public function getSummaries()
    {
        $summaries = [];
        $items = $this->getItems();
        foreach ($items as $key => $item) {
            $summaries[$item->summary][] = $this->itemToArray($item);
        }
        return $summaries;
    }

    /**
     * @param EventObject $item
     *
     * @return array
     */
    public function itemToArray($item)
    {
        return [
            'summary' => $item->summary,
            'description' => $item->description,
            'location' => $item->location,
            'date' => new \DateTime($item->dtstart),
            'end_date' => new \DateTime($item->dtend),
            'sequence' => $item->sequence,
            'rrule' => isset($item->rrule) ? $item->rrule : null,
            'uid' => $item->uid,
        ];
    }

    /**
     * @return Customers
     */
    public function getCustomers()
    {
        $items = $this->getItems();
        foreach ($items as $key => $item) {
            $this->addCustomer($item);
        }
        return $this->customers;
    }

    /**
     * @param EventObjects $item
     */
    public function addCustomer($item)
    {
        $this->customers->addCustomer($item);
    }
}

class Customers
{
    /**
     * @var Customer[]
     */
    private $customers;

    public function __construct()
    {
        $this->customers = array();
    }

    /**
     * @param EventObjects $item
     */
    public function addCustomer($item)
    {
        if (!array_key_exists($item->summary, $this->customers)) {
            $this->customers[$item->summary] = new Customer($item->summary);
        }
        $this->customers[$item->summary]->addAddress($item);
    }

    /**
     * @return Customer[]
     */
    public function getCustomers()
    {
        return $this->customers;
    }
}

class Customer
{
    public $name;
    /**
     * @var Address[]
     */
    public $addresses;

    public function __construct($name)
    {
        $this->addresses = array();
        $this->name = trim($name);
    }

    /**
     * @param EventObjects $item
     */
    public function addAddress($item)
    {
        if (!array_key_exists($item->location, $this->addresses)) {
            $this->addresses[$item->location] = new Address($item->location);
        }
        $this->addresses[$item->location]->addJob($item);
    }

    /**
     * @return Address[]
     */
    public function getAddresses()
    {
        return $this->addresses;
    }
}

class Address
{

    public $address;

    /**
     * @var Job[]
     */
    public $jobs;

    public function __construct($address)
    {
        $this->address = trim($address);
        $this->jobs = array();
    }

    /**
     * @param EventObjects $item
     */
    public function addJob($item)
    {
        if (!array_key_exists($item->description, $this->jobs)) {
            $this->jobs[$item->description] = new Job($item->description, isset($item->rrule) ? $item->rrule : null);
        }
        $this->jobs[$item->description]->addBooking($item);
    }

    /**
     * @return Job[]
     */
    public function getJobs()
    {
        return $this->jobs;
    }
}

class Job
{
    public $job;
    public $rule;
    public $interval_type;
    public $interval;
    public $until;
    /**
     * @var Booking[]
     */
    public $bookings;

    public function __construct($job, $rule)
    {
        $this->job = trim($job);
        $this->rule = $rule;
        $this->bookings = array();

        $this->setFrequency($rule);
    }

    public function setFrequency($rule)
    {
        if (null === $rule) {
            $this->interval_type = 'day';
            $this->interval = 0;
            return true;
        }
        $matches = false;
        if (preg_match('/(FREQ=(?P<freq>[A-Z]+))(;UNTIL=(?P<until>[0-9TZ]+))?(;INTERVAL=(?P<interval>[0-9]+))?(;BYMONTHDAY=(?P<bymonthday>[0-9]+))?/', $rule, $matches)) {
            $this->interval_type = $this->getType($matches);
            $this->interval = $this->getInterval($matches);
            if (array_key_exists('until', $matches)) {
                $this->until = new \DateTime($matches['until']);
            }
            return true;
        }
        return false;
    }

    public function getType($matches)
    {
        if (array_key_exists('freq', $matches)) {
            switch ($matches['freq']) {
                case 'MONTHLY':
                    return 'month';
                case 'WEEKLY':
                    return 'week';
                case 'DAILY':
                    return 'day';
            }
        }
        return 'day';
    }

    public function getInterval($matches)
    {
        if (array_key_exists('interval', $matches)) {
            return $matches['interval'];
        }
        return 1;
    }

    /**
     * @param EventObjects $item
     */
    public function addBooking($item)
    {
        if (!array_key_exists($item->dtstart, $this->bookings)) {
            $this->bookings[$item->dtstart] = new Booking($item->uid, $item->dtstart);
        }
    }

    /**
     * @return Booking[]
     */
    public function getBookings()
    {
        if (count($this->bookings)) {
            if ($this->until > new \DateTime()) {
                $this->bookings[count($this->bookings)-1]->completed = 0;
            }
        }

        return $this->bookings;
    }
}

class Booking
{
    public $date;
    public $id;
    public $completed;

    public function __construct($id, $date)
    {
        $this->id = $id;
        $this->date = new \DateTime($date);
        $this->completed = $this->date < new \DateTime();
    }
}
