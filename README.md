# Round robin tournament simulator

The Round‑Robin Tournament Simulator is a web application built with Symfony and Docker. 
It allows users to simulate a tournament by generating teams, scheduling matches, and automatically 
calculating rankings based on wins and head‑to‑head results. The interface is designed to be simple and 
intuitive, providing immediate visual feedback through a dynamic grid and ranking list.

---

## Setting up and hands-on

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/)
2. Run `docker compose build --no-cache` to build fresh images
3. Run `docker compose up --pull always -d --wait`
4. Open `https://localhost/tournament` in your favorite web browser and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
5. Enter desired team count and enjoy results!
6. To launch UnitTests, run `php bin/phpunit`

---

## User Interface

This application enables users to:

- **Enter tournament data** via a form with input restrictions.
- **Visualize match results** using a grid where each cell indicates a win or loss.
- **Display team rankings** based on overall wins and head‑to‑head tie-breakers.


### Tournament Setup Form

- **Input Field:**
    - **Purpose:** Enter the number of teams participating in the tournament.
    - **Restrictions:**
        - Accepts an integer value.
        - Minimum: 2 teams.
        - Maximum: 12 teams.
    - **Validation:** Ensures the value is within the allowed range.
- **Submit Button:**
    - Labeled “Generate”.
    - When clicked, the form data is submitted to trigger the tournament simulation.

### Dynamic Content Area

Once the form is submitted, the application generates all tournament data and updates the page with:

1. **Match Grid**
2. **Rankings Section**

## Tournament Data Display

### 1. Match Grid

- **Structure:**
    - Displayed as an HTML table.
    - Rows and columns are labeled with team names.
    - Each cell (except for the diagonal) represents the outcome of a match between the corresponding teams.
- **Visual Details:**
    - **Self-Match Cells:**
        - Grayed out to indicate that a team does not play itself.
    - **Match Result Cells:**
        - Show either "W" or "L" to indicate a win or loss.
        - **Color Coding:**
            - **Win:** Light green background.
            - **Loss:** Light coral (or red-toned) background.
- **Purpose:**  
  Offers a comprehensive visual overview of all match results, allowing users to quickly see team performance.

### 2. Rankings Section

- **Placement:**  
  Displayed below the match grid.
- **Content:**
    - The overall ranking is calculated based on the number of wins.
    - In the event of a tie, head‑to‑head results are used to break the tie.
    - **Display Format:**
        - Shown as an ordered list.
        - Each ranking entry shows the team name and win count.
        - If teams are tied, they appear together in the same ranking position with a “[Tied]” marker.
- **Example:**
    - **Rank 1:** Team 2 (3 wins) and Team 5 (3 wins) [Tied]
    - **Rank 2:** Team 3 (3 wins) and Team 4 (3 wins) [Tied]
    - **Rank 3:** Team 1 (2 wins)


## User Workflow

1. **Entering Tournament Data:**
    - Enter the number of teams (between 2 and 12) in the input field.
    - Click the submit button.
2. **Tournament Simulation:**
    - The application generates teams with unique names.
    - Matches are scheduled in a round‑robin format.
    - Winners are determined randomly.
    - Overall win counts are calculated.
    - A match grid is built and tie-breaker logic is applied to determine rankings.
3. **Results Display:**
    - The match grid visually presents the detailed results.
    - The ranking list, below the grid, shows the teams’ standings.
4. **Re-Running Simulations:**
    - Change the team count and re-submit the form to generate a new simulation.
    - The interface refreshes automatically with updated data.

---

# Technical Details

## Code Structure

The application is built using the Symfony framework and follows modern best practices such as separation of concerns. The primary components are:

- **Controller:**
    - Located in `src/Controller/TournamentController.php`.
    - Handles HTTP requests, passes data to the service layer, and renders the user interface.

- **Service:**
    - Located in `src/Service/TournamentService.php`.
    - Contains all the business logic for the tournament:
        - Generating teams and matches.
        - Building the match grid.
        - Calculating rankings based on wins and head-to-head results.
        - Purging (wiping) previous tournament data before a new simulation.

- **Entities:**
    - **Team:**
        - Located in `src/Entity/Team.php`.
        - Represents a tournament team with properties such as ID, name, and wins count.
    - **TournamentMatch:**
        - Located in `src/Entity/TournamentMatch.php`.
        - Represents a match between two teams and stores the result (the winning team).

## Database Structure

The application uses a MySQL database with Doctrine ORM for data persistence. The main database tables are:

- **team:**
    - Stores team details (e.g., unique ID, team name, wins count).

- **tournament_match:**
    - Stores match information, including:
        - Foreign keys linking to the two competing teams.
        - The ID of the winning team.

**Note:**  
For testing purposes, database operations are stubbed, so no real database calls occur during unit tests.


## Docker & Environment

- The application is containerized using Docker.
- Environment variables (such as `APP_ENV`) control configuration aspects, including database connections and test settings.
- In test mode, the application disables real database flushing to keep unit tests fast and isolated.

---

## Main Task Logic Overview

The core logic of the Round‑Robin Tournament Simulator is designed to be straightforward and easy to follow. Here’s a brief overview of how it works:


- **Team Generation:**  

  When the user submits the tournament form with the desired number of teams, the application generates that many teams with unique names.


- **Match Scheduling:**  

  Each team plays every other team exactly once. To guarantee that every two teams play exactly one match, the application uses a simple nested loop approach when scheduling matches. Here's how it works:

  The code iterates through the list of teams with two loops:
    - The **outer loop** runs from the first team up to the second-to-last team.
    - The **inner loop** starts from the next team (i.e., the outer loop's index plus one) and continues to the last team.

  By having the inner loop begin at the team immediately following the current team in the outer loop, each pair of teams is considered only once. For example, if there are 6 teams:
    - When processing Team 1, the inner loop creates matches with Teams 2, 3, 4, 5, and 6.
    - When processing Team 2, the inner loop creates matches with Teams 3, 4, 5, and 6 (avoiding Team 1, which has already been paired with Team 2).
    - This continues until all combinations are covered.

  This approach results in exactly one match for each unique pair of teams, yielding a total of *n(n - 1) / 2* matches for *n* teams (e.g., 15 matches for 6 teams).


- **Winner Calculation:**  
  For every match, a winner is determined (using a simple random selection mechanism). The winning team's win count is incremented accordingly.


- **Grid Rendering:**  

    Building the Internal Match Map:
    - All generated matches are stored in an internal match map (an associative array).
    - The key for each match is created using the IDs of the two teams involved (formatted as `minID-maxID`), ensuring that each unique pair of teams corresponds to exactly one entry in the match map.

    - The grid is represented as a two-dimensional array, where both the rows and columns correspond to the list of teams.
    - A nested loop is used to iterate over all pairs of teams:
        - **Row Loop:** Iterates over each team as the “row team.”
        - **Column Loop:** Iterates over each team as the “column team.”
    - For each cell:
        - **Self-Matches:**
            - When the row and column represent the same team, the cell is set to a special state (e.g., a blank text with a `lightgray` background) to indicate that no match occurs.
        - **Inter-Team Matches:**
            - For two different teams, the application uses the match map to retrieve the corresponding match.
            - Based on the match result:
                - If the row team is the winner, the cell displays a "W" with a `lightgreen` background.
                - Otherwise, the cell displays an "L" with a `lightcoral` background.

    - The completed grid provides a clear visual summary of the tournament outcomes.
    - Each cell succinctly communicates the result between the corresponding teams, allowing users to quickly assess win/loss patterns across the tournament.


- **Ranking Determination:**  

  Teams are initially ranked by their total wins. In cases where teams have the same win count, head‑to‑head results are used as tie-breakers:
    - If one team beat another in their direct match, that team is ranked higher.
    - If the head‑to‑head results form a cycle or remain unresolved, the tied teams are grouped together in the final ranking.

---

## Credits

Symfony Docker skeleton is taken from https://github.com/dunglas/symfony-docker?tab=readme-ov-file
