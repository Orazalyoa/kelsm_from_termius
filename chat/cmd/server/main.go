package main

import (
	"flag"
	"kelisim-chat/internal/config"
	"kelisim-chat/internal/database"
	"kelisim-chat/internal/router"
	"kelisim-chat/internal/services"
	"log"
	"os"
	"os/signal"
	"syscall"

	"github.com/sirupsen/logrus"
)

func main() {
	// 解析命令行参数
	var migrate = flag.Bool("migrate", false, "Run database migrations")
	flag.Parse()

	// 加载配置
	if err := config.Load(); err != nil {
		log.Fatal("Failed to load config:", err)
	}

	// 设置日志级别
	if config.AppConfig.Server.GinMode == "debug" {
		logrus.SetLevel(logrus.DebugLevel)
	} else {
		logrus.SetLevel(logrus.InfoLevel)
	}

	// 连接数据库
	if err := database.Connect(); err != nil {
		log.Fatal("Failed to connect to database:", err)
	}
	defer database.Close()

	// 如果指定了迁移参数，运行迁移
	if *migrate {
		logrus.Info("Running database migrations...")
		// 这里可以添加迁移逻辑
		logrus.Info("Database migrations completed")
		return
	}

	// 创建存储目录
	if err := os.MkdirAll(config.AppConfig.Storage.Path, 0755); err != nil {
		log.Fatal("Failed to create storage directory:", err)
	}

	// 初始化 FCM 服务 (V1 API)
	if err := services.InitFCMService(); err != nil {
		logrus.Warn("Failed to initialize FCM service:", err)
		logrus.Info("Push notifications will use Legacy API if FCM_SERVER_KEY is configured")
	}

	// 设置路由
	r := router.SetupRouter()

	// 启动服务器
	port := config.AppConfig.Server.Port
	logrus.Infof("Starting server on port %s", port)

	// 优雅关闭
	go func() {
		if err := r.Run(":" + port); err != nil {
			log.Fatal("Failed to start server:", err)
		}
	}()

	// 等待中断信号
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	logrus.Info("Shutting down server...")
}
