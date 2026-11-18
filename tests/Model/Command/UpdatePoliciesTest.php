<?php

declare(strict_types=1);

namespace IntegerNet\SansecWatch\Test\Model\Command;

use IntegerNet\SansecWatch\Model\Command\UpdatePolicies;
use IntegerNet\SansecWatch\Model\DTO\Policy;
use IntegerNet\SansecWatch\Model\Exception\CouldNotUpdatePoliciesException;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \IntegerNet\SansecWatch\Model\Command\UpdatePolicies
 */
class UpdatePoliciesTest extends TestCase
{
    private AdapterInterface&MockObject $connection;

    private UpdatePolicies $updatePoliciesCommand;

    /** @noinspection PhpUnhandledExceptionInspection */
    protected function setUp(): void
    {
        $this->connection = self::createMock(AdapterInterface::class);

        $resourceConnection = self::createStub(ResourceConnection::class);
        $resourceConnection
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->updatePoliciesCommand = new UpdatePolicies($resourceConnection);
    }

    public function testDoesNotExecuteInsertWhenNoPoliciesAreGiven(): void
    {
        $this->connection
            ->expects(self::never())
            ->method('insertMultiple');

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->updatePoliciesCommand->execute([]);
    }

    public function testDeletesExistingRulesBeforeUpdating(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('delete');

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->updatePoliciesCommand->execute([]);
    }

    public function testSavesPolicyRowsForTheRequestedStore(): void
    {
        $policy = new Policy('script-src', 'store.example');
        $this->connection
            ->expects(self::once())
            ->method('quoteInto')
            ->with('store_id = ?', 12)
            ->willReturn('store_id = 12');
        $this->connection
            ->expects(self::once())
            ->method('delete')
            ->with(self::anything(), 'store_id = 12');
        $this->connection
            ->expects(self::once())
            ->method('insertMultiple')
            ->with(
                self::anything(),
                [[
                    'directive' => 'script-src',
                    'host' => 'store.example',
                    'store_id' => 12,
                ]],
            );

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->updatePoliciesCommand->execute([$policy], storeId: 12);
    }

    public function testDatabaseQueriesAreRunInTransaction(): void
    {
        $this->connection
            ->expects(self::once())
            ->method('beginTransaction');

        $this->connection
            ->expects(self::once())
            ->method('commit');

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->updatePoliciesCommand->execute([]);
    }

    public function testTransactionIsRolledBackInCaseOfAnyError(): void
    {
        self::expectException(CouldNotUpdatePoliciesException::class);

        $this->connection
            ->expects(self::once())
            ->method('beginTransaction');

        $this->connection
            ->method('commit')
            ->willThrowException(new \Exception('Something went wrong'));

        $this->connection
            ->expects(self::once())
            ->method('rollBack');

        $this->updatePoliciesCommand->execute([]);
    }
}
