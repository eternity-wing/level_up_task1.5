<?php


namespace App\Tests\Command;

use App\Entity\Offer;
use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use App\Command\DataMigrationCommand;
use Liip\TestFixturesBundle\Test\FixturesTrait;

class DataMigrationCommandTest extends BaseKernelTest
{
    use FixturesTrait;

    private const PROVIDED_PRODUCTS_COUNT = 10;

    protected function setUp():void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
    }


    public function testExecute()
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $this->loadFixtures(array(
            'App\DataFixtures\AppFixtures',
        ));

        $command = $application->find(DataMigrationCommand::getDefaultName());
        $commandTester = new CommandTester($command);
        $result = $commandTester->execute([], ['--env' => 'test']);
        $this->assertEquals($result, 0, "Command returns 0 on successful running.");

    }

    public function testDatabaseMustNotContainNoneOrderedOffer(){
        $noneOrderedOffer = $this->getEntityManager()->getRepository(Offer::class)->findOneNoneOrderedOffer();
        $this->assertNull($noneOrderedOffer);
    }

    public function testCheckRandomProductsLastOrderNumberValidity()
    {

        $lastProduct = $this->getRepository(Product::class)->findOneBy([], ['id' => 'desc']);
        $firstProduct = $this->getRepository(Product::class)->findOneBy([], ['id' => 'asc']);

        $randomProductIds = [];

        for($i = 0 ; $i < self::PROVIDED_PRODUCTS_COUNT ; $i++){
            $randomProductIds[] = rand($firstProduct->getId(), $lastProduct->getId());
        }

        foreach ($this->getRepository(Product::class)->findBy(['id' => $randomProductIds]) as $product){
            if($product->getLastOrderNumber() == null){
                $offersCount = $this->getRepository(Offer::class)->findProductOffersCount($product);
            }else{
                $offersCount = $this->getRepository(Offer::class)->findProductFreeSourceOffersCount($product);
            }
            $this->assertEquals($product->getLastOrderNumber() ?? 0, $offersCount);
        }
    }

}