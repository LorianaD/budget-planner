<?php

namespace App\Entity;

use App\Enum\TransactionFrequency;
use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 20, enumType: TransactionType::class)]
    private ?TransactionType $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isRecurring = null;

    #[ORM\Column(length: 20, nullable: true, enumType: TransactionFrequency::class)]
    private ?TransactionFrequency $frequency = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $commitmentEndDate = null;

    /**
     * @var Collection<int, ScenarioTransaction>
     */
    #[ORM\OneToMany(targetEntity: ScenarioTransaction::class, mappedBy: 'transaction')]
    private Collection $scenarioTransactions;

    public function __construct()
    {
        $this->scenarioTransactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): ?TransactionType
    {
        return $this->type;
    }

    public function setType(TransactionType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function isRecurring(): ?bool
    {
        return $this->isRecurring;
    }

    public function setIsRecurring(bool $isRecurring): static
    {
        $this->isRecurring = $isRecurring;

        return $this;
    }

    public function getFrequency(): ?TransactionFrequency
    {
        return $this->frequency;
    }

    public function setFrequency(?TransactionFrequency $frequency): static
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getCommitmentEndDate(): ?\DateTimeImmutable
    {
        return $this->commitmentEndDate;
    }

    public function setCommitmentEndDate(?\DateTimeImmutable $commitmentEndDate): static
    {
        $this->commitmentEndDate = $commitmentEndDate;

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
            $scenarioTransaction->setTransaction($this);
        }

        return $this;
    }

    public function removeScenarioTransaction(ScenarioTransaction $scenarioTransaction): static
    {
        if ($this->scenarioTransactions->removeElement($scenarioTransaction)) {
            // set the owning side to null (unless already changed)
            if ($scenarioTransaction->getTransaction() === $this) {
                $scenarioTransaction->setTransaction(null);
            }
        }

        return $this;
    }
}
