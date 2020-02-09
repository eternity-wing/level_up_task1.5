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

    /**
     * AppFixtures constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager):void
    {
        $this->manager = $manager;

        $this->loadUsers($manager);
        $this->loadSources($manager);
        $this->loadCustomers($manager);
        $manager->flush();
        $this->loadProducts($manager);
        $manager->flush();
        $this->loadOffers($manager);
        $manager->flush();
    }


    /**
     * @param ObjectManager $manager
     */
    private function loadUsers(ObjectManager $manager):void{
        for ($i = 1; $i <= $this->container->getParameter('total_users_number'); $i++) {
            $user = new User();
            $user->setUsername("user-{$i}");
            $manager->persist($user);
        }
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadSources(ObjectManager $manager):void{
        $premiumSourcePossibility = $this->container->getParameter('premium_sources_possibility');
        for ($i = 1; $i <= $this->container->getParameter('total_sources_number'); $i++) {
            $source = new Source();
            $source->setName("source-{$i}");
            $isPremiumByChance = rand(1, 100) < $premiumSourcePossibility;
            $source->setIsPremium($isPremiumByChance);
            $manager->persist($source);
        }
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadCustomers(ObjectManager $manager):void{
        for ($i = 1; $i <= $this->container->getParameter('total_customers_number'); $i++) {
            $customer = new Customer();
            $customer->setBrand("customer-{$i}");
            $manager->persist($customer);
        }
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadProducts(ObjectManager $manager):void{
        list($customerMinId, $customerMaxId) = $this->getCustomerMinAndMax($manager);
        for ($i = 1; $i <= $this->container->getParameter('total_products_number'); $i++) {
            $randomCustomerId = rand($customerMinId, $customerMaxId);
            $customer = $manager->getRepository(Customer::class)->find($randomCustomerId);
            if($customer == null){
                dd($customer, $randomCustomerId);
            }
            $product = new Product();
            $product->setName("product-{$i}");
            $product->setCustomer($customer);
            $manager->persist($product);
        }
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadOffers(ObjectManager $manager):void{
        list($sourceMinId, $sourceMaxId) = $this->getSourceMinAndMax($manager);
        list($productMinId, $productMaxId) = $this->getProductMinAndMax($manager);
        list($userMinId, $userMaxId) = $this->getUserMinAndMax($manager);

        for ($i = 1; $i <= $this->container->getParameter('total_offers_number'); $i++) {

            $offer = new Offer();

            $randomSourceId = rand($sourceMinId, $sourceMaxId);
            $source = $manager->getRepository(Source::class)->find($randomSourceId);
            $offer->setSource($source);

            $randomProductId = rand($productMinId, $productMaxId);
            $product = $manager->getRepository(Product::class)->find($randomProductId);
            $offer->setProduct($product);

            $randomUserId = rand($userMinId, $userMaxId);
            $user = $manager->getRepository(User::class)->find($randomUserId);
            $offer->setUser($user);

            $manager->persist($offer);
        }
    }

    /**
     * @param ObjectManager $manager
     * @return array
     */
    private function getUserMinAndMax(ObjectManager $manager):array{
        $totalUsersNumber = $this->container->getParameter('total_users_number');
        $firstUser = $manager->getRepository(User::class)->findOneBy([],['id' => 'ASC']);
        $firstUserId = $firstUser->getId();
        return [$firstUserId, $firstUserId + $totalUsersNumber - 1];
    }

    /**
     * @param ObjectManager $manager
     * @return array
     */
    private function getProductMinAndMax(ObjectManager $manager):array{
        $totalProductsNumber = $this->container->getParameter('total_products_number');
        $firstProduct = $manager->getRepository(Product::class)->findOneBy([],['id' => 'ASC']);
        $firstId = $firstProduct->getId();
        return [$firstId, $firstId + $totalProductsNumber - 1];
    }

    /**
     * @param ObjectManager $manager
     * @return array
     */
    private function getSourceMinAndMax(ObjectManager $manager):array{
        $totalSourcesNumber = $this->container->getParameter('total_sources_number');
        $firstSource = $manager->getRepository(Source::class)->findOneBy([],['id' => 'ASC']);
        $firstId = $firstSource->getId();
        return [$firstId, $firstId + $totalSourcesNumber - 1];
    }

    /**
     * @param ObjectManager $manager
     * @return array
     */
    private function getCustomerMinAndMax(ObjectManager $manager):array {
        $totalCustomersNumber = $this->container->getParameter('total_customers_number');
        $firstCustomer = $manager->getRepository(Customer::class)->findOneBy([],['id' => 'ASC']);
        $firstId = $firstCustomer->getId();
        return [$firstId, $firstId + $totalCustomersNumber - 1];
    }

}
