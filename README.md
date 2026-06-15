1. Local dev
```bash
docker compose up -d
docker compose down
```

2. Build image 
```bash
docker compose --env-file .env.prod -f docker-compose.yml -f docker-compose.prod.yml build
docker run --rm -it registry.gitlab.com/xxx
docker login registry.gitlab.com
docker compose --env-file .env.prod -f docker-compose.yml -f docker-compose.prod.yml push
```

3. production

```bash
ssh <Server IP>
cd /data/projects/xxx
nano .env
docker compose --env-file .env.prod -f docker-compose.yml -f docker-compose.prod.yml pull
docker compose --env-file .env.prod -f docker-compose.yml -f docker-compose.prod.yml down
docker compose --env-file .env.prod -f docker-compose.yml -f docker-compose.prod.yml up -d
```