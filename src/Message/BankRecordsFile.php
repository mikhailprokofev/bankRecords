<?php 

declare(strict_types=1);

namespace App\Message;

const FILE_FOLDERS = './public/files/';

use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;


class BankRecordsFile
{
    public function __construct(
        public string $fileName,
    )
    {}

    public function getReader(): ReaderInterface
    {
        $reader = ReaderEntityFactory::createCSVReader();
        $reader->open(FILE_FOLDERS . $this->fileName);       
        return $reader;
    }
}