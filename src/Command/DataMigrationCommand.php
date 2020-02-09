<?php


namespace App\Command;

use App\Entity\Offer;
use App\Entity\Product;
use App\Entity\Source;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class DataMigrationCommand
 * @package App\Command
 * @author Wings <eternity.mr8@gmail.com>
 */
class DataMigrationCommand extends Command
{
    /**
     * @var int
     */
    const DEFAULT_BATCH_SIZE = 1000;

    protected static $defaultName = 'app:data-migration';

    /**
     * @var EntityManagerInterface
     */
    private $objectManager;
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var array
     */
    private $premiumSourceIdS = [];


    /**
     * @var OutputInterface
     */
    private $outputInterface;

    /**
     * @var Product|null
     */
    private $activeProduct = null;

    /**
     * DataMigrationCommand constructor.
     * @param EntityManagerInterface $objectManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $objectManager, ContainerInterface $container)
    {
        $this->objectManager = $objectManager;
        $this->container = $container;
        $this->premiumSourceIdS = $this->getPremiumSourceIds();
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Migrating database data.')
            ->setHelp('This command migrates database from state T1 to state T2');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setOutputInterface($output);
        $q = $this->objectManager->getRepository(Offer::class)->getNoneOrderedOffersQuery();
        $iterableResult = $q->iterate();
        $iterationIndex = 1;
        $isReachedToBunchSize = null;
        foreach ($iterableResult as $row) {

            $this->updateFetchedRow($this->fixAndPrepareRow($row));

            $isReachedToBunchSize = ($iterationIndex % self::DEFAULT_BATCH_SIZE) === 0;
            if ($isReachedToBunchSize) {
                $this->flushAndClearObjectManager();
                $this->activeProduct = null;
            }
            ++$iterationIndex;
        }
        $isBunchNotUpdated = $isReachedToBunchSize === false;
        if ($isBunchNotUpdated) {
            $this->flushAndClearObjectManager();
        }

        return 0;
    }

    /**
     * @param $row
     */
    private function updateFetchedRow($row): void
    {
        $offer = $row[0][0];
        $sourceId = $row[1]['sourceId'];
        $productId = $row[1]['productId'];

        $this->outputInterface->writeln("offer id:{$offer->getId()} sourceId: {$sourceId}, productId: {$productId}");


        $isPremiumSource = $this->isPremiumSource($row[1]['sourceId']);

        if (!$this->activeProduct || $this->activeProduct->getId() != $productId) {
            $this->activeProduct = $this->getProduct($productId);
        }
        $productLastOrderNumber = $this->activeProduct->getLastOrderNumber() ?? 0;
        $orderNumber = $isPremiumSource ? 0 : $productLastOrderNumber + 1;
        $this->activeProduct->setLastOrderNumber($orderNumber > 0 ? $orderNumber : $productLastOrderNumber);
        $offer->setOrderNumber($orderNumber);
        $this->outputInterface->writeln("Premium source: {$isPremiumSource} Offer Order Number:{$orderNumber} Product Last Order Number: {$productLastOrderNumber}");
    }

    /**
     *
     */
    private function flushAndClearObjectManager(): void
    {
        $this->outputInterface->writeln("start updating database");
        $this->objectManager->flush();
        $this->objectManager->clear();
        $this->outputInterface->writeln("finish updating database");
    }

    /**
     * Iterable result combine productId and source id with offer for first row but for the others stores
     * them on another index so this function is fix this problem.
     *
     * @param array $row
     * @return array
     */
    private function fixAndPrepareRow(array $row): array
    {
        $row = array_values($row);
        if (!isset($row[1])) {
            $row[1]['productId'] = $row[0]['productId'];
            $row[1]['sourceId'] = $row[0]['sourceId'];
        }
        return $row;
    }


    /**
     * @return array
     */
    private function getPremiumSourceIds(): array
    {
        $premiumIds = $this->objectManager->getRepository(Source::class)->getPremiumSourceIds();
        return array_map(function ($source) {
            return (int)$source["id"];
        }, $premiumIds);
    }

    /**
     * @param $sourceId
     * @return bool
     */
    private function isPremiumSource($sourceId): bool
    {
        return in_array($sourceId, $this->premiumSourceIdS);
    }

    /**
     * @param $productId
     * @return Product
     */
    private function getProduct($productId): Product
    {
        return $this->objectManager->getRepository(Product::class)->find($productId);
    }

    /**
     * @param OutputInterface $output
     */
    private function setOutputInterface(OutputInterface $output): void
    {
        $this->outputInterface = $output;
    }
}