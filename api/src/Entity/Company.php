<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "get"={
 *      		"path"="/companies",
 *              "method"="GET",
 *              "swagger_context" = {
 *              	"description" = "",
 *                  "parameters" = {
 *                      {
 *                          "name" = "kvkNumber",
 *                          "in" = "query",
 *                          "description" = "The number at the chamber of commerce",
 *                          "required" = false,
 *                          "type" : "integer"
 *                      },
 *                      {
 *                          "name" = "branchNumber",
 *                          "in" = "query",
 *                          "description" = "The unique identifier of an registration",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "rsin",
 *                          "in" = "query",
 *                          "description" = "The zip or postcode of the address in a P6 format e.g. 1234AB (without spaces)",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "street",
 *                          "in" = "query",
 *                          "description" = "Street of an address",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "houseNumber",
 *                          "in" = "query",
 *                          "description" = "House number of an address",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "postalCode",
 *                          "in" = "query",
 *                          "description" = "Postal code or ZIP code, example 1000AA",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "city",
 *                          "in" = "query",
 *                          "description" = "City or Town name",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "tradeName",
 *                          "in" = "query",
 *                          "description" = "The name under which a company or a branch of a company operates;",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "includeFormerTradeNames",
 *                          "in" = "query",
 *                          "description" = "Indication (true/false) to search through expired trade names and expired registered names and/or include these in the results. Default is false",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "includeInactiveRegistrations",
 *                          "in" = "query",
 *                          "description" = "Indication (true/false) to include searching through inactive dossiers/deregistered companies. Default is false. Note: History of inactive companies is after 1 January 2012",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "mainBranch",
 *                          "in" = "query",
 *                          "description" = "Search includes main branches. Default is true",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "branch",
 *                          "in" = "query",
 *                          "description" = "Search includes branches. Default is true",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "legalPerson",
 *                          "in" = "query",
 *                          "description" = "Search includes legal persons. Default is true",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "startPage",
 *                          "in" = "query",
 *                          "description" = "Number indicating which page to fetch for pagination. Default = 1, showing the first 10 results",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "site",
 *                          "in" = "query",
 *                          "description" = "Defines the search collection for the query",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "context",
 *                          "in" = "query",
 *                          "description" = "User can optionally add a context to identify his result later on",
 *                          "required" = false,
 *                          "type" : "string"
 *                      },
 *                      {
 *                          "name" = "q",
 *                          "in" = "query",
 *                          "description" = "Free format text search for in the compiled search description.",
 *                          "required" = false,
 *                          "type" : "string"
 *                      }
 *                  }
 *               }
 *          }
 *     },
 *     itemOperations={
 *        "get"={
 *          "path"="/companies/{id}",
 *          "method"="GET"
 *        }
 *     }
 * )
 * @ORM\Entity(repositoryClass=CompanyRepository::class)
 */
class Company
{
    /**
     * @var string The UUID identifier of this object
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\Uuid
     * @ORM\Id
     * @ORM\Column(type="string", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private $id;

    /**
     * @Groups({"read"})
     * @ORM\Column(type="string", length=255)
     */
    private $branchNumber;

    /**
     * @Groups({"read"})
     * @ORM\Column(type="string", length=255)
     */
    private $kvkNumber;

    /**
     * @Groups({"read"})
     * @ORM\Column(type="string", length=255)
     */
    private $rsin;

    /**
     * @Groups({"read"})
     * @ORM\Column(type="boolean")
     */
    private $hasEntryInBusinessRegister;

    /**
     * @Groups({"read"})
     * @ORM\Column(type="boolean")
     */
    private $hasNonMailingIndication;

    /**
     * @Groups({"read"})
     * @ORM\Column(type="boolean")
     */
    private $isLegalPerson;

    /**
     * @Groups({"read"})
     * @ORM\Column(type="boolean")
     */
    private $isBranch;

    /**
     * @Groups({"read"})
     * @ORM\Column(type="boolean")
     */
    private $isMainBranch;

    /**
     * @Groups({"read"})
     * @ORM\ManyToMany(targetEntity=Address::class, inversedBy="companies")
     */
    private $addresses;

    /**
     * @Groups({"read"})
     * @ORM\Column(type="array")
     */
    private $tradeNames;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getBranchNumber(): ?string
    {
        return $this->branchNumber;
    }

    public function setBranchNumber(string $branchNumber): self
    {
        $this->branchNumber = $branchNumber;

        return $this;
    }

    public function getKvkNumber(): ?string
    {
        return $this->kvkNumber;
    }

    public function setKvkNumber(string $kvkNumber): self
    {
        $this->kvkNumber = $kvkNumber;

        return $this;
    }

    public function getRsin(): ?string
    {
        return $this->rsin;
    }

    public function setRsin(string $rsin): self
    {
        $this->rsin = $rsin;

        return $this;
    }

    public function getHasEntryInBusinessRegister(): ?bool
    {
        return $this->hasEntryInBusinessRegister;
    }

    public function setHasEntryInBusinessRegister(bool $hasEntryInBusinessRegister): self
    {
        $this->hasEntryInBusinessRegister = $hasEntryInBusinessRegister;

        return $this;
    }

    public function getHasNonMailingIndication(): ?bool
    {
        return $this->hasNonMailingIndication;
    }

    public function setHasNonMailingIndication(bool $hasNonMailingIndication): self
    {
        $this->hasNonMailingIndication = $hasNonMailingIndication;

        return $this;
    }

    public function getIsLegalPerson(): ?bool
    {
        return $this->isLegalPerson;
    }

    public function setIsLegalPerson(bool $isLegalPerson): self
    {
        $this->isLegalPerson = $isLegalPerson;

        return $this;
    }

    public function getIsBranch(): ?bool
    {
        return $this->isBranch;
    }

    public function setIsBranch(bool $isBranch): self
    {
        $this->isBranch = $isBranch;

        return $this;
    }

    public function getIsMainBranch(): ?bool
    {
        return $this->isMainBranch;
    }

    public function setIsMainBranch(bool $isMainBranch): self
    {
        $this->isMainBranch = $isMainBranch;

        return $this;
    }

    /**
     * @return Collection|Address[]
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Address $address): self
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses[] = $address;
        }

        return $this;
    }

    public function removeAddress(Address $address): self
    {
        if ($this->addresses->contains($address)) {
            $this->addresses->removeElement($address);
        }

        return $this;
    }

    public function getTradeNames(): array
    {
        return $this->tradeNames;
    }

    public function setTradeNames(array $tradeNames): self
    {
        $this->tradeNames = $tradeNames;

        return $this;
    }
}
