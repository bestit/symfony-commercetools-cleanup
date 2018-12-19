<?php

declare(strict_types=1);

namespace BestIt\CtCleanUpBundle\Command;

use BestIt\CtCleanUpBundle\DependencyInjection\Configuration;
use Commercetools\Core\Client;
use Commercetools\Core\Request\AbstractQueryRequest;
use Commercetools\Core\Request\Carts\CartDeleteRequest;
use Commercetools\Core\Request\Carts\CartQueryRequest;
use Commercetools\Core\Request\Categories\CategoryDeleteRequest;
use Commercetools\Core\Request\Categories\CategoryQueryRequest;
use Commercetools\Core\Request\Customers\CustomerDeleteRequest;
use Commercetools\Core\Request\Customers\CustomerQueryRequest;
use Commercetools\Core\Request\CustomObjects\CustomObjectDeleteRequest;
use Commercetools\Core\Request\CustomObjects\CustomObjectQueryRequest;
use Commercetools\Core\Request\Orders\OrderDeleteRequest;
use Commercetools\Core\Request\Orders\OrderQueryRequest;
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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function array_filter;
use function array_map;
use function array_walk;
use function date;
use function implode;
use function preg_replace_callback;
use function set_time_limit;
use function strtotime;

/**
 * The cleanup command.
 *
 * @author blange <lange@bestit-online.de>
 * @package BestIt\CtCleanUpBundle\Command
 */
class CleanupCommand extends Command
{
    use LockableTrait;
    use LoggerAwareTrait;

    /**
     * The commercetools client.
     * @var Client
     */
    private $client;

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

        $this->client = $client;
        $this->predicatesForTypes = $predicates;

        $this->setLogger($logger ?? new NullLogger());
    }

    /**
     * Configures the command.
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('bestit:cleanup')
            ->setDescription('Iterates through the config and cleans the matching commercetools objects.')
            ->addOption(
                'simulate',
                's',
                InputOption::VALUE_NONE,
                'Only simulate the query without any cleanup.'
            );
    }

    /**
     * Executes the cleanup.
     * @param OutputInterface $output Table, progress and verbosity.
     * @param bool $simulate Just simulate the query and give the total.
     * @return Table
     */
    private function doCleanUp(OutputInterface $output, bool $simulate = false): Table
    {
        $logger = $this->logger;

        $predicates = $this->getUsedPredicates();

        $map = [
            Configuration::TYPE_CART => [CartQueryRequest::class, CartDeleteRequest::class],
            Configuration::TYPE_CATEGORY => [CategoryQueryRequest::class, CategoryDeleteRequest::class],
            Configuration::TYPE_CUSTOMER => [CustomerQueryRequest::class, CustomerDeleteRequest::class],
            Configuration::TYPE_PRODUCT => [ProductQueryRequest::class, ProductDeleteRequest::class],
            Configuration::TYPE_ORDER => [OrderQueryRequest::class, OrderDeleteRequest::class],
            Configuration::TYPE_CUSTOM_OBJECT => [CustomObjectQueryRequest::class, CustomObjectDeleteRequest::class],
        ];

        $logger->debug(
            'Predicates to filter.',
            ['predicates' => $predicates, 'simulated' => $simulate]
        );

        if ($simulate) {
            $output->writeln('<comment>Just simulating.</comment>');
        }

        /** @var ProgressBar|null $progress */
        $progress = null;
        $table = new Table($output);
        $table->setHeaders(['Type', 'Predicate', 'First total']);

        foreach ($predicates as $type => $queries) {
            $output->writeln('');
            $output->writeln('<info>Run cleanup for type ' . $type . '.</info>');
            list($queryClass, $deleteClass) = $map[$type];

            if ($progress) {
                $progress->clear();
                $progress = null;
            }

            /** @var AbstractQueryRequest $request */
            $request = new $queryClass();

            $queryString = $this->parseQueryString($queries);

            $logger->debug('Fetching rows.', ['query' => $queryString, 'type' => $type]);

            $request->where($queryString);

            $response = $this->client->execute($request);

            $table->addRow(
                [$type, $queryString, ($response instanceof PagedQueryResponse) ? $response->getTotal() : 0]
            );

            while (!$simulate && ($response instanceof PagedQueryResponse) && ($response->getCount())) {
                if (!$progress) {
                    $progress = new ProgressBar($output);

                    $progress->start($response->getTotal());
                }

                $failedIds = [];

                $logger->debug(
                    'Found rows.',
                    ['count' => $response->getCount(), 'query' => $queryString, 'type' => $type]
                );

                set_time_limit(0);

                /** @var Resource $object */
                foreach ($response->toObject() as $object) {
                    $logger->debug('Removed row.', ['object' => $object]);

                    $this->client->addBatchRequest($deleteClass::ofIdAndVersion(
                        $object->getId(),
                        $object->getVersion()
                    ));

                    $progress->advance();
                }

                $responses = $this->client->executeBatch();

                array_walk($responses, function (ApiResponseInterface $response) use (&$failedIds) {
                    if ($response instanceof ErrorResponse) {
                        $this->logger->error(
                            'Object could not be deleted.',
                            ['errors' => $response->getErrors()->toArray()]
                        );

                        $failedIds[] = $response->getRequest()->getId();
                    }
                });

                if ($failedIds) {
                    $request->where(
                        'id not in (' . implode(',', array_map(function (string $failedId) {
                            return '"' . $failedId . '"';
                        }, $failedIds)) . ')'
                    );
                }

                $logger->debug(
                    'Refetching rows.',
                    [
                        'failedIds' => $failedIds,
                        'query' => urldecode($request->httpRequest()->getUri()->getQuery()),
                        'type' => $type
                    ]
                );

                $response = $this->client->execute($request);
            }

            if ($progress) {
                $progress->finish();
            }

            $output->writeln('');
            $output->writeln('');
        }

        return $table;
    }

    /**
     * Executes the cleanup.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->logger;

        if (!$this->lock()) {
            $output->writeln('<error>The command is already running in another process.</error>');
            $logger->notice('The clean up command is already running.');

            return 0;
        }

        $logger->info('Starting cleanup in command.');

        $this->doCleanUp($output, $input->getOption('simulate'))->render();

        $output->writeln('');

        $logger->info('Finished cleanup in command.');
        $output->writeln('<info>Cleanup finished.</info>');
    }

    /**
     * Returns the used predicates.
     * @return array
     */
    private function getUsedPredicates(): array
    {
        return array_filter($this->predicatesForTypes, function (array $queries): bool {
            return !empty($queries);
        });
    }

    /**
     * Parses the query string.
     * @param array $queries
     * @return string
     */
    private function parseQueryString(array $queries): string
    {
        array_walk($queries, function (&$query) {
            $query = preg_replace_callback(
                '/{{(.*)}}/Uu',
                function (array $hit): string {
                    return date('c', strtotime($hit[1]));
                },
                $query
            );
        });

        $queryString = '(' . implode(') or (', $queries) . ')';

        return $queryString;
    }
}
