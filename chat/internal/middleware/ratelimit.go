package middleware

import (
	"fmt"
	"net/http"
	"sync"
	"time"

	"github.com/gin-gonic/gin"
)

// RateLimiter 简单的速率限制器
type RateLimiter struct {
	requests map[string]*userLimit
	mu       sync.RWMutex
	limit    int           // 每个时间窗口允许的请求数
	window   time.Duration // 时间窗口
}

type userLimit struct {
	count     int
	resetTime time.Time
}

// NewRateLimiter 创建速率限制器
func NewRateLimiter(limit int, window time.Duration) *RateLimiter {
	rl := &RateLimiter{
		requests: make(map[string]*userLimit),
		limit:    limit,
		window:   window,
	}

	// 定期清理过期记录
	go rl.cleanup()

	return rl
}

// Middleware 返回限流中间件
func (rl *RateLimiter) Middleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		userID, exists := GetUserIDFromContext(c)
		if !exists {
			// 未认证用户，跳过限流（由认证中间件处理）
			c.Next()
			return
		}

		key := getUserKey(userID)

		if !rl.allow(key) {
			c.JSON(http.StatusTooManyRequests, gin.H{
				"error": "Too many requests. Please try again later.",
			})
			c.Abort()
			return
		}

		c.Next()
	}
}

// allow 检查是否允许请求
func (rl *RateLimiter) allow(key string) bool {
	rl.mu.Lock()
	defer rl.mu.Unlock()

	now := time.Now()

	userLim, exists := rl.requests[key]
	if !exists || now.After(userLim.resetTime) {
		// 新用户或窗口已重置
		rl.requests[key] = &userLimit{
			count:     1,
			resetTime: now.Add(rl.window),
		}
		return true
	}

	if userLim.count >= rl.limit {
		return false
	}

	userLim.count++
	return true
}

// cleanup 定期清理过期记录
func (rl *RateLimiter) cleanup() {
	ticker := time.NewTicker(rl.window)
	defer ticker.Stop()

	for range ticker.C {
		rl.mu.Lock()
		now := time.Now()
		for key, limit := range rl.requests {
			if now.After(limit.resetTime) {
				delete(rl.requests, key)
			}
		}
		rl.mu.Unlock()
	}
}

// getUserKey 生成用户限流key
func getUserKey(userID uint) string {
	return fmt.Sprintf("user:%d", userID)
}

// AIRateLimitMiddleware AI功能专用的限流中间件
// 限制更严格，防止滥用
func AIRateLimitMiddleware() gin.HandlerFunc {
	limiter := NewRateLimiter(20, time.Minute) // 每分钟20次请求
	return limiter.Middleware()
}
