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
                $result[] = $row;
            }
            
            if ($count == 100){
                $this -> taskProducer 
                    -> publish(json_encode($result));
                $this -> logger -> info(json_encode($result));
                $result = array();
            }
            $count++;
        }
        $this -> taskProducer 
            -> publish(json_encode($result)); 
        $this->logger->info(json_encode($result));
        unset($result);
    }
}