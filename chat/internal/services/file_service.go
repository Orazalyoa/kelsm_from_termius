package services

import (
	"crypto/rand"
	"encoding/hex"
	"fmt"
	"io"
	"kelisim-chat/internal/config"
	"kelisim-chat/internal/database"
	"kelisim-chat/internal/models"
	"mime/multipart"
	"os"
	"path/filepath"
	"strings"
	"time"
)

// FileService 文件服务
type FileService struct{}

// NewFileService 创建文件服务
func NewFileService() *FileService {
	return &FileService{}
}

// UploadFile 上传文件
func (s *FileService) UploadFile(file *multipart.FileHeader, chatID uint) (string, string, int64, error) {
	// 检查文件大小
	if file.Size > config.AppConfig.Storage.MaxFileSize {
		return "", "", 0, fmt.Errorf("file size exceeds limit")
	}

	// 生成唯一文件名
	fileName := s.generateFileName(file.Filename)

	// 创建存储目录
	year := time.Now().Format("2006")
	month := time.Now().Format("01")
	dir := filepath.Join(config.AppConfig.Storage.Path, "chats", fmt.Sprintf("%d", chatID), year, month)

	if err := os.MkdirAll(dir, 0755); err != nil {
		return "", "", 0, fmt.Errorf("failed to create directory: %w", err)
	}

	// 文件路径
	filePath := filepath.Join(dir, fileName)

	// 打开上传的文件
	src, err := file.Open()
	if err != nil {
		return "", "", 0, fmt.Errorf("failed to open uploaded file: %w", err)
	}
	defer src.Close()

	// 创建目标文件
	dst, err := os.Create(filePath)
	if err != nil {
		return "", "", 0, fmt.Errorf("failed to create file: %w", err)
	}
	defer dst.Close()

	// 复制文件内容
	if _, err := io.Copy(dst, src); err != nil {
		return "", "", 0, fmt.Errorf("failed to copy file: %w", err)
	}

	// 生成访问URL
	relativePath := filepath.Join("chats", fmt.Sprintf("%d", chatID), year, month, fileName)
	fileURL := config.AppConfig.Storage.BaseURL + "/" + strings.ReplaceAll(relativePath, "\\", "/")

	return fileURL, file.Filename, file.Size, nil
}

// generateFileName 生成唯一文件名
func (s *FileService) generateFileName(originalName string) string {
	// 生成随机字符串
	bytes := make([]byte, 16)
	rand.Read(bytes)
	randomStr := hex.EncodeToString(bytes)

	// 获取文件扩展名
	ext := filepath.Ext(originalName)
	if ext == "" {
		ext = ".bin"
	}

	// 组合文件名
	return randomStr + ext
}

// GetFileType 根据文件扩展名获取文件类型
func (s *FileService) GetFileType(fileName string) string {
	ext := strings.ToLower(filepath.Ext(fileName))

	switch ext {
	case ".jpg", ".jpeg", ".png", ".gif", ".bmp", ".webp":
		return "image"
	case ".mp4", ".avi", ".mov", ".wmv", ".flv", ".webm":
		return "video"
	default:
		return "document"
	}
}

// GetFilePath 根据URL获取文件路径
func (s *FileService) GetFilePath(fileURL string) string {
	// 从URL中提取相对路径
	baseURL := config.AppConfig.Storage.BaseURL
	if !strings.HasSuffix(baseURL, "/") {
		baseURL += "/"
	}

	relativePath := strings.TrimPrefix(fileURL, baseURL)
	return filepath.Join(config.AppConfig.Storage.Path, relativePath)
}

// FileExists 检查文件是否存在
func (s *FileService) FileExists(fileURL string) bool {
	filePath := s.GetFilePath(fileURL)
	_, err := os.Stat(filePath)
	return !os.IsNotExist(err)
}

// DeleteFile 删除文件
func (s *FileService) DeleteFile(fileURL string) error {
	filePath := s.GetFilePath(fileURL)
	return os.Remove(filePath)
}

// GetFileSize 获取文件大小
func (s *FileService) GetFileSize(fileURL string) (int64, error) {
	filePath := s.GetFilePath(fileURL)
	fileInfo, err := os.Stat(filePath)
	if err != nil {
		return 0, err
	}
	return fileInfo.Size(), nil
}

// GetChatFiles 获取聊天室的文件列表
func (s *FileService) GetChatFiles(chatID uint, limit int, offset int) ([]models.Message, error) {
	var messages []models.Message
	
	err := database.DB.Where("chat_id = ? AND deleted_at IS NULL AND (type = ? OR type = ?) AND file_url IS NOT NULL", 
		chatID, "image", "document").
		Preload("Sender").
		Order("created_at DESC").
		Limit(limit).
		Offset(offset).
		Find(&messages).Error
	
	return messages, err
}