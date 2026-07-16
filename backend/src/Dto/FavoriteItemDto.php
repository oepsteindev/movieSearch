<?php

namespace App\Dto;

final readonly class FavoriteItemDto implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $externalId,
        public string $name,
        public string $image,
        public ?int $year,
        public string $addedAt,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'externalId' => $this->externalId,
            'name' => $this->name,
            'image' => $this->image,
            'year' => $this->year,
            'addedAt' => $this->addedAt,
        ];
    }
}
