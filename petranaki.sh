#!/bin/bash

set -eo pipefail

export DOCKER_USER="$(id -u):$(id -g)"
export STAGE=dev

case $1 in
  "start")
    echo "Starting Petranaki..."
    docker compose up -d
    echo "Petranaki is running at http://localhost:8080/Arena/MainMenu.php"
    ;;
  "stop")
    echo "Stopping Petranaki..."
    docker compose down
    ;;
  "restart")
    echo "Restarting Petranaki..."
    docker compose restart
    ;;
  "bash")
    echo "Opening bash shell in web server container..."
    docker exec -it swuonline-web-server-1 /bin/bash
    ;;
  "test")
    echo "Running tests..."
    docker exec -it swuonline-web-server-1 mkdir -p /tmp/.phpunit-cache
    docker exec -it swuonline-web-server-1 \
      env PHPUNIT_RESULT_CACHE=/tmp/.phpunit-cache \
      php -d xdebug.mode=off /usr/local/bin/phpunit --colors=always --testdox /var/www/html/Arena/tests
    echo "Tests completed."
    ;;
  *)
    echo "Usage: $0 {start|stop|restart|test|bash}"
    exit 1
    ;;
esac
