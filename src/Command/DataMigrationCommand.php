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

class DataMigrationCommand extends Command
{
    const DEFAULT_BATCH_SIZE = 1000;

    protected static $defaultName = 'app:data-migration';

    private $objectManager;
    private $container;
    private $premiumSourceIdS = [];

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
        $q = $this->objectManager->getRepository(Offer::class)->getNoneOrderedOffersQuery();
        $iterableResult = $q->iterate();
        $i = 1;
        $currentProduct = null;
        foreach ($iterableResult as $iterateKey => $row) {
            $row = $this->fixAndPrepareRow($row);

            $offer = $row[0][0];
            $sourceId = $row[1]['sourceId'];
            $productId = $row[1]['productId'];

            $output->writeln("offer id:{$offer->getId()} sourceId: {$sourceId}, productId: {$productId}");


            $isPremiumSource = $this->isPremiumSource($row[1]['sourceId']);

            if(!$currentProduct || $currentProduct->getId() != $productId){
                $currentProduct = $this->getProduct($productId);
            }
            $productLastOrderNumber = $currentProduct->getLastOrderNumber() ?? 0;
            $orderNumber = $isPremiumSource ? 0 : $productLastOrderNumber + 1;
            $currentProduct->setLastOrderNumber($orderNumber > 0 ? $orderNumber : $productLastOrderNumber);
            $offer->setOrderNumber($orderNumber);
            $output->writeln("Premium source: {$isPremiumSource} Offer Order Number:{$orderNumber} Product Last Order Number: {$productLastOrderNumber}");

            if (($i % self::DEFAULT_BATCH_SIZE) === 0) {
                $output->writeln("start updating database");
                $this->objectManager->flush(); // Executes all updates.
                $this->objectManager->clear();
                $output->writeln("finish updating database");
                $currentProduct = null;

            }
            ++$i;
        }
        return 0;
    }

    /**
     * Iterable result combine productId and source id with offer for first row but for the others stores
     * them on another index so this function is fix this problem.
     *
     * @param array $row
     * @return array
     */
    private function fixAndPrepareRow(array $row):array {
        $row = array_values($row);
        if(!isset($row[1])){
            $row[1]['productId'] = $row[0]['productId'];
            $row[1]['sourceId'] = $row[0]['sourceId'];
        }
        return $row;
    }

    private function getPremiumSourceIds()
    {
        $premiumIds = $this->objectManager->getRepository(Source::class)->getPremiumSourceIds();
        return array_map(function ($source) {
            return (int)$source["id"];
        }, $premiumIds);
    }

    private function isPremiumSource($sourceId){
        return in_array($sourceId, $this->premiumSourceIdS);
    }

    /**
     * @param $productId
     * @return Product
     */
    private function getProduct($productId):product{
        return $this->objectManager->getRepository(Product::class)->find($productId);
    }

}