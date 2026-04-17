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
