output "kafka_bootstrap_brokers" {
  # this provides the comma-separated list of kafka IPs for .env fil in laravel
  value = aws_msk_serverless_cluster.kafka.endpoints[0].endpoints
}
