<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Webpatser\LaravelUuid\UuidCast;
use Webpatser\Uuid\Uuid;

beforeEach(function () {
    $this->cast = new UuidCast;
    $this->model = new class extends Model
    {
        protected $table = 'test';
    };
});

it('returns null when getting null value', function () {
    expect($this->cast->get($this->model, 'id', null, []))->toBeNull();
});

it('returns same uuid instance when getting uuid object', function () {
    $uuid = Uuid::v4();
    expect($this->cast->get($this->model, 'id', $uuid, []))->toBe($uuid);
});

it('imports uuid from valid string', function () {
    $uuidString = '550e8400-e29b-41d4-a716-446655440000';
    $result = $this->cast->get($this->model, 'id', $uuidString, []);

    expect($result)->toBeInstanceOf(Uuid::class)
        ->and($result->string)->toBe($uuidString);
});

it('returns null when setting null value', function () {
    expect($this->cast->set($this->model, 'id', null, []))->toBeNull();
});

it('converts uuid instance to string when setting', function () {
    $uuid = Uuid::v4();
    expect($this->cast->set($this->model, 'id', $uuid, []))->toBe($uuid->string);
});

it('passes through valid string when setting', function () {
    $uuidString = '550e8400-e29b-41d4-a716-446655440000';
    expect($this->cast->set($this->model, 'id', $uuidString, []))->toBe($uuidString);
});

it('throws on invalid string when setting', function () {
    $this->cast->set($this->model, 'id', 'invalid-uuid', []);
})->throws(TypeError::class);

it('supports round-trip set and get', function () {
    $originalUuid = Uuid::v4();

    $storedValue = $this->cast->set($this->model, 'id', $originalUuid, []);
    expect($storedValue)->toBeString();

    $retrievedUuid = $this->cast->get($this->model, 'id', $storedValue, []);
    expect($retrievedUuid)->toBeInstanceOf(Uuid::class)
        ->and($retrievedUuid->string)->toBe($originalUuid->string);
});
