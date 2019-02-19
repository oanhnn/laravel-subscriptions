<?php

namespace Laravel\Subscriptions;

use Carbon\Carbon;
use DateTimeInterface;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Class Period
 *
 * @package     Laravel\Subscriptions
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 */
class Period
{
    /**
     * The interval constants.
     */
    const DAY = 'day';
    const WEEK = 'week';
    const MONTH = 'month';
    const YEAR = 'year';

    /**
     * Map Interval to Carbon methods.
     *
     * @var array
     */
    protected static $methodMapping = [
        self::DAY => 'addDays',
        self::WEEK => 'addWeeks',
        self::MONTH => 'addMonths',
        self::YEAR => 'addYears',
    ];

    /**
     * Starting date of the period.
     *
     * @var \Carbon\Carbon
     */
    protected $start;

    /**
     * Ending date of the period.
     *
     * @var \Carbon\Carbon
     */
    protected $end;

    /**
     * Interval.
     *
     * @var string
     */
    protected $interval;

    /**
     * Interval count.
     *
     * @var int
     */
    protected $period = 1;

    /**
     * Create a new Period instance.
     *
     * @param  string $interval
     * @param  int $count
     * @param  null|string|int|\DateTimeInterface $start
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct($interval = 'month', $count = 1, $start = '')
    {
        if ($start instanceof DateTimeInterface) {
            $this->start = Carbon::instance($start);
        } elseif (is_int($start)) {
            $this->start = Carbon::createFromTimestamp($start);
        } elseif (empty($startAt)) {
            $this->start = Carbon::now();
        } else {
            $this->start = Carbon::parse($start);
        }

        if (!isset(self::$methodMapping[$interval])) {
            throw new InvalidArgumentException("Interval unit `{$interval}` is invalid");
        }

        $this->interval = $interval;

        if ($count > 0) {
            $this->period = $count;
        }

        $start = clone $this->start;
        $method = self::$methodMapping[$interval];
        $this->end = $start->{$method}($this->period);
    }

    /**
     * Get start date.
     *
     * @return \DateTimeImmutable
     */
    public function getStartAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->start);
    }

    /**
     * Get end date.
     *
     * @return \DateTimeImmutable
     */
    public function getEndAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->end);
    }

    /**
     * Get period interval.
     *
     * @return string
     */
    public function getInterval(): string
    {
        return $this->interval;
    }

    /**
     * Get period interval count.
     *
     * @return int
     */
    public function getIntervalCount(): int
    {
        return $this->period;
    }

    /**
     * Check if a given interval is valid.
     *
     * @param  string $interval
     * @return bool
     */
    public static function isValidInterval(string $interval): bool
    {
        return isset(self::$methodMapping[$interval]);
    }
}
