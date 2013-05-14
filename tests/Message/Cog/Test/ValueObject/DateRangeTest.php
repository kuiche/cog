<?php

namespace Message\Cog\Test\ValueObjects;

use Message\Cog\ValueObject\DateRange;
use DateTime;

class DateRangeTest extends \PHPUnit_Framework_TestCase
{

	public function testIsInRange()
	{
		// Set the to time
		$to = DateTime::createFromFormat('d/m/Y H:i:s', '15/05/2013 00:00:00');

		$from = DateTime::createFromFormat('d/m/Y H:i:s', '01/05/2013 00:00:00');

		$dateRange = new DateRange($from, $to);
		
		// Set test datetime as today
		$testDateRange = DateTime::createFromFormat('d/m/Y H:i:s', '14/05/2013 00:00:00');

		// Check that today is before tomorrow
		$this->assertTrue($dateRange->isInRange($testDateRange));
		
		// Set date to more than the to date
		$testDateRange = DateTime::createFromFormat('d/m/Y H:i:s', '16/05/2013 00:00:01');
		
		// Should return false
		$this->assertFalse($dateRange->isInRange($testDateRange));		
		$to = new DateTime;
		$to->setTimestamp(strtotime('+1 day'));
		
		$from = new DateTime;
		$from->setTimestamp(strtotime('-1 day'));
		
		$dateRange = new DateRange($from, $to);
		
		// Pass through null, should use current datetime
		$this->assertTrue($dateRange->isInRange());
		
		// Check for differnet times
		$to = DateTime::createFromFormat('d/m/Y H:i:s', '15/05/2013 09:13:52');
		
		$from = DateTime::createFromFormat('d/m/Y H:i:s', '15/05/2013 18:13:22');
		
		$dateRange = new DateRange($from, $to);

		// Check that the different times work as expected
		// Should return true
		$dateRange->assertTrue($dateRange->isInRange(DateTime::createFromFormat('d/m/Y H:i:s', '15/05/2013 10:13:22')));
		
		// Should return false
		$dateRange->assertFalse($dateRange->isInRange(DateTime::createFromFormat('d/m/Y H:i:s', '15/05/2013 09:59:99')));
				
	}
	
	public function testGetIntervalToEnd()
	{
		
	}

	public function getIntervalToStart()
	{
		
	}
}