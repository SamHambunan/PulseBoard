resource "aws_security_group" "kafka" {
  name        = "pulseboard-${var.environment}-kafka-sg"
  description = "Allow internal traffic to Kafka"
  vpc_id      = var.vpc_id

  ingress {
    from_port   = 9092
    to_port     = 9092
    protocol    = "tcp"
    cidr_blocks = ["10.0.0.0/16"]
  }
}

# creates a highly-available serverless Kafka cluster
resource "aws_msk_serverless_cluster" "kafka" {
  cluster_name = "pulseboard-${var.environment}-kafka"

  vpc_config {
    subnet_ids         = var.private_subnet_ids
    security_group_ids = [aws_security_group.kafka.id]
  }

  client_authentication {
    sasl {
      iam {
        enabled = true # IAM for secure log in
      }
    }
  }
}
