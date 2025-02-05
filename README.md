# Round robin tournament simulator

This is a Round-Robin Tournament Simulation with results visualisation.
This small system generates a round-robin tournament table for up to 12 teams, generates team names,
stores tournament data in database and visualizes tournament table with winners ranked in TOP-3 levels.
UnitTests cover it's key functionality.


## Setting up and hands-on

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/)
2. Run `docker compose build --no-cache` to build fresh images
3. Run `docker compose up --pull always -d --wait`
4. Open `https://localhost/tournament` in your favorite web browser and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
5. Enter desired team count and enjoy results!
6. To run UnitTests, run `php bin/phpunit`

## Technologies and tools

I am not very much familiar with Docker and Symfony yet, but I decided to use them for this small project,
because (for practice in the first place, but also because) Docker eases setting up the project 
on another machine, and Symfony (or frameworks in general) clearly was task requestor's interest.
Covering functionality with UnitTests was one of conditions. Here I've had a problem, I couldn't manage to set up
a testing database. Unfortunately, reusing main one, despite that it is not a good practice in general, also 
did not succeed. Here reveals an ugly truth about me: I am not very good configuring things (:
So, to satisfy the UnitTest coverage at least partially, I have used them with stub Entity Manager and
completely avoided database interactions in test mode.



## Credits

Symfony Docker skeleton is taken from https://github.com/dunglas/symfony-docker?tab=readme-ov-file
