<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Webpatser\LaravelUuid\BinaryUuidCast;
use Webpatser\LaravelUuid\BinaryUuidMigrations;
use Webpatser\LaravelUuid\HasBinaryUuids;
use Webpatser\Uuid\Uuid;

// Test model using binary UUIDs with cast
class BinaryUuidTestModel extends Model
{
    use HasBinaryUuids;

    protected $table = 'binary_uuid_test_models';

    protected $fillable = ['name', 'parent_id'];

    protected $casts = [
        'id' => BinaryUuidCast::class,
        'parent_id' => BinaryUuidCast::class,
    ];

    public $timestamps = false;
}

// Test model using binary UUIDs WITHOUT cast (for testing trait methods directly)
class BinaryUuidRawModel extends Model
{
    use HasBinaryUuids;

    protected $table = 'binary_uuid_raw_models';

    public $timestamps = false;

    public function testIsValidUniqueId(mixed $value): bool
    {
        return $this->isValidUniqueId($value);
    }
}

beforeEach(function () {
    \Webpatser\LaravelUuid\UuidMacros::register();
});

it('supports binary uuid str macros', function () {
    $binaryUuid = Str::fastBinaryUuid();
    expect(strlen($binaryUuid))->toBe(16);

    $orderedBinary = Str::fastBinaryOrderedUuid();
    expect(strlen($orderedBinary))->toBe(16);

    $stringUuid = Str::binaryToUuid($binaryUuid);
    expect(Str::fastIsUuid($stringUuid))->toBeTrue()
        ->and($stringUuid)->toHaveLength(36);

    $convertedBinary = Str::uuidToBinary($stringUuid);
    expect($convertedBinary)->toBe($binaryUuid);

    expect(Str::isValidBinaryUuid($binaryUuid))->toBeTrue()
        ->and(Str::isValidBinaryUuid('invalid'))->toBeFalse()
        ->and(Str::isValidBinaryUuid(str_repeat('x', 15)))->toBeFalse();
});

it('provides binary uuid helper methods on model', function () {
    $model = new BinaryUuidTestModel(['name' => 'Test']);

    $randomBinary = $model->newRandomBinaryUuid();
    expect(strlen($randomBinary))->toBe(16)
        ->and(Uuid::import($randomBinary)->version)->toBe(4);

    $orderedBinary = $model->newOrderedBinaryUuid();
    expect(strlen($orderedBinary))->toBe(16)
        ->and(Uuid::import($orderedBinary)->version)->toBe(7);

    $timeBasedBinary = $model->newTimeBasedBinaryUuid();
    expect(strlen($timeBasedBinary))->toBe(16)
        ->and(Uuid::import($timeBasedBinary)->version)->toBe(1);
});

it('generates migration helpers', function () {
    $stub = BinaryUuidMigrations::getMigrationStub('test_table', [
        'user_id' => ['nullable' => true],
        'category_id' => ['nullable' => false],
    ]);

    expect($stub)->toContain('test_table')
        ->toContain('addBinaryUuidPrimary')
        ->toContain('user_id')
        ->toContain('category_id');

    $sql = BinaryUuidMigrations::getConversionSql('users', 'id', 'mysql');
    expect($sql)->toContain('users')
        ->toContain('UNHEX')
        ->toContain('id_binary');
});

it('supports binary uuid conversion macros', function () {
    $timeBasedBinary = Str::binaryTimeBasedUuid();
    expect(strlen($timeBasedBinary))->toBe(16)
        ->and(Uuid::import($timeBasedBinary)->version)->toBe(1);

    $reorderedBinary = Str::binaryReorderedTimeUuid();
    expect(strlen($reorderedBinary))->toBe(16)
        ->and(Uuid::import($reorderedBinary)->version)->toBe(6);

    $customBinary = Str::binaryCustomUuid('test-data');
    expect(strlen($customBinary))->toBe(16)
        ->and(Uuid::import($customBinary)->version)->toBe(8);
});

it('throws on invalid binary uuid cast input', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;

    $cast->set($model, 'test', 'invalid-uuid', []);
})->throws(InvalidArgumentException::class);

// --- BinaryUuidCast get() tests ---

it('cast get returns null for null value', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;

    expect($cast->get($model, 'id', null, []))->toBeNull();
});

it('cast get returns same uuid instance', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;
    $uuid = Uuid::v4();

    $result = $cast->get($model, 'id', $uuid, []);
    expect($result)->toBe($uuid);
});

it('cast get converts 16-byte binary to uuid object', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;
    $uuid = Uuid::v4();

    $result = $cast->get($model, 'id', $uuid->bytes, []);
    expect($result)->toBeInstanceOf(Uuid::class)
        ->and($result->string)->toBe($uuid->string);
});

it('cast get converts 36-char string to uuid object', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;
    $uuid = Uuid::v4();

    $result = $cast->get($model, 'id', $uuid->string, []);
    expect($result)->toBeInstanceOf(Uuid::class)
        ->and($result->string)->toBe($uuid->string);
});

it('cast get throws on invalid string', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;

    $cast->get($model, 'id', 'short-junk', []);
})->throws(InvalidArgumentException::class);

// --- BinaryUuidCast set() tests ---

it('cast set returns null for null value', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;

    expect($cast->set($model, 'id', null, []))->toBeNull();
});

it('cast set converts uuid instance to binary', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;
    $uuid = Uuid::v4();

    $result = $cast->set($model, 'id', $uuid, []);
    expect(strlen($result))->toBe(16)
        ->and($result)->toBe($uuid->bytes);
});

it('cast set passes through 16-byte binary', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;
    $binary = Uuid::v4()->bytes;

    $result = $cast->set($model, 'id', $binary, []);
    expect($result)->toBe($binary);
});

it('cast set converts 36-char string to binary', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;
    $uuid = Uuid::v4();

    $result = $cast->set($model, 'id', $uuid->string, []);
    expect(strlen($result))->toBe(16)
        ->and($result)->toBe($uuid->bytes);
});

// --- BinaryUuidCast serialize() tests ---

it('cast serialize returns null for null value', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;

    expect($cast->serialize($model, 'id', null, []))->toBeNull();
});

it('cast serialize converts uuid instance to string', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;
    $uuid = Uuid::v4();

    $result = $cast->serialize($model, 'id', $uuid, []);
    expect($result)->toBe($uuid->string)->toHaveLength(36);
});

it('cast serialize converts 16-byte binary to string', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;
    $uuid = Uuid::v4();

    $result = $cast->serialize($model, 'id', $uuid->bytes, []);
    expect($result)->toBe($uuid->string)->toHaveLength(36);
});

it('cast serialize passes through other strings', function () {
    $cast = new BinaryUuidCast;
    $model = new BinaryUuidTestModel;
    $str = '550e8400-e29b-41d4-a716-446655440000';

    $result = $cast->serialize($model, 'id', $str, []);
    expect($result)->toBe($str);
});

// --- HasBinaryUuids trait tests (using raw model without cast) ---

it('newUniqueId returns 16-byte binary v7', function () {
    $model = new BinaryUuidRawModel;
    $id = $model->newUniqueId();

    expect(strlen($id))->toBe(16)
        ->and(Uuid::import($id)->version)->toBe(7);
});

it('isValidUniqueId accepts 16-byte binary', function () {
    $model = new BinaryUuidRawModel;
    $binary = Uuid::v4()->bytes;

    expect($model->testIsValidUniqueId($binary))->toBeTrue();
});

it('isValidUniqueId accepts 36-char string', function () {
    $model = new BinaryUuidRawModel;

    expect($model->testIsValidUniqueId('550e8400-e29b-41d4-a716-446655440000'))->toBeTrue();
});

it('isValidUniqueId rejects invalid string', function () {
    $model = new BinaryUuidRawModel;

    expect($model->testIsValidUniqueId('not-a-uuid'))->toBeFalse();
});

it('isValidUniqueId rejects non-string', function () {
    $model = new BinaryUuidRawModel;

    expect($model->testIsValidUniqueId(12345))->toBeFalse()
        ->and($model->testIsValidUniqueId(null))->toBeFalse();
});

it('getUuidAsString returns 36-char string from binary id', function () {
    $model = new BinaryUuidRawModel;
    $uuid = Uuid::v4();
    $model->id = $uuid->bytes;

    expect($model->getUuidAsString())->toBe($uuid->string)->toHaveLength(36);
});

it('getUuidAsString returns empty string when no id', function () {
    $model = new BinaryUuidRawModel;

    expect($model->getUuidAsString())->toBe('');
});

it('setUuidFromString sets binary from valid string', function () {
    $model = new BinaryUuidRawModel;
    $uuid = Uuid::v4();

    $model->setUuidFromString($uuid->string);
    expect(strlen($model->id))->toBe(16)
        ->and($model->id)->toBe($uuid->bytes);
});

it('setUuidFromString ignores invalid string', function () {
    $model = new BinaryUuidRawModel;
    $model->setUuidFromString('not-valid');

    expect($model->id)->toBeNull();
});

it('getRouteKey returns 36-char string from binary', function () {
    $model = new BinaryUuidRawModel;
    $uuid = Uuid::v4();
    $model->id = $uuid->bytes;

    expect($model->getRouteKey())->toBe($uuid->string)->toHaveLength(36);
});

it('getRouteKey returns empty string when null', function () {
    $model = new BinaryUuidRawModel;

    expect($model->getRouteKey())->toBe('');
});

it('getUuidVersion detects version from binary id', function () {
    $model = new BinaryUuidRawModel;
    $model->id = Uuid::v4()->bytes;
    expect($model->getUuidVersion())->toBe(4);

    $model->id = Uuid::v7()->bytes;
    expect($model->getUuidVersion())->toBe(7);
});

it('getUuidVersion detects version from string id', function () {
    $model = new BinaryUuidRawModel;
    $model->id = Uuid::v4()->string;
    expect($model->getUuidVersion())->toBe(4);
});

it('getUuidVersion returns null for null id', function () {
    $model = new BinaryUuidRawModel;
    expect($model->getUuidVersion())->toBeNull();
});

it('usesOrderedUuids returns true for v7 binary', function () {
    $model = new BinaryUuidRawModel;
    $model->id = Uuid::v7()->bytes;

    expect($model->usesOrderedUuids())->toBeTrue();
});

it('usesOrderedUuids returns false for v4 binary', function () {
    $model = new BinaryUuidRawModel;
    $model->id = Uuid::v4()->bytes;

    expect($model->usesOrderedUuids())->toBeFalse();
});

it('getUuidTimestamp returns float for v7 binary', function () {
    $model = new BinaryUuidRawModel;
    $model->id = Uuid::v7()->bytes;

    expect($model->getUuidTimestamp())->toBeFloat();
});

it('getUuidTimestamp returns float for v1 binary', function () {
    $model = new BinaryUuidRawModel;
    $model->id = Uuid::generate(1)->bytes;

    expect($model->getUuidTimestamp())->toBeFloat();
});

it('getUuidTimestamp returns null for v4', function () {
    $model = new BinaryUuidRawModel;
    $model->id = Uuid::v4()->bytes;

    expect($model->getUuidTimestamp())->toBeNull();
});

it('getUuidTimestamp returns null for null id', function () {
    $model = new BinaryUuidRawModel;

    expect($model->getUuidTimestamp())->toBeNull();
});

it('getKeyType returns string', function () {
    $model = new BinaryUuidRawModel;

    expect($model->getKeyType())->toBe('string');
});

it('getIncrementing returns false', function () {
    $model = new BinaryUuidRawModel;

    expect($model->getIncrementing())->toBeFalse();
});

it('generates database info for sqlite', function () {
    $info = BinaryUuidMigrations::getDatabaseInfo('sqlite');

    expect($info['driver'])->toBe('sqlite')
        ->and($info['column_type'])->toBe('BLOB')
        ->and($info['storage_bytes'])->toBe(16)
        ->and($info['supports_native_uuid'])->toBeFalse();
});

it('generates database info for mysql', function () {
    $info = BinaryUuidMigrations::getDatabaseInfo('mysql');

    expect($info['driver'])->toBe('mysql')
        ->and($info['column_type'])->toBe('BINARY(16)')
        ->and($info['storage_bytes'])->toBe(16);
});

it('generates database info for pgsql', function () {
    $info = BinaryUuidMigrations::getDatabaseInfo('pgsql');

    expect($info['driver'])->toBe('pgsql')
        ->and($info['column_type'])->toBe('bytea')
        ->and($info['supports_native_uuid'])->toBeTrue();
});

it('generates database info for sqlsrv', function () {
    $info = BinaryUuidMigrations::getDatabaseInfo('sqlsrv');

    expect($info['driver'])->toBe('sqlsrv')
        ->and($info['column_type'])->toBe('uniqueidentifier')
        ->and($info['supports_native_uuid'])->toBeTrue()
        ->and($info['supports_endianness_conversion'])->toBeTrue();
});

it('generates conversion sql for all drivers', function () {
    $mysqlSql = BinaryUuidMigrations::getConversionSql('users', 'id', 'mysql');
    expect($mysqlSql)->toContain('UNHEX')->toContain('REPLACE');

    $pgsqlSql = BinaryUuidMigrations::getConversionSql('users', 'id', 'pgsql');
    expect($pgsqlSql)->toContain('decode')->toContain('hex');

    $sqliteSql = BinaryUuidMigrations::getConversionSql('users', 'id', 'sqlite');
    expect($sqliteSql)->toContain('unhex')->toContain('BLOB');

    $sqlsrvSql = BinaryUuidMigrations::getConversionSql('users', 'id', 'sqlsrv');
    expect($sqlsrvSql)->toContain('uniqueidentifier')->toContain('CAST');

    $unknownSql = BinaryUuidMigrations::getConversionSql('users', 'id', 'unknown');
    expect($unknownSql)->toContain('Adjust for your database');
});
