<?php

namespace App\Dto;

final readonly class MovieDto implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public string $image,
        public ?int $year = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'year' => $this->year,
        ];
    }
}
