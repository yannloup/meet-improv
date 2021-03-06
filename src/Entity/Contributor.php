<?php

/**
 * @author yannloup
 *
 */
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use App\Entity\OpenDate;

/**
 * The Contributor abstract class represents any form of event contributor.
 * It can be a Troup, a Team or an Improvisator.
 *
 * @ORM\Entity
 * @ORM\Table(name="contributor", indexes={@ORM\Index(name="type_idx", columns={"type"})})
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=3)
 * @ORM\DiscriminatorMap({
 *     "TRO"="Troupe",
 *     "TEA"="Team",
 *     "IMP"="Improvisator",
 *     "Grou" = "ImprovGroup"
 * })
 */
abstract class Contributor
{
    use TimestampableEntity;
    
    
    const TYPE_TROUPE = "troupe";
    const TYPE_TEAM  = "team";
    const TYPE_IMPROVISATOR = "improvisator";

    abstract public function getType();
    abstract public function isImprovGroup();
    
    
    /**
     * @ORM\Id()
     * @ORM\Column(type="uuid_binary")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $name;


    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description='';


    /**
     * @ORM\Column(type="string", length=70)   
     */
    private $shortName;

    /**
     * @ORM\Column(type="string", length=100, unique=true)
     * @Gedmo\Slug(fields={"shortName"})
     */
    private $identifier; 
    
    /**
     * @ORM\Column(type="string", length=100)
     */
    private $location='';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $createdBy;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="superAdminOfContributors")
     * @ORM\JoinTable(name="contributors_super_admins")
     */
    private $superAdmins;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="adminOfContributors")
     * @ORM\JoinTable(name="contributors_admins")
     */
    private $admins;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OpenDate", mappedBy="owner")
     */
    private $ownedOpenDates;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\OpenDate", mappedBy="invitedContributors")
     */
    private $invitedToOpenDates;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bannerPicUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $profilePicUrl;
    
    public function getLocation(): ?string
    {
        return $this->location;
    }
    
    public function setLocation(string $location): self
    {
        $this->location = $location;
        
        return $this;
    }
    
    public function  __construct(){
        $this->id = Uuid::uuid4();
        $this->superAdmins = new ArrayCollection();
        $this->admins = new ArrayCollection();
        $this->ownedOpenDates = new ArrayCollection();
        $this->invitedToOpenDates = new ArrayCollection();
    }

    public function getId()
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
  

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): self
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getSuperAdmins(): Collection
    {
        return $this->superAdmins;
    }

    public function addSuperAdmin(User $superAdmin): self
    {
        if (!$this->superAdmins->contains($superAdmin)) {
            $this->superAdmins[] = $superAdmin;
        }

        return $this;
    }

    public function removeSuperAdmin(User $superAdmin): self
    {
        if ($this->superAdmins->contains($superAdmin)) {
            $this->superAdmins->removeElement($superAdmin);
        }

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getAdmins(): Collection
    {
        return $this->admins;
    }

    public function addAdmin(User $admin): self
    {
        if (!$this->admins->contains($admin)) {
            $this->admins[] = $admin;
        }

        return $this;
    }

    public function removeAdmin(User $admin): self
    {
        if ($this->admins->contains($admin)) {
            $this->admins->removeElement($admin);
        }

        return $this;
    }

    /**
     * @return Collection|OpenDate[]
     */
    public function getOwnedOpenDates(): Collection
    {
        return $this->ownedOpenDates;
    }
    
    /**
     * @return Collection|OpenDate[]
     */
    public function getPublicOwnedOpenDates(): Collection
    {
        
        
        $publicOwnedOpenDates = new ArrayCollection();
        
        foreach ($this->ownedOpenDates->getValues() as $ownedOpenDate){
            /** var OpenDate $ownedOpenDate */
            if($ownedOpenDate->isPublic()){
                $publicOwnedOpenDates->add($ownedOpenDate);
            }
        }
        
        if($this->getType() == self::TYPE_TROUPE){
            /** var Troupe $this */
            $teams = $this->getTeams();
            
            foreach ($teams as $team){
                foreach ($team->ownedOpenDates->getValues() as $ownedOpenDate){
                    /** var OpenDate $ownedOpenDate */
                    if($ownedOpenDate->isPublic()){
                        $publicOwnedOpenDates->add($ownedOpenDate);
                    }
                }
            }
        }
        
        
        return $publicOwnedOpenDates;
    }

    public function addOwnedOpenDate(OpenDate $ownedOpenDate): self
    {
        if (!$this->ownedOpenDates->contains($ownedOpenDate)) {
            $this->ownedOpenDates[] = $ownedOpenDate;
            $ownedOpenDate->setOwner($this);
        }

        return $this;
    }

    public function removeOwnedOpenDate(OpenDate $ownedOpenDate): self
    {
        if ($this->ownedOpenDates->contains($ownedOpenDate)) {
            $this->ownedOpenDates->removeElement($ownedOpenDate);
            // set the owning side to null (unless already changed)
            if ($ownedOpenDate->getOwner() === $this) {
                $ownedOpenDate->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OpenDate[]
     */
    public function getInvitedToOpenDates(): Collection
    {
        return $this->invitedToOpenDates;
    }

    public function addInvitedToOpenDate(OpenDate $invitedToOpenDate): self
    {
        if (!$this->invitedToOpenDates->contains($invitedToOpenDate)) {
            $this->invitedToOpenDates[] = $invitedToOpenDate;
            $invitedToOpenDate->addInvitedContributor($this);
        }

        return $this;
    }

    public function removeInvitedToOpenDate(OpenDate $invitedToOpenDate): self
    {
        if ($this->invitedToOpenDates->contains($invitedToOpenDate)) {
            $this->invitedToOpenDates->removeElement($invitedToOpenDate);
            $invitedToOpenDate->removeInvitedContributor($this);
        }

        return $this;
    }

    public function getBannerPicUrl(): ?string
    {
        return $this->bannerPicUrl;
    }

    public function setBannerPicUrl(?string $bannerPicUrl): self
    {
        $this->bannerPicUrl = $bannerPicUrl;

        return $this;
    }

    public function getProfilePicUrl(): ?string
    {
        return $this->profilePicUrl;
    }

    public function setProfilePicUrl(?string $profilePicUrl): self
    {
        $this->profilePicUrl = $profilePicUrl;

        return $this;
    }
}

