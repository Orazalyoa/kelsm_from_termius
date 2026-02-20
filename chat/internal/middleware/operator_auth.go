package middleware

import (
	"kelisim-chat/internal/config"
	"net/http"
	"strings"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v4"
	"github.com/sirupsen/logrus"
)

// OperatorAuthMiddleware validates operator (admin) JWT tokens
func OperatorAuthMiddleware() gin.HandlerFunc {
	return func(c *gin.Context) {
		// Get token from header
		authHeader := c.GetHeader("Authorization")
		if authHeader == "" {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Authorization header required"})
			c.Abort()
			return
		}

		// Extract token
		parts := strings.Split(authHeader, " ")
		if len(parts) != 2 || parts[0] != "Bearer" {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid authorization format"})
			c.Abort()
			return
		}

		tokenString := parts[1]

		// Parse and validate token
		token, err := jwt.Parse(tokenString, func(token *jwt.Token) (interface{}, error) {
			// Make sure the signing method is HMAC
			if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
				return nil, jwt.ErrSignatureInvalid
			}
			// Return the secret key for validation (must match Laravel backend)
			return []byte(config.AppConfig.JWT.Secret), nil
		})

		if err != nil || !token.Valid {
			logrus.WithError(err).Error("Invalid operator token")
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid or expired token"})
			c.Abort()
			return
		}

		// Extract claims
		claims, ok := token.Claims.(jwt.MapClaims)
		if !ok {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid token claims"})
			c.Abort()
			return
		}

		// Check if this is an operator token
		isOperator, ok := claims["is_operator"].(bool)
		if !ok || !isOperator {
			c.JSON(http.StatusForbidden, gin.H{"error": "Not an operator"})
			c.Abort()
			return
		}

		// Extract operator information
		operatorID, ok := claims["operator_id"].(float64) // JSON numbers are float64
		if !ok {
			c.JSON(http.StatusUnauthorized, gin.H{"error": "Invalid operator ID"})
			c.Abort()
			return
		}

		operatorName, _ := claims["operator_name"].(string)
		username, _ := claims["username"].(string)

		// Store operator information in context
		c.Set("operator_id", uint(operatorID))
		c.Set("operator_name", operatorName)
		c.Set("username", username)
		c.Set("is_operator", true)

		// Also set user_id for compatibility with existing handlers
		// Operator doesn't have a real user_id, but we set operator_id as a fallback
		// This allows operators to use chat APIs that check user_id
		c.Set("user_id", uint(operatorID))

		logrus.WithFields(logrus.Fields{
			"operator_id":   operatorID,
			"operator_name": operatorName,
			"username":      username,
		}).Debug("Operator authenticated")

		c.Next()
	}
}

// GetOperatorIDFromContext retrieves the operator ID from the context
func GetOperatorIDFromContext(c *gin.Context) (uint, bool) {
	operatorID, exists := c.Get("operator_id")
	if !exists {
		return 0, false
	}

	id, ok := operatorID.(uint)
	return id, ok
}

// GetOperatorNameFromContext retrieves the operator name from the context
func GetOperatorNameFromContext(c *gin.Context) (string, bool) {
	operatorName, exists := c.Get("operator_name")
	if !exists {
		return "", false
	}

	name, ok := operatorName.(string)
	return name, ok
}
