<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Muon\TeamsNotifierCore\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    // phpcs:disable Magento2.Commenting.ClassPropertyPHPDocFormatting.Missing
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private ScopeConfigInterface&MockObject $scopeConfig;
    // phpcs:enable Magento2.Commenting.ClassPropertyPHPDocFormatting.Missing
    /** @var \Muon\TeamsNotifierCore\Model\Config */
    private Config $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config      = new Config($this->scopeConfig);
    }

    public function testIsEnabledReturnsTrueWhenFlagSet(): void
    {
        $this->scopeConfig
            ->method('isSetFlag')
            ->with('muon_teamsnotifiercore/general/enabled', ScopeInterface::SCOPE_STORE)
            ->willReturn(true);

        $this->assertTrue($this->config->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenFlagNotSet(): void
    {
        $this->scopeConfig
            ->method('isSetFlag')
            ->willReturn(false);

        $this->assertFalse($this->config->isEnabled());
    }

    public function testGetDefaultChannelNameReturnsConfiguredValue(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with('muon_teamsnotifiercore/general/default_channel', ScopeInterface::SCOPE_STORE)
            ->willReturn('ops-alerts');

        $this->assertSame('ops-alerts', $this->config->getDefaultChannelName());
    }

    public function testGetTimeoutReturnsAtLeastOne(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with('muon_teamsnotifiercore/general/timeout', ScopeInterface::SCOPE_STORE)
            ->willReturn('0');

        $this->assertSame(1, $this->config->getTimeout());
    }

    public function testIsAsyncModeReturnsTrueWhenModeIsAsync(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with('muon_teamsnotifiercore/general/delivery_mode', ScopeInterface::SCOPE_STORE)
            ->willReturn(Config::DELIVERY_MODE_ASYNC);

        $this->assertTrue($this->config->isAsyncMode());
    }

    public function testIsAsyncModeReturnsFalseWhenModeIsSync(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with('muon_teamsnotifiercore/general/delivery_mode', ScopeInterface::SCOPE_STORE)
            ->willReturn(Config::DELIVERY_MODE_SYNC);

        $this->assertFalse($this->config->isAsyncMode());
    }

    public function testGetMaxAttemptsReturnsAtLeastOne(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->willReturn('0');

        $this->assertSame(1, $this->config->getMaxAttempts());
    }

    public function testGetRetryDelayReturnsConfiguredValue(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with('muon_teamsnotifiercore/queue/retry_delay', ScopeInterface::SCOPE_STORE)
            ->willReturn('120');

        $this->assertSame(120, $this->config->getRetryDelay());
    }
}
