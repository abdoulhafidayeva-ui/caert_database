<?php

namespace App\Entity;

use App\Repository\EspaceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EspaceRepository::class)]
class Espace
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $libelle;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'espaces')]
    private $user;

    #[ORM\OneToMany(targetEntity: AllData::class, mappedBy: 'espace')]
    private $allData;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
        $this->allData = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, AllData>
     */
    public function getAllData(): Collection
    {
        return $this->allData;
    }

    public function addAllData(AllData $allData): self
    {
        if (!$this->allData->contains($allData)) {
            $this->allData[] = $allData;
            $allData->setEspace($this);
        }

        return $this;
    }

    public function removeAllData(AllData $allData): self
    {
        if ($this->allData->removeElement($allData)) {
            // set the owning side to null (unless already changed)
            if ($allData->getEspace() === $this) {
                $allData->setEspace(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function __toString() {
        return $this->libelle;
    }
}
