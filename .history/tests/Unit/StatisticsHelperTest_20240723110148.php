<?php

namespace Tests\Unit;

use App\Helpers\StatisticsHelper;
use PHPUnit\Framework\TestCase;

class StatisticsHelperTest extends TestCase
{
    public function setUp(): void
    {
        StatisticsHelper::reset();
    }
}
