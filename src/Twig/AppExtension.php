<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('floor', [$this, 'floorFilter']),
        ];
    }

    public function floorFilter($number)
    {
        return floor($number);
    }
}
