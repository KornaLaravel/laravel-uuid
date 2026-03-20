<?php

declare(strict_types=1);

use Webpatser\Uuid\Uuid;

it('has uuid class available', function () {
    $uuid = Uuid::v4();
    expect($uuid)->toBeInstanceOf(Uuid::class)
        ->and($uuid->version)->toBe(4);
});

it('supports various generation methods', function () {
    $uuid1 = Uuid::generate(1);
    $uuid4 = Uuid::generate(4);
    $uuid7 = Uuid::generate(7);

    expect($uuid1->version)->toBe(1)
        ->and($uuid4->version)->toBe(4)
        ->and($uuid7->version)->toBe(7);
});

it('validates uuids', function () {
    $uuid = Uuid::v4();
    expect(Uuid::validate($uuid->string))->toBeTrue();

    $validUuid = '550e8400-e29b-41d4-a716-446655440000';
    expect(Uuid::validate($validUuid))->toBeTrue()
        ->and(Uuid::validate('invalid-uuid'))->toBeFalse();
});

it('supports shorthand methods', function () {
    $uuid4 = Uuid::v4();
    $uuid7 = Uuid::v7();

    expect($uuid4->version)->toBe(4)
        ->and($uuid7->version)->toBe(7);
});

it('exposes uuid properties', function () {
    $uuid = Uuid::v4();

    expect($uuid->string)->toBeString()->toHaveLength(36)
        ->and($uuid->hex)->toBeString()->toHaveLength(32)
        ->and($uuid->bytes)->toBeString()
        ->and(strlen($uuid->bytes))->toBe(16);
});

it('supports nil uuid', function () {
    $nil = Uuid::nil();

    expect($nil->isNil())->toBeTrue()
        ->and((string) $nil)->toBe('00000000-0000-0000-0000-000000000000')
        ->and(Uuid::isNilUuid($nil))->toBeTrue();
});

it('supports modern uuid versions 6 7 8', function () {
    $uuid6 = Uuid::generate(6);
    $uuid7 = Uuid::generate(7);
    $uuid8 = Uuid::generate(8);

    expect($uuid6->version)->toBe(6)
        ->and($uuid7->version)->toBe(7)
        ->and($uuid8->version)->toBe(8);
});

it('maintains backward compatibility', function () {
    $uuid3 = Uuid::generate(3, 'test', Uuid::NS_DNS);
    $uuid5 = Uuid::generate(5, 'test', Uuid::NS_DNS);

    expect($uuid3->version)->toBe(3)
        ->and($uuid5->version)->toBe(5);

    // Same input produces same output for name-based UUIDs
    $uuid3_2 = Uuid::generate(3, 'test', Uuid::NS_DNS);
    expect((string) $uuid3)->toBe((string) $uuid3_2);
});
