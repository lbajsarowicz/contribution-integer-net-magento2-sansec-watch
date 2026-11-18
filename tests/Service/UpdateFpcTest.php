<?php
/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace IntegerNet\SansecWatch\Test\Service;

use IntegerNet\SansecWatch\Model\Config;
use IntegerNet\SansecWatch\Model\FpcMode;
use IntegerNet\SansecWatch\Service\UpdateFpc;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\PageCache\Model\Config as PageCacheConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IntegerNet\SansecWatch\Service\UpdateFpc
 */
class UpdateFpcTest extends TestCase
{
    public function testNothingIsDoneIfFpcIsDisabled(): void
    {
        $pageCacheConfig = self::createStub(PageCacheConfig::class);
        $pageCacheConfig->method('isEnabled')->willReturn(false);

        $config = $this->createMock(Config::class);
        $config->expects(self::never())->method(self::anything());

        (new UpdateFpc(
            $config,
            $pageCacheConfig,
            self::createStub(TypeListInterface::class),
        ))->execute();
    }

    public function testNothingIsDoneIfFpcModeIsNone(): void
    {
        $pageCacheConfig = self::createStub(PageCacheConfig::class);
        $pageCacheConfig->method('isEnabled')->willReturn(true);

        $config = $this->createMock(Config::class);
        $config->expects(self::once())->method('getFpcMode')->willReturn(FpcMode::None);

        $cacheTypeList = $this->createMock(TypeListInterface::class);
        $cacheTypeList->expects(self::never())->method(self::anything());

        (new UpdateFpc(
            $config,
            $pageCacheConfig,
            $cacheTypeList,
        ))->execute();
    }

    public function testInvalidatesFpcIfModeIfInvalidate(): void
    {
        $pageCacheConfig = self::createStub(PageCacheConfig::class);
        $pageCacheConfig->method('isEnabled')->willReturn(true);

        $config = $this->createMock(Config::class);
        $config->expects(self::once())->method('getFpcMode')->willReturn(FpcMode::Invalidate);

        $cacheTypeList = $this->createMock(TypeListInterface::class);
        $cacheTypeList->expects(self::once())->method('invalidate');

        (new UpdateFpc(
            $config,
            $pageCacheConfig,
            $cacheTypeList,
        ))->execute();
    }

    public function testClearsFpcIfModeIfClear(): void
    {
        $pageCacheConfig = self::createStub(PageCacheConfig::class);
        $pageCacheConfig->method('isEnabled')->willReturn(true);

        $config = $this->createMock(Config::class);
        $config->expects(self::once())->method('getFpcMode')->willReturn(FpcMode::Clear);

        $cacheTypeList = $this->createMock(TypeListInterface::class);
        $cacheTypeList->expects(self::once())->method('cleanType');

        (new UpdateFpc(
            $config,
            $pageCacheConfig,
            $cacheTypeList,
        ))->execute();
    }
}
