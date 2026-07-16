<?php

namespace App\Dto;

final readonly class FavoriteListDto implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public int $itemCount,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'itemCount' => $this->itemCount,
        ];
    }
}
