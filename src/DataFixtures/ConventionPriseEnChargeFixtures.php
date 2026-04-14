<?php

namespace App\DataFixtures;

use App\Entity\ConventionPriseEnCharge;
use App\Entity\OrganismePriseEnCharge;
use App\Enum\TypePrestationPEC;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ConventionPriseEnChargeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $references = [
            OrganismePriseEnChargeFixtures::ORG_CNSS,
            OrganismePriseEnChargeFixtures::ORG_NIGELEC,
            OrganismePriseEnChargeFixtures::ORG_SONIDEP,
            OrganismePriseEnChargeFixtures::ORG_CIGNA,
            OrganismePriseEnChargeFixtures::ORG_NIGER_TELECOMS,
            OrganismePriseEnChargeFixtures::ORG_ASCOMA,
            OrganismePriseEnChargeFixtures::ORG_SAHAM,
            OrganismePriseEnChargeFixtures::ORG_ISLAMIC_RELIEF,
        ];

        foreach ($references as $reference) {
            /** @var OrganismePriseEnCharge $organisme */
            $organisme = $this->getReference($reference, OrganismePriseEnCharge::class);

            $this->persistConvention($manager, $organisme, TypePrestationPEC::CONSULTATION, 80);
            $this->persistConvention($manager, $organisme, TypePrestationPEC::EXAMEN, 80);
            $this->persistConvention($manager, $organisme, TypePrestationPEC::ACTE, 80);
            $this->persistConvention($manager, $organisme, TypePrestationPEC::SOIN, 80);
            $this->persistConvention($manager, $organisme, TypePrestationPEC::CONSOMMABLE, 80);
            $this->persistConvention($manager, $organisme, TypePrestationPEC::HOSPITALISATION, 100);
            $this->persistConvention($manager, $organisme, TypePrestationPEC::AUTRE, 0);
        }

        $manager->flush();
    }

    private function persistConvention(
        ObjectManager $manager,
        OrganismePriseEnCharge $organisme,
        TypePrestationPEC $type,
        int $taux
    ): void {
        $convention = (new ConventionPriseEnCharge())
            ->setOrganisme($organisme)
            ->setTypePrestation($type)
            ->setTauxCouverture($taux)
            ->setActif(true)
            ->setObservation(sprintf(
                'Convention par défaut : %s = %d%%',
                $type->value,
                $taux
            ));

        $manager->persist($convention);
    }

    public function getDependencies(): array
    {
        return [
            OrganismePriseEnChargeFixtures::class,
        ];
    }
}