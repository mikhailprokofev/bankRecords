<?php 

declare(strict_types=1);

namespace App\Message;

use Psr\Log\LoggerInterface;
use App\Message\BankRecordsFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BankRecordsHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private \OldSound\RabbitMqBundle\RabbitMq\Producer $taskProducer,
    )
    {}

    public function __invoke(BankRecordsFile $file): void
    {
        $reader = $file -> getFileStream();
        fgets($reader);

        $result = array();
        $count = 0;
        while($row = fgets($reader)){
            if ($row != "") {
                $transaction_id = explode(',',$row)[0];
                if (!empty($result[$transaction_id])){
                    $this->logger->info("Uuid=$transaction_id duplicated in this part of file;");
                } else {
                    $result[$transaction_id] = $row;
                }
            }
            if ($count == 1000){
                $resultjs = json_encode($result);
                $this -> taskProducer 
                    -> publish($resultjs);
                $this -> logger -> info($resultjs);
                $result = array();
                $count = 0;
            }
            $count++;
        }
        $this -> taskProducer 
            -> publish(json_encode($result)); 
        $this->logger->info(json_encode($result));
        unset($result);
    }
}