#!/bin/bash

# Configurable DB env vars with defaults for single-container setup
DB_HOST="${DB_HOST:-localhost}"
DB_NAME="${DB_NAME:-crm_database}"
DB_USER="${DB_USER:-crm_user}"
DB_PASS="${DB_PASS:-crm_password}"

echo "🧹 Cleaning up existing containers and images..."
docker stop crm-container 2>/dev/null || true
docker rm crm-container 2>/dev/null || true
docker rmi crm-app 2>/dev/null || true

echo "🔨 Building fresh Docker image..."
docker build --no-cache -t crm-app .

if [ $? -eq 0 ]; then
    echo "✅ Build successful! Starting container..."
    docker run -d \
        -p 8080:80 \
        --name crm-container \
        -e DB_HOST="$DB_HOST" \
        -e DB_NAME="$DB_NAME" \
        -e DB_USER="$DB_USER" \
        -e DB_PASS="$DB_PASS" \
        crm-app
    
    echo "⏳ Waiting for services to start..."
    sleep 10
    
    echo "📋 Container logs:"
    docker logs crm-container
    
    echo ""
    echo "🌐 Application should be available at:"
    echo "   http://localhost:8080"
    echo ""
    echo "🔍 To monitor logs: docker logs -f crm-container"
    echo "🛑 To stop: docker stop crm-container"
    echo "🗑️  To remove: docker rm crm-container"
    echo "⚙️  Override DB settings by exporting env vars before running, e.g.:"
    echo "   DB_NAME=mydb DB_USER=myuser DB_PASS=mypass ./run.sh"
    
else
    echo "❌ Build failed!"
    exit 1
fi