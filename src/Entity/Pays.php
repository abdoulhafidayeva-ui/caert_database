<?php

namespace App\Entity;

use App\Repository\PaysRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaysRepository::class)]
class Pays
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $libelle;

    #[ORM\Column(type: 'string', length: 255)]
    private $code;

    #[ORM\Column(type: 'string', length: 255)]
    private $capitale;

    #[ORM\ManyToOne(targetEntity: Region::class, inversedBy: 'pays')]
    private $region;

    #[ORM\OneToMany(targetEntity: AllData::class, mappedBy: 'pays')]
    private $allData;

    public function __construct()
    {
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCapitale(): ?string
    {
        return $this->capitale;
    }

    public function setCapitale(string $capitale): self
    {
        $this->capitale = $capitale;

        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): self
    {
        $this->region = $region;

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
            $allData->setPays($this);
        }

        return $this;
    }

    public function removeAllData(AllData $allData): self
    {
        if ($this->allData->removeElement($allData)) {
            // set the owning side to null (unless already changed)
            if ($allData->getPays() === $this) {
                $allData->setPays(null);
            }
        }

        return $this;
    }

    public function __toString() {
        return $this->libelle;
    }
}
