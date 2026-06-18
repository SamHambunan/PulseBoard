resource "aws_security_group" "redis" {
  name        = "pulseboard-${var.environment}-redis-sg"
  description = "Allow internal traffic to Redis"
  vpc_id      = var.vpc_id

  ingress {
    from_port   = 6379
    to_port     = 6379
    protocol    = "tcp"
    cidr_blocks = ["10.0.0.0/16"] # only allow traffic from inside the VPC
  }
}

resource "aws_elasticache_subnet_group" "main" {
  name       = "pulseboard-${var.environment}-redis-subnet"
  subnet_ids = var.private_subnet_ids
}

# Creates a Serverless Redis cache
resource "aws_elasticache_serverless_cache" "redis" {
  engine = "redis"
  name   = "pulseboard-${var.environment}-redis"

  cache_usage_limits {
    data_storage {
      maximum = 10
      unit    = "GB"
    }
    ecpu_per_second {
      maximum = 5000
    }
  }

  description              = "Serverless Redis for Rate Limiting and Pub/Sub"
  kms_key_id               = aws_kms_key.redis.arn
  major_engine_version     = "7"
  security_group_ids       = [aws_security_group.redis.id]
  subnet_ids               = var.private_subnet_ids
}

resource "aws_kms_key" "redis" {
  description             = "KMS key for Redis encryption"
  deletion_window_in_days = 7
}
