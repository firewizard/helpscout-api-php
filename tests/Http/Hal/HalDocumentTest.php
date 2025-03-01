<?php

declare(strict_types=1);

namespace HelpScout\Api\Tests\Http\Hal;

use HelpScout\Api\Exception\InvalidArgumentException;
use HelpScout\Api\Http\Hal\HalDeserializer;
use HelpScout\Api\Http\Hal\HalDocument;
use HelpScout\Api\Http\Hal\HalLink;
use HelpScout\Api\Http\Hal\HalLinks;
use PHPUnit\Framework\TestCase;

class HalDocumentTest extends TestCase
{
    public function testGetEmbeddedThrowsExceptionWhenLinkNotFound()
    {
        $this->expectException(InvalidArgumentException::class);

        $document = new HalDocument([], new HalLinks([]), []);
        $document->getEmbedded('unknown');
    }

    public function testGetEmbeddedEntitiesMapsToNestedArrays()
    {
        $emptyLinks = new HalLinks([]);
        $thread = new HalDocument(
            [],
            $emptyLinks,
            [
                'attachments' => [
                    new HalDocument(
                        [
                            'id' => 4823,
                        ],
                        $emptyLinks,
                        []
                    ),
                ],
                'address' => new HalDocument(
                    [
                        'city' => 'London',
                    ],
                    $emptyLinks,
                    []
                ),
            ]
        );

        $entities = $thread->getEmbeddedEntities();

        // Asserts that a HasMany collection of embedded entities is mapped correctly
        $this->assertArrayHasKey('attachments', $entities);
        $this->assertEquals(4823, $entities['attachments'][0]['id']);

        // Asserts that a HasOne embedded entity is mapped correctly
        $this->assertArrayHasKey('address', $entities);
        $this->assertEquals('London', $entities['address']['city']);
    }

    public function testGetEmbeddedEntitiesMapsLinksWithMultipleEmbeddedEntities()
    {
        $emptyLinks = new HalLinks([]);
        $thread = new HalDocument(
            [],
            $emptyLinks,
            [
                'attachments' => [
                    new HalDocument(
                        [
                            'id' => 4823,
                        ],
                        new HalLinks([
                            new HalLink('rel', 'href', true),
                        ]),
                        []
                    ),
                ],
                'address' => new HalDocument(
                    [
                        'city' => 'London',
                    ],
                    $emptyLinks,
                    []
                ),
            ]
        );

        $entities = $thread->getEmbeddedEntities();

        // Asserts that a HasMany collection of embedded entities is mapped correctly
        $this->assertArrayHasKey('attachments', $entities);
        $this->assertEquals(4823, $entities['attachments'][0]['id']);

        $this->assertArrayHasKey(HalDeserializer::LINKS, $entities['attachments'][0]);
        $this->assertInstanceOf(HalLinks::class, $entities['attachments'][0][HalDeserializer::LINKS]);
    }

    public function testGetEmbeddedEntitiesMapsLinksWithSingleEmbeddedEntity()
    {
        $emptyLinks = new HalLinks([]);
        $thread = new HalDocument(
            [],
            $emptyLinks,
            [
                'attachments' => [
                    new HalDocument(
                        [
                            'id' => 4823,
                        ],
                        new HalLinks([
                            new HalLink('rel', 'href', true),
                        ]),
                        []
                    ),
                ],
            ]
        );

        $entities = $thread->getEmbeddedEntities();

        // Asserts that a HasMany collection of embedded entities is mapped correctly
        $this->assertArrayHasKey('attachments', $entities);
        $this->assertEquals(4823, $entities['attachments'][0]['id']);

        $this->assertArrayHasKey(HalDeserializer::LINKS, $entities['attachments'][0]);
        $this->assertInstanceOf(HalLinks::class, $entities['attachments'][0][HalDeserializer::LINKS]);
    }

    public function testGetEmbedKeysIsAlwaysAnArray()
    {
        $document = new HalDocument([], new HalLinks([]), []);

        $this->assertEquals([], $document->getEmbeddedEntities());
    }

    public function testDeterminesWhenLinksArePresent()
    {
        $documentWithoutLinks = new HalDocument([], new HalLinks([]), []);

        $this->assertFalse($documentWithoutLinks->hasLinks());

        $documentWithLinks = new HalDocument(
            [],
            new HalLinks([
                new HalLink('rel', 'href', true),
            ]),
            []
        );

        $this->assertTrue($documentWithLinks->hasLinks());
    }
}
