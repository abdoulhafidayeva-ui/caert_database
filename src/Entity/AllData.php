<?php

namespace App\Entity;

use App\Repository\AllDataRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AllDataRepository::class)]
class AllData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $details;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $mortSecuriteMilitaire;

    #[ORM\Column(type: 'integer')]
    private $mortCivil;

    #[ORM\Column(type: 'integer')]
    private $mortTerroriste;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $disparuSecuriteMilitaire;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $disparuCivil;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $disparuTerroriste;

    #[ORM\Column(type: 'integer')]
    private $blesseSecuriteMilitaire;

    #[ORM\Column(type: 'integer')]
    private $blesseCivil;

    #[ORM\Column(type: 'integer')]
    private $blesseTerroriste;

    #[ORM\Column(type: 'integer')]
    private $totalDeces;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $totalDisparus;

    #[ORM\Column(type: 'integer')]
    private $totalBlesses;

    #[ORM\Column(type: 'integer')]
    private $otages;

    #[ORM\Column(type: 'integer')]
    private $liberes;

    #[ORM\Column(type: 'integer')]
    private $terroristeArretes;

    #[ORM\Column(type: 'string', length: 255)]
    private $autres;

    #[ORM\Column(type: 'string', length: 255)]
    private $remarque;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\ManyToOne(targetEntity: Attaque::class, inversedBy: 'allData')]
    private $attaque;

    #[ORM\ManyToOne(targetEntity: MaterielAttaque::class, inversedBy: 'allData')]
    private $materielAttaque;

    #[ORM\ManyToOne(targetEntity: Cible::class, inversedBy: 'allData')]
    private $cible;

    #[ORM\ManyToOne(targetEntity: Materiaux::class, inversedBy: 'allData')]
    private $materieaux;

    #[ORM\ManyToOne(targetEntity: MoyenAttaque::class, inversedBy: 'allData')]
    private $moyenAttaque;

    #[ORM\ManyToOne(targetEntity: Perpetrateurs::class, inversedBy: 'allData')]
    private $perpetrateur;

    #[ORM\ManyToOne(targetEntity: Pays::class, inversedBy: 'allData')]
    private $pays;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'allData')]
    private $user;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $dateAttaque;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $localite;

    #[ORM\ManyToOne(targetEntity: Espace::class, inversedBy: 'allData')]
    private $espace;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isPublished;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $objetRejet;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(string $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getMortSecuriteMilitaire(): ?int
    {
        return $this->mortSecuriteMilitaire;
    }

    public function setMortSecuriteMilitaire(?int $mortSecuriteMilitaire): self
    {
        $this->mortSecuriteMilitaire = $mortSecuriteMilitaire;

        return $this;
    }

    public function getMortCivil(): ?int
    {
        return $this->mortCivil;
    }

    public function setMortCivil(int $mortCivil): self
    {
        $this->mortCivil = $mortCivil;

        return $this;
    }

    public function getMortTerroriste(): ?int
    {
        return $this->mortTerroriste;
    }

    public function setMortTerroriste(int $mortTerroriste): self
    {
        $this->mortTerroriste = $mortTerroriste;

        return $this;
    }

    public function getDisparuSecuriteMilitaire(): ?int
    {
        return $this->disparuSecuriteMilitaire;
    }

    public function setDisparuSecuriteMilitaire(int $disparuSecuriteMilitaire): self
    {
        $this->disparuSecuriteMilitaire = $disparuSecuriteMilitaire;

        return $this;
    }

    public function getDisparuCivil(): ?int
    {
        return $this->disparuCivil;
    }

    public function setDisparuCivil(int $disparuCivil): self
    {
        $this->disparuCivil = $disparuCivil;

        return $this;
    }

    public function getDisparuTerroriste(): ?int
    {
        return $this->disparuTerroriste;
    }

    public function setDisparuTerroriste(int $disparuTerroriste): self
    {
        $this->disparuTerroriste = $disparuTerroriste;

        return $this;
    }

    public function getBlesseSecuriteMilitaire(): ?int
    {
        return $this->blesseSecuriteMilitaire;
    }

    public function setBlesseSecuriteMilitaire(int $blesseSecuriteMilitaire): self
    {
        $this->blesseSecuriteMilitaire = $blesseSecuriteMilitaire;

        return $this;
    }

    public function getBlesseCivil(): ?int
    {
        return $this->blesseCivil;
    }

    public function setBlesseCivil(int $blesseCivil): self
    {
        $this->blesseCivil = $blesseCivil;

        return $this;
    }

    public function getBlesseTerroriste(): ?int
    {
        return $this->blesseTerroriste;
    }

    public function setBlesseTerroriste(int $blesseTerroriste): self
    {
        $this->blesseTerroriste = $blesseTerroriste;

        return $this;
    }

    public function getTotalDeces(): ?int
    {
        return $this->totalDeces;
    }

    public function setTotalDeces(int $totalDeces): self
    {
        $this->totalDeces = $totalDeces;

        return $this;
    }

    public function getTotalDisparus(): ?int
    {
        return $this->totalDisparus;
    }

    public function setTotalDisparus(int $totalDisparus): self
    {
        $this->totalDisparus = $totalDisparus;

        return $this;
    }

    public function getTotalBlesses(): ?int
    {
        return $this->totalBlesses;
    }

    public function setTotalBlesses(int $totalBlesses): self
    {
        $this->totalBlesses = $totalBlesses;

        return $this;
    }

    public function getOtages(): ?int
    {
        return $this->otages;
    }

    public function setOtages(int $otages): self
    {
        $this->otages = $otages;

        return $this;
    }

    public function getLiberes(): ?int
    {
        return $this->liberes;
    }

    public function setLiberes(int $liberes): self
    {
        $this->liberes = $liberes;

        return $this;
    }

    public function getTerroristeArretes(): ?int
    {
        return $this->terroristeArretes;
    }

    public function setTerroristeArretes(int $terroristeArretes): self
    {
        $this->terroristeArretes = $terroristeArretes;

        return $this;
    }

    public function getAutres(): ?string
    {
        return $this->autres;
    }

    public function setAutres(string $autres): self
    {
        $this->autres = $autres;

        return $this;
    }

    public function getRemarque(): ?string
    {
        return $this->remarque;
    }

    public function setRemarque(string $remarque): self
    {
        $this->remarque = $remarque;

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

    public function getAttaque(): ?Attaque
    {
        return $this->attaque;
    }

    public function setAttaque(?Attaque $attaque): self
    {
        $this->attaque = $attaque;

        return $this;
    }

    public function getMaterielAttaque(): ?MaterielAttaque
    {
        return $this->materielAttaque;
    }

    public function setMaterielAttaque(?MaterielAttaque $materielAttaque): self
    {
        $this->materielAttaque = $materielAttaque;

        return $this;
    }

    public function getCible(): ?Cible
    {
        return $this->cible;
    }

    public function setCible(?Cible $cible): self
    {
        $this->cible = $cible;

        return $this;
    }

    public function getMaterieaux(): ?Materiaux
    {
        return $this->materieaux;
    }

    public function setMaterieaux(?Materiaux $materieaux): self
    {
        $this->materieaux = $materieaux;

        return $this;
    }

    public function getMoyenAttaque(): ?MoyenAttaque
    {
        return $this->moyenAttaque;
    }

    public function setMoyenAttaque(?MoyenAttaque $moyenAttaque): self
    {
        $this->moyenAttaque = $moyenAttaque;

        return $this;
    }

    public function getPerpetrateur(): ?Perpetrateurs
    {
        return $this->perpetrateur;
    }

    public function setPerpetrateur(?Perpetrateurs $perpetrateur): self
    {
        $this->perpetrateur = $perpetrateur;

        return $this;
    }

    public function getPays(): ?Pays
    {
        return $this->pays;
    }

    public function setPays(?Pays $pays): self
    {
        $this->pays = $pays;

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

    public function getDateAttaque(): ?\DateTimeInterface
    {
        return $this->dateAttaque;
    }

    public function setDateAttaque(?\DateTimeInterface $dateAttaque): self
    {
        $this->dateAttaque = $dateAttaque;

        return $this;
    }

    public function getLocalite(): ?string
    {
        return $this->localite;
    }

    public function setLocalite(?string $localite): self
    {
        $this->localite = $localite;

        return $this;
    }

    public function getEspace(): ?Espace
    {
        return $this->espace;
    }

    public function setEspace(?Espace $espace): self
    {
        $this->espace = $espace;

        return $this;
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(?bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getObjetRejet(): ?string
    {
        return $this->objetRejet;
    }

    public function setObjetRejet(?string $objetRejet): self
    {
        $this->objetRejet = $objetRejet;

        return $this;
    }

}
