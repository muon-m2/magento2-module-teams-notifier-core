<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Test\Unit\Model;

use Muon\TeamsNotifierCore\Model\TemplateVariableSubstitutor;
use PHPUnit\Framework\TestCase;

class TemplateVariableSubstitutorTest extends TestCase
{
    /** @var \Muon\TeamsNotifierCore\Model\TemplateVariableSubstitutor */
    private TemplateVariableSubstitutor $substitutor;

    protected function setUp(): void
    {
        $this->substitutor = new TemplateVariableSubstitutor();
    }

    public function testSubstitutesKnownPlaceholders(): void
    {
        $template = [
            'type'    => 'AdaptiveCard',
            'version' => '1.4',
            'body'    => [
                ['type' => 'TextBlock', 'text' => 'Order ${order_id} placed by ${customer}'],
            ],
        ];

        $result = $this->substitutor->substitute($template, [
            'order_id' => '#42',
            'customer' => 'Alice',
        ]);

        $this->assertSame(
            'Order #42 placed by Alice',
            $result['body'][0]['text']
        );
    }

    public function testLeavesUnknownPlaceholdersUnchanged(): void
    {
        $template = [
            'type'    => 'AdaptiveCard',
            'version' => '1.4',
            'body'    => [
                ['type' => 'TextBlock', 'text' => 'Hello ${unknown}'],
            ],
        ];

        $result = $this->substitutor->substitute($template, ['order_id' => '#1']);

        $this->assertSame('Hello ${unknown}', $result['body'][0]['text']);
    }

    public function testHandlesNestedArrays(): void
    {
        $template = [
            'type'    => 'AdaptiveCard',
            'version' => '1.4',
            'body'    => [
                [
                    'type'    => 'ColumnSet',
                    'columns' => [
                        ['type' => 'Column', 'items' => [
                            ['type' => 'TextBlock', 'text' => 'Total: ${total}'],
                        ]],
                    ],
                ],
            ],
        ];

        $result = $this->substitutor->substitute($template, ['total' => '$129.00']);

        $this->assertSame(
            'Total: $129.00',
            $result['body'][0]['columns'][0]['items'][0]['text']
        );
    }

    public function testReturnsTemplateUnchangedWhenDataIsEmpty(): void
    {
        $template = [
            'type'    => 'AdaptiveCard',
            'version' => '1.4',
            'body'    => [['type' => 'TextBlock', 'text' => 'Hello ${name}']],
        ];

        $result = $this->substitutor->substitute($template, []);

        $this->assertSame($template, $result);
    }

    public function testDoesNotModifyNonStringLeaves(): void
    {
        $template = [
            'type'    => 'AdaptiveCard',
            'version' => '1.4',
            'body'    => [],
            'wrap'    => true,
            'count'   => 3,
            'ratio'   => null,
        ];

        $result = $this->substitutor->substitute($template, ['wrap' => 'replaced']);

        $this->assertTrue($result['wrap']);
        $this->assertSame(3, $result['count']);
        $this->assertNull($result['ratio']);
    }

    public function testReplacesMultiplePlaceholdersInSameString(): void
    {
        $template = [
            'type'    => 'AdaptiveCard',
            'version' => '1.4',
            'body'    => [
                ['type' => 'TextBlock', 'text' => '${greeting}, ${name}! Your order is ${status}.'],
            ],
        ];

        $result = $this->substitutor->substitute($template, [
            'greeting' => 'Hello',
            'name'     => 'Bob',
            'status'   => 'shipped',
        ]);

        $this->assertSame('Hello, Bob! Your order is shipped.', $result['body'][0]['text']);
    }
}
