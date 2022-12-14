<?php

namespace App\Rabbit;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Uid\Uuid;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use App\Entity\BankRecord;
use DateTimeImmutable;
use DateTimeZone;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class TaskConsumer implements ConsumerInterface 
{
    public function __construct(
        protected readonly ManagerRegistry $registry,
        protected EntityManagerInterface $em,
        protected LoggerInterface $logger,
        protected ValidatorInterface $validator,
    )
    {}

    public function execute(AMQPMessage $msg): void
    {
        $this -> manager = $this -> registry -> getManager();
        $this -> manager->clear();

        $message = json_decode($msg->body, true);

        $duplicates_uids = $this->checkDuplicatesUids(array_keys($message));

        foreach($message as $row) {
            $data = explode(',', $row, 7);

            if (in_array($data[0],$duplicates_uids)) {
                $uid = $data[0];
                $date = new DateTimeImmutable();
                $date = $date->setTimezone(new DateTimeZone('Europe/Moscow'));
                $timestamp = $date->format('Y-m-d H:i:sP');
                $this->logger->info("$timestamp\t | Uuid = $uid is exist in DB {$row}");
            } else {
                $record = $this->createBankRecord($data);
                $this->manager->persist($record);
            }
        }
        $this->manager->flush();
        $this->manager->clear();
    }

    public function createBankRecord(array $cells): BankRecord
    {
        return new BankRecord(
            Uuid::fromString($cells[0]),
            $cells[1],
            $cells[2],
            (float)$cells[3],
            $cells[4],
            $cells[5],  
            DateTimeImmutable::createFromMutable(new DateTime($cells[6])),
        );
    }

    public function checkDuplicatesUids(array $uids): array|null
    {
        $qb = $this->em->getConnection()->createQueryBuilder();

        $result = $qb->select('b.transaction_id')->from('bank_record','b')
            ->andWhere($qb->expr()->in('b.transaction_id', ':uids'))
            ->setParameter('uids',  $uids,Connection::PARAM_STR_ARRAY)->fetchFirstColumn();
        return $result;
    }

}