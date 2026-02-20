#!/bin/bash

# 测试 Go 聊天服务
echo "Testing Go Chat Server..."

# 检查编译
echo "1. Building server..."
go build -o chat-server ./cmd/server
if [ $? -eq 0 ]; then
    echo "✅ Build successful"
else
    echo "❌ Build failed"
    exit 1
fi

# 检查配置文件
echo "2. Checking configuration..."
if [ -f "env.example" ]; then
    echo "✅ Configuration template exists"
    if [ ! -f ".env" ]; then
        echo "⚠️  .env file not found, copying from env.example"
        cp env.example .env
        echo "Please edit .env file with your database and JWT settings"
    else
        echo "✅ .env file exists"
    fi
else
    echo "❌ Configuration template not found"
    exit 1
fi

# 检查存储目录
echo "3. Creating storage directory..."
mkdir -p storage/chat-files
echo "✅ Storage directory created"

# 检查数据库迁移文件
echo "4. Checking database migrations..."
if [ -f "migrations/2025_10_29_000001_create_chat_tables.sql" ]; then
    echo "✅ Database migration file exists"
else
    echo "❌ Database migration file not found"
    exit 1
fi

echo ""
echo "🎉 All checks passed! Server is ready to run."
echo ""
echo "To start the server:"
echo "1. Edit .env file with your database and JWT settings"
echo "2. Run database migrations: ./chat-server -migrate"
echo "3. Start server: ./chat-server"
echo ""
echo "API will be available at: http://localhost:8080"
echo "WebSocket will be available at: ws://localhost:8080/ws"
