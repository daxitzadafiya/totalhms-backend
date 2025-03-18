<?php

namespace App\Services;


class CarouselService
{
    protected $items;

    public function __construct()
    {

        $this->items[]= [
            "src" => "vendor/wrappixel/monster-admin/4.2.1/assets/images/big/img6.jpg",
            "title" => "First title goes here",
            "text" => "this is the subcontent you can use this1",
            "alt" => "Alt1"
        ];

        $this->items[] = [
            "src" => "vendor/wrappixel/monster-admin/4.2.1/assets/images/big/img3.jpg",
            "title" => "Second title goes here",
            "text" => "this is the subcontent you can use this2",
            "alt" => "Alt2"
        ];

        $this->items[] = [
            "src" => "vendor/wrappixel/monster-admin/4.2.1/assets/images/big/img4.jpg",
            "title" => "Third title goes here",
            "text" => "this is the subcontent you can use this3",
            "alt" => "Alt3"
        ];

    }

    public function get()
    {
        return $this->items;
    }
}
