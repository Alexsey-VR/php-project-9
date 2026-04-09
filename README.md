### Simple Web Analyzer

_A simple service to check web site availability with response status info_

### Hexlet tests and linter status:
[![Actions Status](https://github.com/Alexsey-VR/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/Alexsey-VR/php-project-9/actions)[![Quality gate](https://sonarcloud.io/api/project_badges/quality_gate?project=Alexsey-VR_php-project-9)](https://sonarcloud.io/summary/new_code?id=Alexsey-VR_php-project-9)[![check-for-linter](https://github.com/Alexsey-VR/php-project-9/actions/workflows/check-for-linter.yml/badge.svg)](https://github.com/Alexsey-VR/php-project-9/actions/workflows/check-for-linter.yml)


This is a simple web page analyzer. It evaluates pages based on the HTTP response status of the specified address. The URL must contain no more than 255 characters and follow the standard format:

https://example.com

The analysis results include the response status, brief information about the webpage, and the verification timestamp.


View service:
https://php-project-9-6wzw.onrender.com/


### How to install
Install Docker on your platform
https://docs.docker.com/get-started/get-docker/

Build image:

docker build . --tag php-project-9:v1


Define PostgreSQL connection parameters:

export DB_PROVIDER=postgresql
export DB_USER=...
export DB_PASS=...
export DB_HOST=...
export DB_PORT=...
export DB_NAME=...


Run container:

docker run -it \
    -e DATABASE_URL="${DB_PROVIDER}://${DB_USER}:${DB_PASS}@${DB_HOST}:${DB_PORT}/${DB_NAME}" \
    -p 8000:8000 \
    php-project-9:v1


View service on localhost:8000
