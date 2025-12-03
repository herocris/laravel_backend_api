<?php

declare(strict_types=1);

namespace Tests\Unit\Activitylog;

use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\LogOptions;
use Tests\ActivitylogProxy;
use Tests\UnitTestCase;

class RecordLogTest extends UnitTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        Auth::shouldReceive('user')
            ->once()
            ->andReturn((object)['name' => 'Test User']);
    }

    #[Test]
    public function it_returns_logoptions_instance(): void
    {
        $proxy = new ActivitylogProxy();
        $result = $proxy->callRecordLog(['name'], 'ammunition');
        $this->assertInstanceOf(LogOptions::class, $result);
    }

    #[Test]
    public function it_sets_log_only_fields(): void
    {
        $fields = ['description', 'logo'];
        $proxy = new ActivitylogProxy();
        $result = $proxy->callRecordLog($fields, 'ammunition');
        // logOnly se guarda internamente en protected property
        $this->assertEquals($fields, $result->logAttributes);
    }

    #[Test]
    public function it_sets_log_only_dirty_flag(): void
    {
        $proxy = new ActivitylogProxy();
        $result = $proxy->callRecordLog(['field'], 'ammunition');
        $this->assertTrue($result->logOnlyDirty);
    }

    #[Test]
    public function it_sets_correct_description_for_events(): void
    {
        $proxy = new ActivitylogProxy();
        $result = $proxy->callRecordLog(['name'], 'ammunition');
        $callable = $result->descriptionForEvent;
        $this->assertIsCallable($callable);
        // Spatie enviará "created", "updated", "deleted", etc.
        $this->assertEquals(
            'Se ha creado el ammunition',
            $callable('created')
        );

        $this->assertEquals(
            'Se ha borrado el ammunition',
            $callable('deleted')
        );
    }
}
