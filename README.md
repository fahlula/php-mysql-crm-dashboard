# CRM PHP + MySQL (Docker)

## Local
```bash
chmod +x run.sh
./run.sh
# App: http://localhost:8080
```

Opções:
```bash
DB_HOST=127.0.0.1 DB_NAME=crm_database DB_USER=crm_user DB_PASS=crm_password PORT=8080 ./run.sh
```

Gerenciar:
```bash
docker logs -f crm-container
docker stop crm-container && docker rm crm-container
```

## Windows
- Git Bash/WSL:
```bash
chmod +x run.sh
./run.sh
```
- PowerShell (sem usar o script):
```powershell
docker build -t crm-app .
docker run -d -p 8080:80 --name crm-container `
  -e DB_HOST=127.0.0.1 -e DB_NAME=crm_database -e DB_USER=crm_user -e DB_PASS=crm_password `
  crm-app
```
