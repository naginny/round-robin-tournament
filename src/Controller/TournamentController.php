<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Team;
use App\Entity\TournamentMatch;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TournamentController extends AbstractController
{
    private EntityManagerInterface $em;

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

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/tournament', name: 'tournament_page')]
    public function tournament(Request $request): Response
    {
        $teamCount = null;
        $rankedTeams = [];

        if ($request->isMethod('POST')) {
            $teamCount = (int) $request->request->get('team_count');

            $this->purgePreviousTournamentData();
            $this->generateTeams($teamCount);
            $this->generateMatches();
            $this->generateGrid();

            $rankedTeams = $this->determineTopTeams();
        }

        return $this->render('tournament/index.html.twig', [
            'teamCount' => $teamCount,
            'teams' => $this->teams,
            'matches' => $this->tournamentMatches,
            'tournamentGrid' => $this->grid,
            'rankedTeams' => $rankedTeams,
        ]);
    }

    private function purgePreviousTournamentData(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\TournamentMatch')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Team')->execute();

        $connection = $this->em->getConnection();
        $connection->executeStatement('ALTER TABLE tournament_match AUTO_INCREMENT = 1');
        $connection->executeStatement('ALTER TABLE team AUTO_INCREMENT = 1');
    }

    private function generateTeams(int $count): void
    {
        $teams = [];
        for ($i = 1; $i <= $count; $i++) {
            $team = new Team();
            $team->setName("Team $i");
            $this->em->persist($team);
            $teams[] = $team;
        }
        $this->em->flush();
        $this->teams = $teams;
    }

    private function generateMatches(): void
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

                $this->em->persist($match);
                $this->tournamentMatches[] = $match;
            }
        }
        $this->em->flush();
    }

    private function generateGrid(): void
    {
        $this->matchMapIndexed = [];
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

    private function generateMatchMapKey(int $oneId, int $otherId): string
    {
        return min($oneId, $otherId) . '-' . max($oneId, $otherId);
    }

    /**
     * @return array
     */
    private function determineTopTeams(): array
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

        $rankedTeams = [];
        foreach ($grouped as $wins => $group) {
            if (count($rankedTeams) > 2) {
                break;
            }
            if (count($group) === 1) {
                // Only one team with this wins count: rank is clear.
                $rankedTeams[] = $group;
            } else {
                // More than one team: perform head-to-head comparisons and add to resulting array
                $rankedTeams = $this->evaluateHeadToHead($group, $rankedTeams);
            }
        }

        return array_slice($rankedTeams, 0, 3);
    }

    /**
     * @param Team[] $group Array of Team objects tied on overall wins.
     * @param array $rankedTeams The main ranking array to which the sorted teams will be attached.
     * @return array The updated ranking array.
     */
    private function evaluateHeadToHead(array $group, array $rankedTeams): array
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
                $rankedTeams[] = $subgroup[0];
            } else {
                $rankedTeams[] = $subgroup;
            }
        }

        return $rankedTeams;
    }

}
