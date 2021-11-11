<?php

namespace Tests\Eloquent;

use Illuminate\Support\Carbon;
use LaravelFreelancerNL\Aranguent\Eloquent\Model;
use Mockery as M;
use Tests\Setup\Models\Character;
use Tests\Setup\Models\Location;
use Tests\TestCase;

class HasManyTest extends TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../Setup/Database/Migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());

        Character::insert(
            [
                [
                    '_key'          => 'NedStark',
                    'name'          => 'Ned',
                    'surname'       => 'Stark',
                    'alive'         => false,
                    'age'           => 41,
                    'traits'        => ['A', 'H', 'C', 'N', 'P'],
                    'residence_id' => 'locations/winterfell',
                ],
                [
                    '_key'          => 'SansaStark',
                    'name'          => 'Sansa',
                    'surname'       => 'Stark',
                    'alive'         => true,
                    'age'           => 13,
                    'traits'        => ['D', 'I', 'J'],
                    'residence_id' => 'locations/winterfell',
                ],
                [
                    '_key'          => 'RobertBaratheon',
                    'name'          => 'Robert',
                    'surname'       => 'Baratheon',
                    'alive'         => false,
                    'age'           => null,
                    'traits'        => ['A', 'H', 'C'],
                    'residence_id' => 'locations/dragonstone',
                ],
            ]
        );
        Location::insert(
            [
                [
                    '_key'       => 'dragonstone',
                    'name'       => 'Dragonstone',
                    'coordinate' => [55.167801, -6.815096],
                ],
                [
                    '_key'       => 'winterfell',
                    'name'       => 'Winterfell',
                    'coordinate' => [54.368321, -5.581312],
                    'led_by'     => 'characters/SansaStark',
                ],
                [
                    '_key'       => 'kingslanding',
                    'name'       => "King's Landing",
                    'coordinate' => [42.639752, 18.110189],
                ],
            ]
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);
        Carbon::resetToStringFormat();

        Model::unsetEventDispatcher();

        M::close();
    }

    public function testRetrieveRelation()
    {
        $location = Location::find('locations/winterfell');

        $inhabitants = $location->inhabitants;
        $this->assertCount(2, $inhabitants);
        $this->assertEquals($inhabitants->first()->residence_id, $location->_id);
        $this->assertInstanceOf(Character::class, $inhabitants->first());
    }

    public function testSave()
    {
        $character = Character::create(
            [
                '_key'    => 'TheonGreyjoy',
                'name'    => 'Theon',
                'surname' => 'Greyjoy',
                'alive'   => true,
                'age'     => 16,
                'traits'  => ['E', 'R', 'K'],
            ]
        );
        $location = Location::create(
            [
                '_key'       => 'pyke',
                'name'       => 'Pyke',
                'coordinate' => [55.8833342, -6.1388807],
            ]
        );

        $location->inhabitants()->save($character);
        // Reload
        $location = Location::findOrFail('locations/pyke');
        $inhabitants = $location->inhabitants;

        $this->assertEquals('TheonGreyjoy', $inhabitants->first()->_key);
        $this->assertEquals($inhabitants->first()->residence_id, $location->_id);
        $this->assertInstanceOf(Character::class, $inhabitants->first());
    }

    public function testCreate()
    {
        $location = Location::create(
            [
                '_key'       => 'pyke',
                'name'       => 'Pyke',
                'coordinate' => [55.8833342, -6.1388807],
            ]
        );

        $location->inhabitants()->create(
            [
                '_key'    => 'TheonGreyjoy',
                'name'    => 'Theon',
                'surname' => 'Greyjoy',
                'alive'   => true,
                'age'     => 16,
                'traits'  => ['E', 'R', 'K'],
            ]
        );
        $character = Character::find('characters/TheonGreyjoy');
        $location = Location::find('locations/pyke');

        $this->assertEquals('TheonGreyjoy', $location->inhabitants->first()->_key);
        $this->assertEquals($character->residence_id, 'locations/pyke');
        $this->assertInstanceOf(Character::class, $location->inhabitants->first());
    }

    public function testFirstOrCreate()
    {
        $location = Location::create(
            [
                '_key'       => 'pyke',
                'name'       => 'Pyke',
                'coordinate' => [55.8833342, -6.1388807],
            ]
        );

        $location->inhabitants()->firstOrCreate(
            [
                '_key'    => 'TheonGreyjoy',
                'name'    => 'Theon',
                'surname' => 'Greyjoy',
                'alive'   => true,
                'age'     => 16,
                'traits'  => ['E', 'R', 'K'],
            ]
        );
        $character = Character::find('characters/TheonGreyjoy');
        $location = Location::find('locations/pyke');

        $this->assertEquals('TheonGreyjoy', $location->inhabitants->first()->_key);
        $this->assertEquals($character->residence_id, 'locations/pyke');
        $this->assertInstanceOf(Character::class, $location->inhabitants->first());
    }

    public function testFirstOrNew()
    {
        $location = Location::create(
            [
                '_key'       => 'pyke',
                'name'       => 'Pyke',
                'coordinate' => [55.8833342, -6.1388807],
            ]
        );

        $character = $location->inhabitants()->firstOrNew(
            [
                '_key'    => 'TheonGreyjoy',
                'name'    => 'Theon',
                'surname' => 'Greyjoy',
                'alive'   => true,
                'age'     => 16,
                'traits'  => ['E', 'R', 'K'],
            ]
        );

        $this->assertInstanceOf(Character::class, $character);
        $this->assertEquals('TheonGreyjoy', $character->_key);
        $this->assertEquals($character->residence_id, 'locations/pyke');
    }

    public function testWith(): void
    {
        $location = Location::with('inhabitants')->find('locations/winterfell');

        $this->assertInstanceOf(Character::class, $location->inhabitants->first());
        $this->assertEquals('characters/NedStark', $location->inhabitants->first()->_id);
    }
}
