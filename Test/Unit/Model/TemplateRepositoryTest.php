<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Test\Unit\Model;

use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use RuntimeException;
use Muon\TeamsNotifierCore\Model\ResourceModel\Template as TemplateResource;
use Muon\TeamsNotifierCore\Model\ResourceModel\Template\Collection;
use Muon\TeamsNotifierCore\Model\ResourceModel\Template\CollectionFactory;
use Muon\TeamsNotifierCore\Model\Template;
use Muon\TeamsNotifierCore\Model\Template\JsonValidator;
use Muon\TeamsNotifierCore\Model\TemplateFactory;
use Muon\TeamsNotifierCore\Model\TemplateRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TemplateRepositoryTest extends TestCase
{
    // phpcs:disable Magento2.Commenting.ClassPropertyPHPDocFormatting.Missing
    private TemplateFactory&MockObject $templateFactory;
    private TemplateResource&MockObject $resource;
    private CollectionFactory&MockObject $collectionFactory;
    private SearchResultsInterfaceFactory&MockObject $searchResultsFactory;
    private JsonValidator&MockObject $jsonValidator;
    // phpcs:enable Magento2.Commenting.ClassPropertyPHPDocFormatting.Missing

    /** @var \Muon\TeamsNotifierCore\Model\TemplateRepository */
    private TemplateRepository $repository;

    protected function setUp(): void
    {
        $this->templateFactory      = $this->createMock(TemplateFactory::class);
        $this->resource             = $this->createMock(TemplateResource::class);
        $this->collectionFactory    = $this->createMock(CollectionFactory::class);
        $this->searchResultsFactory = $this->createMock(SearchResultsInterfaceFactory::class);
        $this->jsonValidator        = $this->createMock(JsonValidator::class);

        $this->repository = new TemplateRepository(
            $this->templateFactory,
            $this->resource,
            $this->collectionFactory,
            $this->searchResultsFactory,
            $this->jsonValidator
        );
    }

    public function testSaveValidatesJsonBeforePersisting(): void
    {
        $template = $this->createMock(Template::class);
        $template->method('getTemplateJson')->willReturn('{"type":"AdaptiveCard"}');

        $this->jsonValidator->expects($this->once())
            ->method('validate')
            ->with('{"type":"AdaptiveCard"}');

        $this->resource->expects($this->once())->method('save')->with($template);

        $this->repository->save($template);
    }

    public function testSaveThrowsWhenJsonValidationFails(): void
    {
        $template = $this->createMock(Template::class);
        $template->method('getTemplateJson')->willReturn('bad json');

        $this->jsonValidator->method('validate')
            ->willThrowException(new LocalizedException(__('Invalid JSON.')));

        $this->expectException(LocalizedException::class);
        $this->repository->save($template);
    }

    public function testSaveThrowsCouldNotSaveOnResourceException(): void
    {
        $template = $this->createMock(Template::class);
        $template->method('getTemplateJson')->willReturn('{}');

        $this->jsonValidator->method('validate');
        $this->resource->method('save')
            ->willThrowException(new RuntimeException('DB error'));

        $this->expectException(CouldNotSaveException::class);
        $this->repository->save($template);
    }

    public function testGetByIdThrowsWhenNotFound(): void
    {
        $template = $this->createMock(Template::class);
        $template->method('getTemplateId')->willReturn(null);

        $this->templateFactory->method('create')->willReturn($template);
        $this->resource->method('load')->with($template, 99);

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getById(99);
    }

    public function testGetByIdReturnsTemplateWhenFound(): void
    {
        $template = $this->createMock(Template::class);
        $template->method('getTemplateId')->willReturn(5);

        $this->templateFactory->method('create')->willReturn($template);

        $result = $this->repository->getById(5);

        $this->assertSame($template, $result);
    }

    public function testGetByNameThrowsWhenNotFound(): void
    {
        $template = $this->createMock(Template::class);
        $template->method('getTemplateId')->willReturn(null);

        $this->templateFactory->method('create')->willReturn($template);

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getByName('non-existent');
    }
}
