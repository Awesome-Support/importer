<?php

namespace Pressware\AwesomeSupport\Tests\ZAPI\Repository;

use Mockery;
use PHPUnit\Framework\TestCase;
use Pressware\AwesomeSupport\API\Repository\Repository;

class RepositoryTest extends TestCase
{
    protected $repository;
    protected $items;

    public function setUp()
    {
        $this->items = [
            'foo'       => 'bar',
            'bar'       => 'baz',
            'baz'       => 'bat',
            'null'      => null,
            'associate' => [
                'x' => 'xxx',
                'y' => 'yyy',
            ],
            'array'     => [
                'aaa',
                'zzz',
            ],
            'x'         => [
                'z' => 'zoo',
            ],
            'arrayNested'         => [
                'foo' => [
                    'bar' => 'baz',
                    'foobar',
                ],
            ],
        ];

        $this->repository = new Repository(
            Mockery::mock('Pressware\AwesomeSupport\Notifications\Notifier'),
            $this->items
        );

        parent::setUp();
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(Repository::class, $this->repository);
    }

    public function testHasIsTrue()
    {
        $this->assertTrue($this->repository->has('foo'));
    }

    public function testHasIsFalse()
    {
        $this->assertFalse($this->repository->has('not-exist'));
    }

    public function testGet()
    {
        $this->assertSame('bar', $this->repository->get('foo'));
        $this->assertSame('xxx', $this->repository->get('associate.x'));
        $this->assertSame([
            'bar' => 'baz',
            'foobar'
        ], $this->repository->get('arrayNested.foo'));
        $this->assertSame('foobar', $this->repository->get('arrayNested.foo.0'));
        $this->assertSame('baz', $this->repository->get('arrayNested.foo.bar'));
    }

    public function testGetWithDefault()
    {
        $this->assertSame('default', $this->repository->get('keyDoesNotExist', 'default'));
        $this->assertSame([], $this->repository->get('not-exist', []));
        $this->assertSame('', $this->repository->get('x.dummyKey', ''));
    }

    public function testGetAll()
    {
        $this->assertSame($this->items, $this->repository->getAll());
    }

    public function testSet()
    {
        $this->repository->set('key', 'value');
        $this->assertSame('value', $this->repository->get('key'));

        $this->repository->set('arrayNested.foo.1', 'foo1');
        $this->assertSame('foo1', $this->repository->get('arrayNested.foo.1'));

        // try an empty array.
        $this->repository->set('associate.foo.data', []);
        $this->assertSame([], $this->repository->get('associate.foo.data'));

        $this->repository->set('associate.foo', ['barfoo' => 'bazfoo']);
        $this->assertSame(['barfoo' => 'bazfoo'], $this->repository->get('associate.foo'));
    }

    public function testPush()
    {
        $this->repository->push('array', 'xxx');
        $this->assertSame('xxx', $this->repository->get('array.2'));

        $this->repository->push('arrayNested.foo', 'barbaz');
        $this->assertSame('barbaz', $this->repository->get('arrayNested.foo.1'));

        $this->repository->set('associate.foo.data', []);
        $this->repository->push('associate.foo.data', ['foo' => '']);
        $this->assertSame(['foo' => ''], $this->repository->get('associate.foo.data.0'));
        $this->repository->push('associate.foo.data', ['bar' => 'baz']);
        $this->assertSame(['bar' => 'baz'], $this->repository->get('associate.foo.data.1'));
        $this->assertSame('baz', $this->repository->get('associate.foo.data.1.bar'));
    }

    public function testCount()
    {
        $expected = 8;
        $this->assertCount($expected, $this->repository->getAll());
        $this->assertEquals($expected, $this->repository->count());
        $this->assertCount($this->repository->count(), $this->repository->getAll());

        $this->repository->set('ktc', 'hello');
        $this->assertEquals(++$expected, $this->repository->count());
        $this->assertCount($this->repository->count(), $this->repository->getAll());
    }

    public function testClear()
    {
        $this->assertNotEmpty($this->repository->getAll());
        $this->assertNotNull($this->repository->get('foo'));
        $this->repository->clear();
        $this->assertEmpty($this->repository->getAll());
        $this->assertNull($this->repository->get('foo'));
    }
}
