<?php

namespace BestIt\CtCleanUpBundle\Command;

use Commercetools\Core\Client;
use Commercetools\Core\Model\Category\Category;
use Commercetools\Core\Model\Common\Resource;
use Commercetools\Core\Request\AbstractQueryRequest;
use Commercetools\Core\Request\Categories\CategoryDeleteRequest;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\Customers\CustomerDeleteRequest;
use Commercetools\Core\Request\Customers\CustomerQueryRequest;
use Commercetools\Core\Request\Products\ProductDeleteRequest;
use Commercetools\Core\Request\Products\ProductQueryRequest;
use Commercetools\Core\Response\ApiResponseInterface;
use Commercetools\Core\Response\ErrorResponse;
use Commercetools\Core\Response\PagedQueryResponse;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The cleanup command.
 * @author blange <lange@bestit-online.de>
 * @package BestIt\CtCleanUpBundle
 * @subpackage Command
 * @version $id$
 */
class CleanupCommand extends Command
{
    use LockableTrait, LoggerAwareTrait;

    /**
     * The commercetools client.
     * @var Client
     */
    private $client = null;

    /**
     * The predicates for the types.
     * @var array
     */
    private $predicatesForTypes = [];

    /**
     * CleanupCommand constructor.
     * @param Client $client
     * @param array $predicates
     * @param LoggerInterface|null $logger
     */
    public function __construct(Client $client, array $predicates, LoggerInterface $logger = null)
    {
        parent::__construct();

        $this
            ->setClient($client)
            ->setPredicatesForTypes($predicates)
            ->setLogger($logger ?? new NullLogger());
    }

    protected function configure()
    {
        $this
            ->setName('bestit:cleanup:cleanup')
            ->setDescription('Iterates through the config and cleans the matching commercetools objects.');
    }

    /**
     * Executes the cleanup.
     * @return void
     */
    private function doCleanUp()
    {
        $logger = $this->getLogger();

        $predicates = $this->getUsedPredicates();

        $map = [
            'category' => [CategoryQueryRequest::class, CategoryDeleteRequest::class],
            'customer' => [CustomerQueryRequest::class, CustomerDeleteRequest::class],
            'product' => [ProductQueryRequest::class, ProductDeleteRequest::class]
        ];

        $client = $this->getClient();

        $logger->debug('Predicates are filtered.', ['predicates' => $predicates]);

        foreach ($predicates as $type => $queries) {
            list($queryClass, $deleteClass) = $map[$type];

            /** @var AbstractQueryRequest $queryObject */
            $request = new $queryClass();

            if ($queries) {
                $queryString = $this->parseQueryString($queries);

                $logger->debug('Fetching rows.', ['query' => $queryString, 'type' => $type]);

                $request->where($queryString);

                $response = $client->execute($request);

                while (($response instanceof PagedQueryResponse) && ($response->getCount())) {
                    $logger->debug(
                        'Found rows.',
                        ['count' => $response->getCount(), 'query' => $queryString, 'type' => $type]
                    );

                    set_time_limit(0);

                    /** @var Resource $object */
                    foreach ($response->toObject() as $object) {
                        $logger->debug('Removed row.', ['object' => $object]);

                        $client->addBatchRequest($deleteClass::ofIdAndVersion(
                            $object->getId(),
                            $object->getVersion()
                        ));
                    }

                    $responses = $client->executeBatch();

                    array_walk($responses, function (ApiResponseInterface $response) {
                        if ($response instanceof ErrorResponse) {
                            exit(var_dump($response->getMessage()));
                        }
                    });

                    $logger->debug('Refetching rows.', ['query' => $queryString, 'type' => $type]);

                    $response = $client->execute($request);
                }
            }
        }
    }

    /**
     * Executes the cleanup.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getLogger();

        if (!$this->lock()) {
            $output->writeln('<error>The command is already running in another process.</error>');
            $logger->notice('The clean up command is already running.');

            return 0;
        }

        $logger->info('Starting cleanup in command.');

        $this->doCleanUp();

        $logger->info('Finished cleanup in command.');
        $output->writeln('<info>Cleanup finished</info>');
    }

    /**
     * Returns the client.
     * @return Client
     */
    private function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Returns the logger.
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Returns the predicates for the types.
     * @return array
     */
    private function getPredicatesForTypes(): array
    {
        return $this->predicatesForTypes;
    }

    /**
     * Returns the used predicates.
     * @return array
     */
    private function getUsedPredicates(): array
    {
        return array_filter($this->getPredicatesForTypes(), function (array $queries): bool {
            return !empty($queries);
        });
    }

    /**
     * Parses the query string.
     * @param $queries
     * @return string
     */
    private function parseQueryString($queries): string
    {
        $queryString = '(' . implode(') or (', $queries) . ')';

        return $queryString;
    }

    /**
     * Sets the predicates for the types.
     * @param array $predicatesForTypes
     * @return CleanupCommand
     */
    private function setPredicatesForTypes(array $predicatesForTypes): CleanupCommand
    {
        $this->predicatesForTypes = $predicatesForTypes;

        return $this;
    }

    /**
     * Sets the client.
     * @param Client $client
     * @return CleanupCommand
     */
    private function setClient(Client $client): CleanupCommand
    {
        $this->client = $client;

        return $this;
    }
}
