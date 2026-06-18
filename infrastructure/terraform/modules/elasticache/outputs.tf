output "redis_endpoint" {
  # this provides the URL laravel needs to connect to redis
  value = aws_elasticache_serverless_cache.redis.endpoint[0].address
}
