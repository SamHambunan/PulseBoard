resource "aws_security_group" "rds" {
  name        = "pulseboard-${var.environment}-rds-sg"
  description = "Allow internal traffic to Aurora"
  vpc_id      = var.vpc_id

  ingress {
    from_port   = 3306
    to_port     = 3306
    protocol    = "tcp"
    cidr_blocks = ["10.0.0.0/16"] # Only allow traffic from inside our VPC
  }
}

resource "aws_db_subnet_group" "main" {
  name       = "pulseboard-${var.environment}-db-subnet"
  subnet_ids = var.private_subnet_ids
}

resource "aws_rds_cluster" "aurora" {
  cluster_identifier          = "pulseboard-${var.environment}-aurora"
  engine                      = "aurora-mysql"
  engine_version              = "8.0.mysql_aurora.3.05.2"
  database_name               = "pulseboard"
  master_username             = "admin"

  # AWS automatically generates a highly secure password and stores it in Secrets Manager!
  manage_master_user_password = true

  db_subnet_group_name        = aws_db_subnet_group.main.name
  vpc_security_group_ids      = [aws_security_group.rds.id]
  skip_final_snapshot         = true # Set to false in real production!

  serverlessv2_scaling_configuration {
    min_capacity = 0.5
    max_capacity = 8.0
  }
}

resource "aws_rds_cluster_instance" "aurora_instances" {
  count              = 2 # One primary, one standby for failover
  identifier         = "pulseboard-${var.environment}-instance-${count.index}"
  cluster_identifier = aws_rds_cluster.aurora.id
  instance_class     = "db.serverless"
  engine             = aws_rds_cluster.aurora.engine
  engine_version     = aws_rds_cluster.aurora.engine_version
}

# Call the Networking Module
module "networking" {
  source      = "./modules/networking"
  environment = var.environment
  vpc_cidr    = var.vpc_cidr
}

# Call the RDS Module
module "rds" {
  source             = "./modules/rds"
  environment        = var.environment
  vpc_id             = module.networking.vpc_id
  private_subnet_ids = module.networking.private_subnet_ids
}
