<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Webpatser\LaravelUuid\HasUuids;

// Test model class for HasUuids trait tests
class TestModel extends Model
{
    use HasUuids;

    protected $table = 'test_models';

    public function testIsValidUniqueId($value): bool
    {
        return $this->isValidUniqueId($value);
    }
}
