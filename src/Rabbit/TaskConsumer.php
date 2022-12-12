<?php

namespace App\Rabbit;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Uid\Uuid;
use App\Entity\BankRecord;
use DateTimeImmutable;
use DateTimeZone;
use DateTime;

class TaskConsumer implements ConsumerInterface 
{
    public function __construct(
        private readonly ManagerRegistry $registry,
    )
    {}

    public function execute(AMQPMessage $msg): void
    {
        $this -> manager = $this -> registry -> getManager();
        $message = json_decode($msg->body, true);

        if (empty($this->checkExistence($message['transaction_id']))){
            $this->manager->persist($this->createBankRecord(array_values($message)));
            $this->manager->flush();
            $this->manager->clear();
            return ;
        }
        $uid = $message['transaction_id'];
        $date = new DateTimeImmutable();
        $date = $date->setTimezone(new DateTimeZone('Europe/Moscow'));
        $timestamp = $date->format('Y-m-d H:i:sP');
        return ;
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