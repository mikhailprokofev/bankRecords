<?php

namespace App\Rabbit;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Uid\Uuid;
use Psr\Log\LoggerInterface;
use App\Entity\BankRecord;
use DateTimeImmutable;
use DateTimeZone;
use DateTime;

class TaskConsumer implements ConsumerInterface 
{
    public function __construct(
        protected readonly ManagerRegistry $registry,
        protected LoggerInterface $logger,
    )
    {}

    public function execute(AMQPMessage $msg): void
    {
        $this -> manager = $this -> registry -> getManager();
        $this -> manager->clear();

        $message = json_decode($msg->body, true);

        foreach ($message as $row) {
            $record = explode(',', $row, 7);
            if (empty($this->checkExistence($record[0]))){
                $this->manager->persist($this->createBankRecord($record));
            } else {
                $uid = $record[0];
                $date = new DateTimeImmutable();
                $date = $date->setTimezone(new DateTimeZone('Europe/Moscow'));
                $timestamp = $date->format('Y-m-d H:i:sP');
                $this->logger->info("$timestamp\t | Uuid = $uid is exist in DB {$row}");
            }
        }
        $this->manager->flush();
        $this->manager->clear();
    }

    public function createBankRecord($cells): BankRecord
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

    public function checkExistence(string $uuid): BankRecord|null
    {
        $record = $this -> manager
            -> getRepository(BankRecord::class)
                -> findOneBy(['transaction_id' => Uuid::fromString($uuid)]);
        return $record;
    }

}