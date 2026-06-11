<?php

namespace App\Entity;

use App\Repository\MaterielAttaqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MaterielAttaqueRepository::class)]
class MaterielAttaque
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    private $libelle;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'materielAttaques')]
    private $user;

    #[ORM\OneToMany(targetEntity: AllData::class, mappedBy: 'materielAttaque')]
    private $allData;

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

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

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
            $allData->setMaterielAttaque($this);
        }

        return $this;
    }

    public function removeAllData(AllData $allData): self
    {
        if ($this->allData->removeElement($allData)) {
            // set the owning side to null (unless already changed)
            if ($allData->getMaterielAttaque() === $this) {
                $allData->setMaterielAttaque(null);
            }
        }

        return $this;
    }

    public function __toString() {
        return $this->libelle;
    }
}
