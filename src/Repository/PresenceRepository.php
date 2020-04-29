<?php

namespace App\Repository;

use App\Entity\Presence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Presence|null find($id, $lockMode = null, $lockVersion = null)
 * @method Presence|null findOneBy(array $criteria, array $orderBy = null)
 * @method Presence[]    findAll()
 * @method Presence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PresenceRepository extends ServiceEntityRepository
{

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * PresenceRepository constructor.
     * @param ManagerRegistry $registry
     * @param EntityManagerInterface $manager
     */
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $manager)
    {
        parent::__construct($registry, Presence::class);
        $this->manager = $manager;
    }

    /**
     * @param int $userId
     * @param bool $isEntry
     * @return bool
     */
    public function checkIn(int $userId, bool $isEntry): bool
    {
        // Using a try catch here to validate whether the information was successfully inserted, there are probably better ways to do this
        try {
            $newEntry = new Presence();
            $newEntry->setUserId($userId)
                ->setDateTime(\DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s')))
                ->setType(($isEntry ? 'in' : 'out'));
            $this->manager->persist($newEntry);
            $this->manager->flush();
            return true;
        }
        catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @param Presence $presence
     * @return bool
     */
    public function updateDateTime(Presence $presence): bool
    {
        try {
            $this->manager->persist($presence);
            $this->manager->flush();
            return true;
        }
        catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Presence $presence
     * @return bool
     */
    public function deleteEntry(Presence $presence): bool
    {
        try {
            $this->manager->remove($presence);
            $this->manager->flush();
            return true;
        }
        catch (\Exception $ex) {
            return false;
        }
    }

}
