<?php

namespace Tests;

use Illuminate\Support\Facades\DB;

abstract class StatisticsTestCase extends TestCase
{
    /**
     * Fail before migrations or transactions if the MySQL target is not isolated.
     */
    protected function beforeRefreshingDatabase(): void
    {
        $this->assertTrue($this->app->environment('testing'));
        $this->assertNull(config('activitylog.database_connection'));

        $connection = config('database.default');
        if ($connection === 'sqlite') {
            $this->assertSame(':memory:', config('database.connections.sqlite.database'));

            return;
        }

        $this->assertSame('mysql', $connection);
        $this->assertEmpty(config('database.connections.mysql.url'));
        $this->assertSame('mysql', config('database.connections.mysql.host'));
        $this->assertSame('refactor_statistics_test', config('database.connections.mysql.database'));
        $this->assertSame('refactor_statistics_test', DB::selectOne('SELECT DATABASE() AS name')->name);
    }

    protected function migrateDatabases(): void
    {
        $this->artisan('migrate', ['--no-interaction' => true])->assertExitCode(0);
    }
}
