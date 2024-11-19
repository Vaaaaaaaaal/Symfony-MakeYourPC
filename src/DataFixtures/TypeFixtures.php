<?php

namespace App\DataFixtures;

use App\Entity\Type;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TypeFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $types = [
            'Carte mère',
            'Carte graphique',
            'Stockage',
            'Alimentation',
            'Processeur',
            'Mémoire RAM',
            'Boîtier'
        ];

        foreach ($types as $typeName) {
            $type = new Type();
            $type->setName($typeName);
            $manager->persist($type);
        }

        $manager->flush();
    }
} 