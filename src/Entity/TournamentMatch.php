<?php

namespace App\Entity;

use App\Repository\TournamentMatchRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentMatchRepository::class)]
class TournamentMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $teamA;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $teamB;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: true)]
    private Team $winner;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTeamA(): Team
    {
        return $this->teamA;
    }

    public function setTeamA(Team $teamA): self
    {
        $this->teamA = $teamA;
        return $this;
    }

    public function getTeamB(): Team
    {
        return $this->teamB;
    }

    public function setTeamB(Team $teamB): self
    {
        $this->teamB = $teamB;
        return $this;
    }

    public function getWinner(): Team
    {
        return $this->winner;
    }

    public function setWinner(Team $winner): self
    {
        $this->winner = $winner;
        return $this;
    }
}
