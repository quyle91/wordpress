1. local dev
```bash
docker compose up -d
docker compose down
```

2. local build image
```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml build
docker login registry.gitlab.com
docker compose -f docker-compose.yml -f docker-compose.prod.yml push
```

3. production

```bash
ssh <Server IP>
cd /data/projects/xxx
nano .env
docker compose -f docker-compose.yml -f docker-compose.prod.yml pull
docker compose -f docker-compose.yml -f docker-compose.prod.yml down
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```