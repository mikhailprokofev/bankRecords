<?php 

declare(strict_types=1);

namespace App\Message;

use DateTime;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;
use App\Message\BankRecordsFile;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BankRecordsHandler
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        public \OldSound\RabbitMqBundle\RabbitMq\Producer $taskProducer,
    )
    {}

    public function __invoke(BankRecordsFile $file): void
    {
        $this -> manager = $this -> registry -> getManager();
        $this -> reader = $file -> getReader();

        foreach ($this -> reader -> getSheetIterator() as $sheet) {
            $test = $sheet -> getRowIterator();
            $test -> rewind();
            $test -> next();

            while (!empty($test -> valid())){
                $this -> taskProducer 
                    -> publish($this->createJson($test ->current()->toArray()));
                $test -> next();
            }

            // foreach ($test as $index => $row) {

            //     // if ($index != 1){
            //         $this -> taskProducer 
            //             -> publish($this->createJson($row -> toArray()));
            //     // }
            // }
        }

        $this -> reader -> close();
    }

    public function createJson($cells): string|false
    {
        return json_encode([
            'transaction_id'    => Uuid::fromString($cells[0]),
            'client_name'       => $cells[1],
            'product_name'      => $cells[2],
            'product_price'     => (float)$cells[3],
            'credit_card_issuer'=> $cells[4],
            'credit_card_number'=> $cells[5],
            'purhase_date'      => $cells[6]
        ]);
    }
}