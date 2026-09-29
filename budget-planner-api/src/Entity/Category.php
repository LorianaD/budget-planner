<?php

namespace App\Entity;

use App\Enum\CategoryEnvelope;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Household $household = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100, enumType: CategoryEnvelope::class)]
    private ?CategoryEnvelope $envelope = null;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'category')]
    private Collection $transactions;

    /**
     * @var Collection<int, ScenarioTransaction>
     */
    #[ORM\OneToMany(targetEntity: ScenarioTransaction::class, mappedBy: 'category')]
    private Collection $scenarioTransactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
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

    public function getEnvelope(): ?string
    {
        return $this->envelope;
    }

    public function setEnvelope(?string $envelope): static
    {
        $this->envelope = $envelope;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setCategory($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getCategory() === $this) {
                $transaction->setCategory(null);
            }
        }

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
            $scenarioTransaction->setCategory($this);
        }

        return $this;
    }

    public function removeScenarioTransaction(ScenarioTransaction $scenarioTransaction): static
    {
        if ($this->scenarioTransactions->removeElement($scenarioTransaction)) {
            // set the owning side to null (unless already changed)
            if ($scenarioTransaction->getCategory() === $this) {
                $scenarioTransaction->setCategory(null);
            }
        }

        return $this;
    }
}
