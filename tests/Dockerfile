FROM php:8.4-cli
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libcurl4-openssl-dev python3 openssl ca-certificates \
    && docker-php-ext-install pdo_pgsql curl && rm -rf /var/lib/apt/lists/*
COPY . /work
WORKDIR /work
CMD ["python3", "tests/emissions.py"]
