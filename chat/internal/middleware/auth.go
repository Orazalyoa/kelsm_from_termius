package middleware

import (
	"kelisim-chat/internal/config"
	"kelisim-chat/internal/database"
	"kelisim-chat/internal/models"
	"net/http"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
	"github.com/sirupsen/logrus"
)

// AuthMiddleware JWT 认证中间件
func AuthMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		// 从 Authorization header 获取 token
		authHeader := c.GetHeader("Authorization")
		if authHeader == "" {
			logrus.Warn("Authorization header missing")
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Authorization header required"})
			c.Abort()
			return
		}

		// 检查 Bearer 前缀
		tokenString := strings.TrimPrefix(authHeader, "Bearer ")
		if tokenString == authHeader {
			logrus.Warnf("Invalid authorization header format: %s", authHeader)
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid authorization header format"})
			c.Abort()
			return
		}

		// 解析 JWT token
		token, err := jwt.Parse(tokenString, func(token *jwt.Token) (interface{}, error) {
			// 验证签名方法
			if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
				logrus.Warnf("Invalid signing method: %v", token.Method)
				return nil, jwt.ErrSignatureInvalid
			}
			return []byte(config.AppConfig.JWT.Secret), nil
		})

		if err != nil {
			logrus.Warnf("JWT parse error: %v", err)
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid token", "details": err.Error()})
			c.Abort()
			return
		}

		if !token.Valid {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Token is not valid"})
			c.Abort()
			return
		}

		// 获取 claims
		claims, ok := token.Claims.(jwt.MapClaims)
		if !ok {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid token claims"})
			c.Abort()
			return
		}

		// 检查是否是 Operator Token
		if isOperator, ok := claims["is_operator"].(bool); ok && isOperator {
			// Operator Token - 提取 operator_id
			operatorIDFloat, ok := claims["operator_id"].(float64)
			if !ok {
				c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid operator ID in token"})
				c.Abort()
				return
			}

			operatorID := uint(operatorIDFloat)
			operatorName, _ := claims["operator_name"].(string)
			username, _ := claims["username"].(string)

			// Store operator information in context (compatible with user context)
			c.Set("operator_id", operatorID)
			c.Set("operator_name", operatorName)
			c.Set("username", username)
			c.Set("is_operator", true)
			c.Set("user_id", operatorID) // For compatibility with handlers

			logrus.WithFields(logrus.Fields{
				"operator_id":   operatorID,
				"operator_name": operatorName,
			}).Debug("Operator authenticated via AuthMiddleware")

			c.Next()
			return
		}

		// 获取用户ID (Laravel JWT 使用 sub 字段存储用户ID)
		userIDFloat, ok := claims["sub"].(float64)
		if !ok {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid user ID in token"})
			c.Abort()
			return
		}

		userID := uint(userIDFloat)

		// 获取用户类型 (Laravel JWT 自定义字段)
		userType, _ := claims["user_type"].(string)

		// 从数据库加载用户信息
		var user models.User
		if err := database.DB.First(&user, userID).Error; err != nil {
			logrus.Warnf("User not found in database: %d, error: %v", userID, err)
			c.JSON(http.StatusUnauthorized, gin.H{"error": "User not found"})
			c.Abort()
			return
		}

		// 验证用户类型是否匹配 (可选验证)
		if userType != "" && user.UserType != userType {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "User type mismatch"})
			c.Abort()
			return
		}

		// 将用户信息存储到上下文中
		c.Set("user", user)
		c.Set("user_id", userID)
		c.Set("user_type", userType)

		c.Next()
	}
}

// OptionalAuthMiddleware 可选的 JWT 认证中间件（用于 WebSocket）
func OptionalAuthMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		// 从 query 参数获取 token
		tokenString := c.Query("token")
		if tokenString == "" {
			c.Next()
			return
		}

		// 解析 JWT token
		token, err := jwt.Parse(tokenString, func(token *jwt.Token) (interface{}, error) {
			if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
				return nil, jwt.ErrSignatureInvalid
			}
			return []byte(config.AppConfig.JWT.Secret), nil
		})

		if err != nil || !token.Valid {
			c.Next()
			return
		}

		// 获取 claims
		claims, ok := token.Claims.(jwt.MapClaims)
		if !ok {
			c.Next()
			return
		}

		// 检查是否是 Operator Token
		if isOperator, ok := claims["is_operator"].(bool); ok && isOperator {
			// Operator Token
			operatorIDFloat, ok := claims["operator_id"].(float64)
			if !ok {
				c.Next()
				return
			}

			operatorID := uint(operatorIDFloat)
			operatorName, _ := claims["operator_name"].(string)
			username, _ := claims["username"].(string)

			// Store operator information in context
			c.Set("operator_id", operatorID)
			c.Set("operator_name", operatorName)
			c.Set("username", username)
			c.Set("is_operator", true)
			c.Set("user_id", operatorID) // For compatibility

			c.Next()
			return
		}

		// 获取用户ID (Laravel JWT 使用 sub 字段存储用户ID)
		userIDFloat, ok := claims["sub"].(float64)
		if !ok {
			c.Next()
			return
		}

		userID := uint(userIDFloat)

		// 获取用户类型 (Laravel JWT 自定义字段)
		userType, _ := claims["user_type"].(string)

		// 从数据库加载用户信息
		var user models.User
		if err := database.DB.First(&user, userID).Error; err != nil {
			c.Next()
			return
		}

		// 验证用户类型是否匹配 (可选验证)
		if userType != "" && user.UserType != userType {
			c.Next()
			return
		}

		// 将用户信息存储到上下文中
		c.Set("user", user)
		c.Set("user_id", userID)
		c.Set("user_type", userType)

		c.Next()
	}
}

// GetUserFromContext 从上下文中获取用户信息
func GetUserFromContext(c *gin.Context) (*models.User, bool) {
	user, exists := c.Get("user")
	if !exists {
		return nil, false
	}
	userModel, ok := user.(models.User)
	return &userModel, ok
}

// GetUserIDFromContext 从上下文中获取用户ID
func GetUserIDFromContext(c *gin.Context) (uint, bool) {
	userID, exists := c.Get("user_id")
	if !exists {
		return 0, false
	}
	id, ok := userID.(uint)
	return id, ok
}
