<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Helpers\StatisticsHelper;

class StatisticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        StatisticsHelper::reset();
    }

    public function test_get_statistics(): void
    {
        StatisticsHelper::addTransaction(100.50, Carbon::now()->subSeconds(10));
        StatisticsHelper::addTransaction(200.75, Carbon::now()->subSeconds(20));
        StatisticsHelper::addTransaction(300.25, Carbon::now()->subSeconds(30));

        $response = $this->getJson('/api/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'sum' => 601.50,
                     'avg' => 200.50,
                     'max' => 300.25,
                     'min' => 100.50,
                     'count' => 3
                 ]);
    }

    public function test_get_statistics_after_cleanup(): void
    {
        StatisticsHelper::addTransaction(100.50, Carbon::now()->subSeconds(61));
        StatisticsHelper::addTransaction(200.75, Carbon::now()->subSeconds(50));
        StatisticsHelper::addTransaction(300.25, Carbon::now()->subSeconds(30));

        $response = $this->getJson('/api/statistics');

        $response->assertStatus(200)
                 ->assertJson([
                     'sum' => 501.00,
                     'avg' => 250.50,
                     'max' => 300.25,
                     'min' => 200.75,
                     'count' => 2
                 ]);
    }
}
