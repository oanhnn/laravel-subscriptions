<?php

namespace Tests\Unit;

use DateTimeImmutable;
use Laravel\Subscriptions\Period;
use PHPUnit\Framework\TestCase;

class PeriodTest extends TestCase
{
    /**
     * Test it can validate interval
     */
    public function test_it_can_validate_interval()
    {
        $this->assertTrue(Period::isValidInterval('day'));
        $this->assertTrue(Period::isValidInterval('month'));
        $this->assertTrue(Period::isValidInterval('week'));
        $this->assertTrue(Period::isValidInterval('year'));

        $this->assertfalse(Period::isValidInterval(''));
        $this->assertfalse(Period::isValidInterval('date'));
        $this->assertfalse(Period::isValidInterval('minute'));
        $this->assertfalse(Period::isValidInterval('days'));
        $this->assertfalse(Period::isValidInterval('wwek'));
        $this->assertfalse(Period::isValidInterval('months'));
        $this->assertfalse(Period::isValidInterval('5'));
    }

    /**
     * Test it will return DateTimeImmutable object when call Period::getStartAt(), Period::getEndAt()
     */
    public function test_it_will_return_immutable_datetime()
    {
        $period = new Period('month', 1, now());

        $this->assertInstanceOf(DateTimeImmutable::class, $period->getStartAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $period->getEndAt());
    }

    /**
     * @param string $interval
     * @param int $count
     * @param int|string|\DateTimeInterface $start
     * @dataProvider invalidIntervalForCreatePeriod
     */
    public function test_it_will_throw_exception_when_create_with_invalid_interval(string $interval, int $count, $start)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('#Interval unit `(.*)` is invalid#');

        new Period($interval, $count, $start);
    }

    /**
     * @return array
     */
    public function invalidIntervalForCreatePeriod()
    {
        return [
            ['', 1, ''],
            ['date', 1, ''],
            ['minute', 1, ''],
            ['days', 1, ''],
            ['wwek', 1, ''],
            ['months', 2, ''],
        ];
    }
}
