<?php

declare(strict_types=1);

use Webpatser\Uuid\Uuid;

beforeEach(function () {
    $this->model = new TestModel;
});

it('generates v7 uuid as new unique id', function () {
    $uuid = $this->model->newUniqueId();

    expect($uuid)->toBeString()->toHaveLength(36)
        ->and(Uuid::validate($uuid))->toBeTrue()
        ->and(Uuid::import($uuid)->version)->toBe(7);
});

it('validates unique ids', function () {
    expect($this->model->testIsValidUniqueId('550e8400-e29b-41d4-a716-446655440000'))->toBeTrue()
        ->and($this->model->testIsValidUniqueId('not-a-uuid'))->toBeFalse();
});

it('provides uuid helper methods', function () {
    $randomUuid = $this->model->newRandomUuid();
    expect(Uuid::import($randomUuid)->version)->toBe(4);

    $orderedUuid = $this->model->newOrderedUuid();
    expect(Uuid::import($orderedUuid)->version)->toBe(7);

    $timeUuid = $this->model->newTimeBasedUuid();
    expect(Uuid::import($timeUuid)->version)->toBe(1);
});

it('detects uuid version from model id', function () {
    $this->model->id = Uuid::v4()->string;
    expect($this->model->getUuidVersion())->toBe(4);

    $this->model->id = Uuid::v7()->string;
    expect($this->model->getUuidVersion())->toBe(7);
});

it('returns null version for invalid uuid', function () {
    $this->model->id = 'invalid-uuid';
    expect($this->model->getUuidVersion())->toBeNull();
});

it('returns null version for null id', function () {
    $this->model->id = null;
    expect($this->model->getUuidVersion())->toBeNull();
});

it('detects ordered uuids', function () {
    $this->model->id = Uuid::v7()->string;
    expect($this->model->usesOrderedUuids())->toBeTrue();

    $this->model->id = Uuid::v4()->string;
    expect($this->model->usesOrderedUuids())->toBeFalse();
});

it('extracts uuid timestamp from model', function () {
    $this->model->id = Uuid::generate(1)->string;
    expect($this->model->getUuidTimestamp())->toBeFloat();

    $this->model->id = Uuid::v7()->string;
    expect($this->model->getUuidTimestamp())->toBeFloat();

    $this->model->id = Uuid::v4()->string;
    expect($this->model->getUuidTimestamp())->toBeNull();
});

it('generates different uuids each time', function () {
    $uuid1 = $this->model->newUniqueId();
    $uuid2 = $this->model->newUniqueId();

    expect($uuid1)->not->toBe($uuid2);
});

it('generates sortable ordered uuids', function () {
    $uuids = [];

    for ($i = 0; $i < 5; $i++) {
        if ($i > 0) {
            usleep(1000);
        }
        $uuids[] = $this->model->newOrderedUuid();
    }

    $sortedUuids = $uuids;
    sort($sortedUuids);

    expect($uuids)->toBe($sortedUuids);
});
