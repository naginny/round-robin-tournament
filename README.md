# Round robin tournament simulator


## Getting Started

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/)
2. Run `docker compose build --no-cache` to build fresh images
3. Run `docker compose up --pull always -d --wait`
4. Open `https://localhost/tournament` in your favorite web browser and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
5. Enter desired team count and enjoy results!
6. To run UnitTests, run `php bin/phpunit`



## Credits

Symfony Docker skeleton is taken from https://github.com/dunglas/symfony-docker?tab=readme-ov-file
