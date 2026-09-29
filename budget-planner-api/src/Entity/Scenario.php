<?php

namespace App\Entity;

use App\Repository\ScenarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScenarioRepository::class)]
class Scenario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'scenarios')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Household $household = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $basePeriod = null;

    /**
     * @var Collection<int, ScenarioTransaction>
     */
    #[ORM\OneToMany(targetEntity: ScenarioTransaction::class, mappedBy: 'scenario')]
    private Collection $scenarioTransactions;

    public function __construct()
    {
        $this->scenarioTransactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHousehold(): ?Household
    {
        return $this->household;
    }

    public function setHousehold(?Household $household): static
    {
        $this->household = $household;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBasePeriod(): ?\DateTimeImmutable
    {
        return $this->basePeriod;
    }

    public function setBasePeriod(\DateTimeImmutable $base_period): static
    {
        $this->basePeriod = $base_period;

        return $this;
    }

    /**
     * @return Collection<int, ScenarioTransaction>
     */
    public function getScenarioTransactions(): Collection
    {
        return $this->scenarioTransactions;
    }

    public function addScenarioTransaction(ScenarioTransaction $scenarioTransaction): static
    {
        if (!$this->scenarioTransactions->contains($scenarioTransaction)) {
            $this->scenarioTransactions->add($scenarioTransaction);
            $scenarioTransaction->setScenario($this);
        }

        return $this;
    }

    public function removeScenarioTransaction(ScenarioTransaction $scenarioTransaction): static
    {
        if ($this->scenarioTransactions->removeElement($scenarioTransaction)) {
            // set the owning side to null (unless already changed)
            if ($scenarioTransaction->getScenario() === $this) {
                $scenarioTransaction->setScenario(null);
            }
        }

        return $this;
    }
}
