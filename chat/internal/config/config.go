package config

import (
	"os"
	"strconv"

	"github.com/joho/godotenv"
	"github.com/sirupsen/logrus"
)

type Config struct {
	Server                ServerConfig
	Database              DatabaseConfig
	JWT                   JWTConfig
	Storage               StorageConfig
	LLM                   LLMConfig
	FCMServerKey          string // Legacy API (deprecated)
	FCMServiceAccountPath string // V1 API (recommended)
}

type ServerConfig struct {
	Port    string
	GinMode string
}

type DatabaseConfig struct {
	Host     string
	Port     string
	Database string
	Username string
	Password string
}

type JWTConfig struct {
	Secret string
	Algo   string
}

type StorageConfig struct {
	Path        string
	BaseURL     string
	MaxFileSize int64
}

type LLMConfig struct {
	APIKey      string
	APIBase     string
	Model       string
	MaxTokens   int
	Temperature float64
	Timeout     int
}

var AppConfig *Config

func Load() error {
	// 尝试加载 .env 文件
	if err := godotenv.Load(); err != nil {
		logrus.Warn("No .env file found, using environment variables")
	}

	AppConfig = &Config{
		Server: ServerConfig{
			Port:    getEnv("PORT", "8080"),
			GinMode: getEnv("GIN_MODE", "debug"),
		},
		Database: DatabaseConfig{
			Host:     getEnv("DB_HOST", "localhost"),
			Port:     getEnv("DB_PORT", "3306"),
			Database: getEnv("DB_DATABASE", "kelisim"),
			Username: getEnv("DB_USERNAME", "root"),
			Password: getEnv("DB_PASSWORD", ""),
		},
		JWT: JWTConfig{
			Secret: getEnv("JWT_SECRET", ""),
			Algo:   getEnv("JWT_ALGO", "HS256"),
		},
		Storage: StorageConfig{
			Path:        getEnv("STORAGE_PATH", "./storage/chat-files"),
			BaseURL:     getEnv("STORAGE_BASE_URL", "http://localhost:8080/storage/chat-files"),
			MaxFileSize: getEnvAsInt64("MAX_FILE_SIZE", 10485760), // 10MB
		},
		LLM: LLMConfig{
			APIKey:      getEnv("DEEPSEEK_API_KEY", ""),
			APIBase:     getEnv("DEEPSEEK_API_BASE", "https://api.deepseek.com/v1"),
			Model:       getEnv("DEEPSEEK_MODEL", "deepseek-chat"),
			MaxTokens:   getEnvAsInt("DEEPSEEK_MAX_TOKENS", 2000),
			Temperature: getEnvAsFloat64("DEEPSEEK_TEMPERATURE", 0.7),
			Timeout:     getEnvAsInt("DEEPSEEK_TIMEOUT", 30),
		},
		FCMServerKey:          getEnv("FCM_SERVER_KEY", ""),
		FCMServiceAccountPath: getEnv("FCM_SERVICE_ACCOUNT_PATH", ""),
	}

	// 验证必要的配置
	if AppConfig.JWT.Secret == "" {
		logrus.Fatal("JWT_SECRET is required")
	}

	return nil
}

func getEnv(key, defaultValue string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return defaultValue
}

func getEnvAsInt64(key string, defaultValue int64) int64 {
	if value := os.Getenv(key); value != "" {
		if intValue, err := strconv.ParseInt(value, 10, 64); err == nil {
			return intValue
		}
	}
	return defaultValue
}

func getEnvAsInt(key string, defaultValue int) int {
	if value := os.Getenv(key); value != "" {
		if intValue, err := strconv.Atoi(value); err == nil {
			return intValue
		}
	}
	return defaultValue
}

func getEnvAsFloat64(key string, defaultValue float64) float64 {
	if value := os.Getenv(key); value != "" {
		if floatValue, err := strconv.ParseFloat(value, 64); err == nil {
			return floatValue
		}
	}
	return defaultValue
}
