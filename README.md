# Elasticsearch Upgrade Playground

A playground for testing Elasticsearch rolling upgrades.

## Prerequisites

This project requires the following tools installed and available on your machine:

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [mise](https://mise.jdx.dev/)

**Docker Desktop** must be installed and running to start Elasticsearch clusters via Docker Compose.

**mise** is required to run project tasks (starting clusters, upgrade workflows, benchmarks, etc.).

Verify the Docker installation:
```bash
docker version
docker compose version
```

Verify the mise installation:
```bash
mise version
```

## Available Tasks

Project workflows are exposed through `mise` tasks.

List all available tasks:
```bash
glib@es:~/es-playground$ mise tasks ls
Name                  Description
create-es-v6-cluster  Create Elasticsearch v6 cluster
get-workload          Run get workload
indexing-workload     Run indexing workload
populate-indices      Populate Elasticsearch indices with datasets
search-workload       Run search workload
update-workload       Run update workload
upgrade-es-v6-to-v7
```