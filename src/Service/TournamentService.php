<?php
namespace App\Service;

use App\Entity\Team;
use App\Entity\TournamentMatch;
use Doctrine\ORM\EntityManagerInterface;

class TournamentService
{
    private EntityManagerInterface $entityManager;

    /**
     * @var Team[]
     */
    private array $teams = [];

    /**
     * @var TournamentMatch[]
     */
    private array $tournamentMatches = [];

    /**
     * @var TournamentMatch[]
     */
    private array $matchMapIndexed = [];

    private array $grid = [];

    private array $rankedTeams = [];

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * For testing purposes only!
     * @teams Team[]
     */
    public function setTeams(array $teams): void
    {
        if ($_ENV['APP_ENV'] === 'test')
        {
            $this->teams = $teams;
        }
    }

    /**
     * @return Team[]
     */
    public function getTeams(): array
    {
        return $this->teams;
    }

    /**
     * For testing purposes only!
     * @tournamentMatches TournamentMatch[]
     */
    public function setTournamentMatches(array $tournamentMatches): void
    {
        if ($_ENV['APP_ENV'] === 'test')
        {
            $this->tournamentMatches = $tournamentMatches;
        }
    }

    public function getTournamentGrid(): array
    {
        return $this->grid;
    }

    public function getRankedTeams(): array
    {
        return $this->rankedTeams;
    }

    public function purgePreviousTournamentData(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\TournamentMatch')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Team')->execute();
    }

    public function generateTeams(int $count): void
    {
        $teams = [];
        for ($i = 1; $i <= $count; $i++) {
            $team = new Team();
            $team->setName("Team $i");
            $this->entityManager->persist($team);
            $teams[] = $team;
        }
        $this->entityManager->flush();

        $this->teams = $teams;
    }

    public function generateMatches(): void
    {
        $totalTeams = count($this->teams);

        for ($i = 0; $i < $totalTeams; $i++) {
            for ($j = $i + 1; $j < $totalTeams; $j++) {
                $match = new TournamentMatch();
                $match->setTeamA($this->teams[$i]);
                $match->setTeamB($this->teams[$j]);

                // Random winner
                $winner = rand(0, 1) ? $this->teams[$i] : $this->teams[$j];
                $winner->increaseWinsCount();
                $match->setWinner($winner);

                $this->entityManager->persist($match);
                $this->tournamentMatches[] = $match;
            }
        }
        $this->entityManager->flush();
    }

    public function generateGrid(): void
    {
        foreach ($this->tournamentMatches as $match) {
            $idA = $match->getTeamA()->getId();
            $idB = $match->getTeamB()->getId();

            $key = $this->generateMatchMapKey($idA, $idB);
            $this->matchMapIndexed[$key] = $match;
        }

        // Build grid data for each team combination
        foreach ($this->teams as $rowTeam) {
            $rowId = $rowTeam->getId();
            $this->grid[$rowId] = [];
            foreach ($this->teams as $colTeam) {
                $colId = $colTeam->getId();

                if ($rowId === $colId) {
                    // Self-match: gray out
                    $this->grid[$rowId][$colId] = [
                        'text'    => '',
                        'bgColor' => 'lightgray',
                    ];
                } else {
                    $key = $this->generateMatchMapKey($rowId, $colId);
                    if (isset($this->matchMapIndexed[$key])) {
                        $match = $this->matchMapIndexed[$key];
                        if ($match->getWinner()->getId() === $rowId) {
                            $this->grid[$rowId][$colId] = [
                                'text'    => 'W',
                                'bgColor' => 'lightgreen',
                            ];
                        } else {
                            $this->grid[$rowId][$colId] = [
                                'text'    => 'L',
                                'bgColor' => 'lightcoral',
                            ];
                        }
                    } else {
                        // Shouldn't happen
                        $this->grid[$rowId][$colId] = [
                            'text'    => '-',
                            'bgColor' => 'white',
                        ];
                    }
                }
            }
        }
    }

    protected function generateMatchMapKey(int $oneId, int $otherId): string
    {
        return min($oneId, $otherId) . '-' . max($oneId, $otherId);
    }

    public function determineTopTeams(): void
    {
        $teams = $this->teams;
        usort($teams, function (Team $a, Team $b) {
            return $b->getWinsCount() <=> $a->getWinsCount();
        });

        // Group teams by wins
        $grouped = [];
        foreach ($teams as $team) {
            $grouped[$team->getWinsCount()][] = $team;
        }

        foreach ($grouped as $group) {
            if (count($this->rankedTeams) > 2) {
                break;
            }
            if (count($group) === 1) {
                // Only one team with this wins count: rank is clear.
                $this->rankedTeams[] = $group[0];
            } else {
                // More than one team: perform head-to-head comparisons and add to resulting array
                $this->evaluateHeadToHead($group);
            }
        }

        $this->rankedTeams = array_slice($this->rankedTeams, 0, 3);
    }

    /**
     * @param Team[] $group Array of Team objects tied on overall wins.
     */
    protected function evaluateHeadToHead(array $group): void
    {
        // Initialize head-to-head wins counter for each team in the group.
        $headToHeadWins = [];
        foreach ($group as $team) {
            $headToHeadWins[$team->getId()] = 0;
        }

        // Count head-to-head wins for each distinct pair within the group.
        $groupCount = count($group);
        for ($i = 0; $i < $groupCount; $i++) {
            for ($j = $i + 1; $j < $groupCount; $j++) {
                $teamA = $group[$i];
                $teamB = $group[$j];
                $idA = $teamA->getId();
                $idB = $teamB->getId();

                $key = $this->generateMatchMapKey($idA, $idB);
                if (isset($this->matchMapIndexed[$key])) {
                    $match = $this->matchMapIndexed[$key];
                    $winner = $match->getWinner();

                    if ($winner->getId() == $idA) {
                        $headToHeadWins[$idA]++;
                    } elseif ($winner->getId() == $idB) {
                        $headToHeadWins[$idB]++;
                    }
                }
            }
        }

        // Sort the group by head-to-head wins (descending)
        usort($group, function (Team $a, Team $b) use ($headToHeadWins) {
            return $headToHeadWins[$b->getId()] <=> $headToHeadWins[$a->getId()];
        });

        // Group teams by their head-to-head wins so that tied teams remain together.
        $grouped = [];
        foreach ($group as $team) {
            $score = $headToHeadWins[$team->getId()];
            $grouped[$score][] = $team;
        }
        // Sort the groups by score in descending order.
        krsort($grouped);

        foreach ($grouped as $subgroup) {
            if (count($subgroup) === 1) {
                $this->rankedTeams[] = $subgroup[0];
            } else {
                $this->rankedTeams[] = $subgroup;
            }
        }
    }
}
