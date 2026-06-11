<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`users`')]
#[UniqueEntity(fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    const ROLE_DEFAULT = 'ROLE_USER';

    const  NOTIFYBY = [
        0 => 'Email',
        1 => 'SMS'
    ];

    const  PROFILS = [
        0 => 'Point focal Pays',
        1 => 'Staff_Caert',
        3 => 'Administrateur'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private $name;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private $email;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $profil;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $password;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $fonction;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updateAt;

    #[ORM\Column(type: 'json', nullable: true)]
    private $roles = [];

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $enable;

    #[ORM\Column(type: 'string', length: 255)]
    private $prenoms;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $notifyBy;

    #[ORM\Column(type: 'string', length: 255, nullable: true, unique: true)]
    private $token;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isVerified;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $tokenCreatedAt;

    #[ORM\OneToMany(targetEntity: Perpetrateurs::class, mappedBy: 'user')]
    private $perpetrateurs;

    #[ORM\OneToMany(targetEntity: MoyenAttaque::class, mappedBy: 'user')]
    private $moyenAttaques;

    #[ORM\OneToMany(targetEntity: Cible::class, mappedBy: 'user')]
    private $cibles;

    #[ORM\OneToMany(targetEntity: Materiaux::class, mappedBy: 'user')]
    private $materiauxes;

    #[ORM\OneToMany(targetEntity: Attaque::class, mappedBy: 'user')]
    private $attaques;

    #[ORM\OneToMany(targetEntity: MaterielAttaque::class, mappedBy: 'user')]
    private $materielAttaques;

    #[ORM\ManyToOne(targetEntity: Pays::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $pays;

    #[ORM\OneToMany(targetEntity: AllData::class, mappedBy: 'user')]
    private $allData;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $organisation;

    #[ORM\OneToMany(targetEntity: Espace::class, mappedBy: 'user')]
    private $espaces;

    public function __construct()
    {
        $this->roles = [];
        $this->createdAt = new \DateTime();
        $this->updateAt = new \DateTime();
        $this->isVerified = false;
        // $this->notifyBy = 0;
        $this->perpetrateurs = new ArrayCollection();
        $this->moyenAttaques = new ArrayCollection();
        $this->cibles = new ArrayCollection();
        $this->materiauxes = new ArrayCollection();
        $this->attaques = new ArrayCollection();
        $this->materielAttaques = new ArrayCollection();
        $this->allData = new ArrayCollection();
        $this->espaces = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

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

    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTimeInterface $updateAt): self
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials(): void
    {
    }

      /**
     *
     * @return string[] The user roles
     */

    public function addRole($role)
    {
        $role = strtoupper($role);
        if ($role === self::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles ?? [];
    }


    public function setRoles(?array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getEnable(): ?bool
    {
        return $this->enable;
    }

    public function setEnable(?bool $enable): self
    {
        $this->enable = $enable;

        return $this;
    }

    public function getPrenoms(): ?string
    {
        return $this->prenoms;
    }

    public function setPrenoms(string $prenoms): self
    {
        $this->prenoms = $prenoms;

        return $this;
    }

    public function getNotifyBy(): ?int
    {
        return $this->notifyBy;
    }

    public function setNotifyBy(int $notifyBy): self
    {
        $this->notifyBy = $notifyBy;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(?bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getTokenCreatedAt(): ?\DateTimeInterface
    {
        return $this->tokenCreatedAt;
    }

    public function setTokenCreatedAt(?\DateTimeInterface $tokenCreatedAt): self
    {
        $this->tokenCreatedAt = $tokenCreatedAt;

        return $this;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(string $fonction): self
    {
        $this->fonction = $fonction;

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

    public function getProfil(): ?string
    {
        return $this->profil;
    }

    public function setProfil(?string $profil): self
    {
        $this->profil = $profil;

        return $this;
    }

    /**
     * @return Collection<int, Perpetrateurs>
     */
    public function getPerpetrateurs(): Collection
    {
        return $this->perpetrateurs;
    }

    public function addPerpetrateur(Perpetrateurs $perpetrateur): self
    {
        if (!$this->perpetrateurs->contains($perpetrateur)) {
            $this->perpetrateurs[] = $perpetrateur;
            $perpetrateur->setUser($this);
        }

        return $this;
    }

    public function removePerpetrateur(Perpetrateurs $perpetrateur): self
    {
        if ($this->perpetrateurs->removeElement($perpetrateur)) {
            // set the owning side to null (unless already changed)
            if ($perpetrateur->getUser() === $this) {
                $perpetrateur->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MoyenAttaque>
     */
    public function getMoyenAttaques(): Collection
    {
        return $this->moyenAttaques;
    }

    public function addMoyenAttaque(MoyenAttaque $moyenAttaque): self
    {
        if (!$this->moyenAttaques->contains($moyenAttaque)) {
            $this->moyenAttaques[] = $moyenAttaque;
            $moyenAttaque->setUser($this);
        }

        return $this;
    }

    public function removeMoyenAttaque(MoyenAttaque $moyenAttaque): self
    {
        if ($this->moyenAttaques->removeElement($moyenAttaque)) {
            // set the owning side to null (unless already changed)
            if ($moyenAttaque->getUser() === $this) {
                $moyenAttaque->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Cible>
     */
    public function getCibles(): Collection
    {
        return $this->cibles;
    }

    public function addCible(Cible $cible): self
    {
        if (!$this->cibles->contains($cible)) {
            $this->cibles[] = $cible;
            $cible->setUser($this);
        }

        return $this;
    }

    public function removeCible(Cible $cible): self
    {
        if ($this->cibles->removeElement($cible)) {
            // set the owning side to null (unless already changed)
            if ($cible->getUser() === $this) {
                $cible->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Materiaux>
     */
    public function getMateriauxes(): Collection
    {
        return $this->materiauxes;
    }

    public function addMateriaux(Materiaux $materiaux): self
    {
        if (!$this->materiauxes->contains($materiaux)) {
            $this->materiauxes[] = $materiaux;
            $materiaux->setUser($this);
        }

        return $this;
    }

    public function removeMateriaux(Materiaux $materiaux): self
    {
        if ($this->materiauxes->removeElement($materiaux)) {
            // set the owning side to null (unless already changed)
            if ($materiaux->getUser() === $this) {
                $materiaux->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Attaque>
     */
    public function getAttaques(): Collection
    {
        return $this->attaques;
    }

    public function addAttaque(Attaque $attaque): self
    {
        if (!$this->attaques->contains($attaque)) {
            $this->attaques[] = $attaque;
            $attaque->setUser($this);
        }

        return $this;
    }

    public function removeAttaque(Attaque $attaque): self
    {
        if ($this->attaques->removeElement($attaque)) {
            // set the owning side to null (unless already changed)
            if ($attaque->getUser() === $this) {
                $attaque->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MaterielAttaque>
     */
    public function getMaterielAttaques(): Collection
    {
        return $this->materielAttaques;
    }

    public function addMaterielAttaque(MaterielAttaque $materielAttaque): self
    {
        if (!$this->materielAttaques->contains($materielAttaque)) {
            $this->materielAttaques[] = $materielAttaque;
            $materielAttaque->setUser($this);
        }

        return $this;
    }

    public function removeMaterielAttaque(MaterielAttaque $materielAttaque): self
    {
        if ($this->materielAttaques->removeElement($materielAttaque)) {
            // set the owning side to null (unless already changed)
            if ($materielAttaque->getUser() === $this) {
                $materielAttaque->setUser(null);
            }
        }

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
            $allData->setUser($this);
        }

        return $this;
    }

    public function removeAllData(AllData $allData): self
    {
        if ($this->allData->removeElement($allData)) {
            // set the owning side to null (unless already changed)
            if ($allData->getUser() === $this) {
                $allData->setUser(null);
            }
        }

        return $this;
    }

    public function getOrganisation(): ?string
    {
        return $this->organisation;
    }

    public function setOrganisation(?string $organisation): self
    {
        $this->organisation = $organisation;

        return $this;
    }

    /**
     * @return Collection<int, Espace>
     */
    public function getEspaces(): Collection
    {
        return $this->espaces;
    }

    public function addEspace(Espace $espace): self
    {
        if (!$this->espaces->contains($espace)) {
            $this->espaces[] = $espace;
            $espace->setUser($this);
        }

        return $this;
    }

    public function removeEspace(Espace $espace): self
    {
        if ($this->espaces->removeElement($espace)) {
            // set the owning side to null (unless already changed)
            if ($espace->getUser() === $this) {
                $espace->setUser(null);
            }
        }

        return $this;
    }
}
