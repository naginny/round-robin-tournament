<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\TournamentService;

final class TournamentController extends AbstractController
{
    private TournamentService $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }

    #[Route('/tournament', name: 'tournament_page')]
    public function tournament(Request $request): Response
    {
        $teamCount = null;

        if ($request->isMethod('POST')) {
            $teamCount = (int) $request->request->get('team_count');
            $this->tournamentService->purgePreviousTournamentData();
            $this->tournamentService->generateTeams($teamCount);
            $this->tournamentService->generateMatches();
            $this->tournamentService->generateGrid();
            $this->tournamentService->determineTopTeams();
        }

        return $this->render('tournament/index.html.twig', [
            'teamCount' => $teamCount,
            'teams' => $this->tournamentService->getTeams(),
            'tournamentGrid' => $this->tournamentService->getTournamentGrid(),
            'rankedTeams' => $this->tournamentService->getRankedTeams(),
        ]);
    }
}
