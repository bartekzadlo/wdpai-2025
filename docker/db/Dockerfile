FROM postgres:15

# Ustawienie zmiennych środowiskowych
ENV POSTGRES_DB=wdpai_db
ENV POSTGRES_USER=postgres
ENV POSTGRES_PASSWORD=postgres

# Kopiowanie skryptu inicjalizacyjnego
COPY ./database/01_create_tables.sql /docker-entrypoint-initdb.d/

# Upewnienie się że pgcrypto extension jest dostępne
RUN echo "CREATE EXTENSION IF NOT EXISTS pgcrypto;" > /docker-entrypoint-initdb.d/00_extensions.sql
