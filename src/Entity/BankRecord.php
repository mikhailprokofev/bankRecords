<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BankRecordsRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

#[
    ORM\Entity(repositoryClass: BankRecordsRepository::class),
    ORM\Table(name: 'bank_record')
]
class BankRecord implements \JsonSerializable
{
    public function __construct(
        #[
            ORM\Id,
            ORM\Column(type: 'uuid', unique: true),
            ORM\GeneratedValue(strategy: 'CUSTOM'),
            ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')
        ]
        public readonly Uuid $transaction_id,
        #[ORM\Column(type:"string", length:75)]
        public readonly string $client_name,
        #[ORM\Column(type:"string", length:30)]
        public readonly string $product_name,
        #[ORM\Column(type:"float")]
        public readonly float $product_price,
        #[ORM\Column(type:"string", length:30)]
        public readonly string $credit_card_issuer,
        #[ORM\Column(type:"string", length:25)]
        public readonly string $credit_card_number,
        #[ORM\Column(type:"datetime_immutable")]
        public readonly DateTimeImmutable $purhase_date,
    )
    {}
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
    return [
        "transaction_id" => $this->transaction_id,
        "client_name" => $this->client_name,
        "product_name" => $this->product_name,
        "product_price" => $this->product_price,
        "credit_card_issuer" => $this->credit_card_issuer,
        "credit_card_number" => $this->credit_card_number,
        "purhase_date" => $this->purhase_date,
    ];
    }
}




