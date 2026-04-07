<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Test\Unit\Model\Template;

use Magento\Framework\Exception\LocalizedException;
use Muon\TeamsNotifierCore\Model\Template\JsonValidator;
use PHPUnit\Framework\TestCase;

class JsonValidatorTest extends TestCase
{
    /** @var \Muon\TeamsNotifierCore\Model\Template\JsonValidator */
    private JsonValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new JsonValidator();
    }

    public function testAcceptsValidAdaptiveCard(): void
    {
        $json = json_encode([
            '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
            'type'    => 'AdaptiveCard',
            'version' => '1.4',
            'body'    => [
                ['type' => 'TextBlock', 'text' => 'Hello ${name}'],
            ],
        ]);

        $this->validator->validate($json);
        $this->addToAssertionCount(1);
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(LocalizedException::class);
        $this->validator->validate('');
    }

    public function testRejectsInvalidJson(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('not valid JSON');
        $this->validator->validate('{not: valid json}');
    }

    public function testRejectsJsonArray(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('root must be an object');
        $this->validator->validate('["a","b"]');
    }

    public function testRejectsMissingType(): void
    {
        $json = json_encode(['version' => '1.4', 'body' => []]);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('"type": "AdaptiveCard"');
        $this->validator->validate($json);
    }

    public function testRejectsWrongType(): void
    {
        $json = json_encode(['type' => 'Container', 'version' => '1.4', 'body' => []]);
        $this->expectException(LocalizedException::class);
        $this->validator->validate($json);
    }

    public function testRejectsMissingBody(): void
    {
        $json = json_encode(['type' => 'AdaptiveCard', 'version' => '1.4']);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('"body"');
        $this->validator->validate($json);
    }

    public function testRejectsBodyNotArray(): void
    {
        $json = json_encode(['type' => 'AdaptiveCard', 'version' => '1.4', 'body' => 'text']);
        $this->expectException(LocalizedException::class);
        $this->validator->validate($json);
    }

    public function testRejectsMissingVersion(): void
    {
        $json = json_encode(['type' => 'AdaptiveCard', 'body' => []]);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('"version"');
        $this->validator->validate($json);
    }

    public function testRejectsEmptyVersion(): void
    {
        $json = json_encode(['type' => 'AdaptiveCard', 'version' => '', 'body' => []]);
        $this->expectException(LocalizedException::class);
        $this->validator->validate($json);
    }
}
