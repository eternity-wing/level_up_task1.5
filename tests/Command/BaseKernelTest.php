<?php


namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BaseKernelTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager():\Doctrine\ORM\EntityManager{
        return $this->entityManager;
    }

    /**
     * @param string $class
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository(string $class):\Doctrine\ORM\EntityRepository{
        return $this->getEntityManager()->getRepository($class);
    }

}