<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Offer;
use App\Entity\Product;
use App\Entity\Source;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AppFixtures
 * @package App\DataFixtures
 */
class AppFixtures extends Fixture
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ObjectManager
     */
    private $manager;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }



    public function load(ObjectManager $manager):void
    {
        $this->manager = $manager;

        $this->loadUsers($manager);
        $this->loadSources($manager);
        $this->loadCustomers($manager);
        $manager->flush();
        $this->loadProducts($manager);
        $manager->flush();
//        $this->loadOffers($manager);
//        $manager->flush();
    }



    private function loadUsers(ObjectManager $manager):void{
        for ($i = 1; $i <= $this->container->getParameter('total_users_number'); $i++) {
            $user = new User();
            $user->setUsername("user-{$i}");
            $manager->persist($user);
        }
    }

    private function loadSources(ObjectManager $manager):void{
        $premiumSourcePossibility = $this->container->getParameter('premium_sources_possibility');
        for ($i = 1; $i <= $this->container->getParameter('total_sources_number'); $i++) {
            $source = new Source();
            $source->setName("source-{$i}");
            $isPremiumByChance = rand() < $premiumSourcePossibility;
            $source->setIsPremium($isPremiumByChance);
            $manager->persist($source);
        }
    }

    private function loadCustomers(ObjectManager $manager):void{
        for ($i = 1; $i <= $this->container->getParameter('total_customers_number'); $i++) {
            $customer = new Customer();
            $customer->setBrand("customer-{$i}");
            $manager->persist($customer);
        }
    }

    private function loadProducts(ObjectManager $manager):void{
        $totalCustomersNumber = $this->container->getParameter('total_customers_number');
        for ($i = 1; $i <= $this->container->getParameter('total_products_number'); $i++) {
            $randomCustomerId = rand(1, $totalCustomersNumber);
            $customer = $manager->getRepository(Customer::class)->find($randomCustomerId);
            $product = new Product();
            $product->setName("{$customer}-product-{$i}");
            $product->setCustomer($customer);
            $manager->persist($product);
        }
    }

    private function loadOffers(ObjectManager $manager):void{
        $totalSourcesNumber = $this->container->getParameter('total_sources_number');
        $totalProductsNumber = $this->container->getParameter('total_products_number');
        $totalUsersNumber = $this->container->getParameter('total_users_number');
        for ($i = 1; $i <= $this->container->getParameter('total_offers_number'); $i++) {

            $offer = new Offer();

            $randomSourceId = rand(1, $totalSourcesNumber);
            $source = $manager->getRepository(Source::class)->find($randomSourceId);
            $offer->setSource($source);

            $randomProductId = rand(1, $totalProductsNumber);
            $product = $manager->getRepository(Product::class)->find($randomProductId);
            $offer->setProduct($product);

            $randomUserId = rand(1, $totalUsersNumber);
            $user = $manager->getRepository(User::class)->find($randomUserId);
            $offer->setUser($user);

            $manager->persist($offer);
        }
    }

}
