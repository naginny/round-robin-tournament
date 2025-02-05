<?php

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Team;
use App\Entity\TournamentMatch;
use App\Service\TournamentService;
use Doctrine\ORM\EntityManagerInterface;

final class TournamentServiceTest extends WebTestCase
{
    public function testGrid(): void
    {
        // Create a stub for the EntityManager (no real DB calls)
        $emStub = $this->createStub(EntityManagerInterface::class);
        $service = new TournamentService($emStub);

        $team1 = new Team();
        $team1->setName("Team 1");
        $team1->setId(1);
        $team1->setWinsCount(2);

        $team2 = new Team();
        $team2->setName("Team 2");
        $team2->setId(2);
        $team2->setWinsCount(1);

        $team3 = new Team();
        $team3->setName("Team 3");
        $team3->setId(3);
        $team3->setWinsCount(0);

        $service->setTeams([$team1, $team2, $team3]);

        // Match between Team 1 and Team 2: Team 1 wins.
        $match1 = new TournamentMatch();
        $match1->setTeamA($team1);
        $match1->setTeamB($team2);
        $match1->setWinner($team1);

        // Match between Team 1 and Team 3: Team 1 wins.
        $match2 = new TournamentMatch();
        $match2->setTeamA($team1);
        $match2->setTeamB($team3);
        $match2->setWinner($team1);

        // Match between Team 2 and Team 3: Team 2 wins.
        $match3 = new TournamentMatch();
        $match3->setTeamA($team2);
        $match3->setTeamB($team3);
        $match3->setWinner($team2);

        $service->setTournamentMatches([$match1, $match2, $match3]);

        // Generate the grid.
        $service->generateGrid();
        $grid = $service->getTournamentGrid();

        // Assert grid structure: rows for teams with IDs 1, 2, 3.
        foreach ([1, 2, 3] as $id) {
            $this->assertArrayHasKey($id, $grid, "Grid should contain a row for team ID $id.");
        }

        // Check self-match cell for team1 is gray.
        $this->assertEquals('lightgray', $grid[1][1]['bgColor'], "Cell 1-1 should be lightgray for self-match.");

        // Check match cell: for team1 vs team2, since team1 won, row1-col2 should be 'W'/lightgreen.
        $this->assertEquals('W', $grid[1][2]['text'], "Cell 1-2 should display 'W'.");
        $this->assertEquals('lightgreen', $grid[1][2]['bgColor'], "Cell 1-2 should be lightgreen.");

        // And conversely, cell 2-1 should be 'L'/lightcoral.
        $this->assertEquals('L', $grid[2][1]['text'], "Cell 2-1 should display 'L'.");
        $this->assertEquals('lightcoral', $grid[2][1]['bgColor'], "Cell 2-1 should be lightcoral.");
    }

    /**
     * Scenario 1: Three teams all have different wins count.
     * Expectation: They should be ranked in order.
     */
    public function testRankingThreeDifferentStrictResults(): void
    {
        $emStub = $this->createStub(EntityManagerInterface::class);
        $service = new TournamentService($emStub);

        $team1 = new Team();
        $team1->setName("Team 1");
        $team1->setId(1);
        $team1->setWinsCount(1);

        $team2 = new Team();
        $team2->setName("Team 2");
        $team2->setId(2);
        $team2->setWinsCount(2);

        $team3 = new Team();
        $team3->setName("Team 3");
        $team3->setId(3);
        $team3->setWinsCount(0);

        $service->setTeams([$team1, $team2, $team3]);

        // Define circular head-to-head results:
        // Team 1 vs Team 2: Team 2 wins.
        $match1 = new TournamentMatch();
        $match1->setTeamA($team1);
        $match1->setTeamB($team2);
        $match1->setWinner($team2);

        // Team 2 vs Team 3: Team 2 wins.
        $match2 = new TournamentMatch();
        $match2->setTeamA($team2);
        $match2->setTeamB($team3);
        $match2->setWinner($team2);

        // Team 3 vs Team 1: Team 1 wins.
        $match3 = new TournamentMatch();
        $match3->setTeamA($team3);
        $match3->setTeamB($team1);
        $match3->setWinner($team1);

        $service->setTournamentMatches([$match1, $match2, $match3]);
        $service->generateGrid();
        $service->determineTopTeams();
        $ranked = $service->getRankedTeams();

        // Expectation:
        // The ranking array should have three entries.
        // Team 2 should be ranked first, Team 1 second, and Team 3 third.
        $this->assertIsNotArray($ranked[0], "Top ranked entry should be a single Team instance.");
        $this->assertEquals("Team 2", $ranked[0]->getName(), "Team 2 should be ranked first.");
        $this->assertIsNotArray($ranked[1], "Second ranked entry should be a single Team instance.");
        $this->assertEquals("Team 1", $ranked[1]->getName(), "Team 1 should be ranked second.");
        $this->assertIsNotArray($ranked[2], "Third ranked entry should be a single Team instance.");
        $this->assertEquals("Team 3", $ranked[2]->getName(), "Team 3 should be ranked second.");
    }

    /**
     * Scenario 2: Three teams have the same overall wins and the head‐to‐head results form a circle:
     *   - Team 1 beats Team 2
     *   - Team 2 beats Team 3
     *   - Team 3 beats Team 1
     * Expectation: All three teams remain tied.
     */
    public function testRankingThreeTeamCircleTie(): void
    {
        $emStub = $this->createStub(EntityManagerInterface::class);
        $service = new TournamentService($emStub);

        $team1 = new Team();
        $team1->setName("Team 1");
        $team1->setId(1);
        $team1->setWinsCount(2);

        $team2 = new Team();
        $team2->setName("Team 2");
        $team2->setId(2);
        $team2->setWinsCount(2);

        $team3 = new Team();
        $team3->setName("Team 3");
        $team3->setId(3);
        $team3->setWinsCount(2);

        $service->setTeams([$team1, $team2, $team3]);

        // Define circular head-to-head results:
        // Team 1 vs Team 2: Team 1 wins.
        $match1 = new TournamentMatch();
        $match1->setTeamA($team1);
        $match1->setTeamB($team2);
        $match1->setWinner($team1);

        // Team 2 vs Team 3: Team 2 wins.
        $match2 = new TournamentMatch();
        $match2->setTeamA($team2);
        $match2->setTeamB($team3);
        $match2->setWinner($team2);

        // Team 3 vs Team 1: Team 3 wins.
        $match3 = new TournamentMatch();
        $match3->setTeamA($team3);
        $match3->setTeamB($team1);
        $match3->setWinner($team3);

        $service->setTournamentMatches([$match1, $match2, $match3]);

        // Build grid and calculate rankings.
        $service->generateGrid();
        $service->determineTopTeams();
        $ranked = $service->getRankedTeams();

        // Expectation:
        // All three teams remain tied and should appear together in one ranking entry (a tie group).
        $this->assertIsArray($ranked[0], "The ranking should be a tie group (an array) for a circle tie.");
        $this->assertCount(3, $ranked[0], "The tie group should contain all 3 teams.");
        $names = array_map(fn($team) => $team->getName(), $ranked[0]);
        sort($names);
        $this->assertEquals(["Team 1", "Team 2", "Team 3"], $names, "All teams should be tied in a circular head-to-head.");
    }

    /**
     * Scenario 3: 6 teams have four teams with equal wins count but different wins count within the group.
     * Expectation: Two tied groups
     */
    public function testComplexRankingScenario(): void
    {
        $emStub = $this->createStub(EntityManagerInterface::class);
        $service = new TournamentService($emStub);

        $team1 = new Team();
        $team1->setName("Team 1");
        $team1->setId(1);
        $team1->setWinsCount(2);

        $team2 = new Team();
        $team2->setName("Team 2");
        $team2->setId(2);
        $team2->setWinsCount(3);

        $team3 = new Team();
        $team3->setName("Team 3");
        $team3->setId(3);
        $team3->setWinsCount(3);

        $team4 = new Team();
        $team4->setName("Team 4");
        $team4->setId(4);
        $team4->setWinsCount(3);

        $team5 = new Team();
        $team5->setName("Team 5");
        $team5->setId(5);
        $team5->setWinsCount(3);

        $team6 = new Team();
        $team6->setName("Team 6");
        $team6->setId(6);
        $team6->setWinsCount(1);

        $service->setTeams([$team1, $team2, $team3, $team4, $team5, $team6]);

        // Match 1: Team 1 vs Team 2 — Team 2 wins.
        $match1 = new TournamentMatch();
        $match1->setTeamA($team1);
        $match1->setTeamB($team2);
        $match1->setWinner($team2);

        // Match 2: Team 1 vs Team 3 — Team 3 wins.
        $match2 = new TournamentMatch();
        $match2->setTeamA($team1);
        $match2->setTeamB($team3);
        $match2->setWinner($team3);

        // Match 3: Team 1 vs Team 4 — Team 4 wins.
        $match3 = new TournamentMatch();
        $match3->setTeamA($team1);
        $match3->setTeamB($team4);
        $match3->setWinner($team4);

        // Match 4: Team 1 vs Team 5 — Team 1 wins.
        $match4 = new TournamentMatch();
        $match4->setTeamA($team1);
        $match4->setTeamB($team5);
        $match4->setWinner($team1);

        // Match 5: Team 1 vs Team 6 — Team 1 wins.
        $match5 = new TournamentMatch();
        $match5->setTeamA($team1);
        $match5->setTeamB($team6);
        $match5->setWinner($team1);

        // Match 6: Team 2 vs Team 3 — Team 2 wins.
        $match6 = new TournamentMatch();
        $match6->setTeamA($team2);
        $match6->setTeamB($team3);
        $match6->setWinner($team2);

        // Match 7: Team 2 vs Team 4 — Team 2 wins.
        $match7 = new TournamentMatch();
        $match7->setTeamA($team2);
        $match7->setTeamB($team4);
        $match7->setWinner($team2);

        // Match 8: Team 2 vs Team 5 — Team 5 wins.
        $match8 = new TournamentMatch();
        $match8->setTeamA($team2);
        $match8->setTeamB($team5);
        $match8->setWinner($team5);

        // Match 9: Team 2 vs Team 6 — Team 6 wins.
        $match9 = new TournamentMatch();
        $match9->setTeamA($team2);
        $match9->setTeamB($team6);
        $match9->setWinner($team6);

        // Match 10: Team 3 vs Team 4 — Team 3 wins.
        $match10 = new TournamentMatch();
        $match10->setTeamA($team3);
        $match10->setTeamB($team4);
        $match10->setWinner($team3);

        // Match 11: Team 3 vs Team 5 — Team 5 wins.
        $match11 = new TournamentMatch();
        $match11->setTeamA($team3);
        $match11->setTeamB($team5);
        $match11->setWinner($team5);

        // Match 12: Team 3 vs Team 6 — Team 3 wins.
        $match12 = new TournamentMatch();
        $match12->setTeamA($team3);
        $match12->setTeamB($team6);
        $match12->setWinner($team3);

        // Match 13: Team 4 vs Team 5 — Team 4 wins.
        $match13 = new TournamentMatch();
        $match13->setTeamA($team4);
        $match13->setTeamB($team5);
        $match13->setWinner($team4);

        // Match 14: Team 4 vs Team 6 — Team 4 wins.
        $match14 = new TournamentMatch();
        $match14->setTeamA($team4);
        $match14->setTeamB($team6);
        $match14->setWinner($team4);

        // Match 15: Team 5 vs Team 6 — Team 5 wins.
        $match15 = new TournamentMatch();
        $match15->setTeamA($team5);
        $match15->setTeamB($team6);
        $match15->setWinner($team5);

        $matches = [
            $match1, $match2, $match3, $match4, $match5,
            $match6, $match7, $match8, $match9,
            $match10, $match11, $match12,
            $match13, $match14, $match15
        ];

        $service->setTournamentMatches($matches);

        // Generate the grid (which builds the internal match map used for tie-breaking)
        // and then determine the top teams.
        $service->generateGrid();
        $service->determineTopTeams();
        $ranked = $service->getRankedTeams();

        // Expected ranking:
        // - Ranked entry 0: A tie group with Team 2 and Team 5 (3 wins each, with head-to-head advantage)
        // - Ranked entry 1: A tie group with Team 3 and Team 4 (3 wins each, with lower head-to-head wins)
        // - Ranked entry 2: A single Team instance: Team 1 (2 wins)
        $this->assertCount(3, $ranked, "There should be 3 ranking entries.");

        // Verify the first ranking entry: Tie group containing Team 2 and Team 5.
        $this->assertIsArray($ranked[0], "The first ranking entry should be a tie group (an array).");
        $this->assertCount(2, $ranked[0], "The first tie group should contain 2 teams.");
        $firstGroupNames = array_map(fn($team) => $team->getName(), $ranked[0]);
        sort($firstGroupNames);
        $this->assertEquals(["Team 2", "Team 5"], $firstGroupNames, "The first tie group should be Team 2 and Team 5.");

        // Verify the second ranking entry: Tie group containing Team 3 and Team 4.
        $this->assertIsArray($ranked[1], "The second ranking entry should be a tie group (an array).");
        $this->assertCount(2, $ranked[1], "The second tie group should contain 2 teams.");
        $secondGroupNames = array_map(fn($team) => $team->getName(), $ranked[1]);
        sort($secondGroupNames);
        $this->assertEquals(["Team 3", "Team 4"], $secondGroupNames, "The second tie group should be Team 3 and Team 4.");

        // Verify the third ranking entry: A single team: Team 1.
        $this->assertInstanceOf(Team::class, $ranked[2], "The third ranking entry should be a Team instance.");
        $this->assertEquals("Team 1", $ranked[2]->getName(), "Team 1 should be ranked third.");
    }

}
