<?php

namespace App\Dto;

final readonly class FavoriteListDetailDto implements \JsonSerializable
{
    /**
     * @param FavoriteItemDto[] $items
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $items,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'items' => $this->items,
        ];
    }
}
