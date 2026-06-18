terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }

  # In a real environment, you store state in S3.
  # We will use local state for this codebase foundation.
  backend "local" {
    path = "terraform.tfstate"
  }
}

provider "aws" {
  region = var.aws_region

  default_tags {
    tags = {
      Project     = "PulseBoard"
      Environment = var.environment
      ManagedBy   = "Terraform"
    }
  }
}

# redis module
module "redis" {
  source             = "./modules/elasticache"
  environment        = var.environment
  vpc_id             = module.networking.vpc_id
  private_subnet_ids = module.networking.private_subnet_ids
}

# kafka module
module "kafka" {
  source             = "./modules/msk"
  environment        = var.environment
  vpc_id             = module.networking.vpc_id
  private_subnet_ids = module.networking.private_subnet_ids
}

# networking module fencing
module "networking" {
  source      = "./modules/networking"
  environment = var.environment
  vpc_cidr    = var.vpc_cidr
}

# rds module
module "rds" {
  source             = "./modules/rds"
  environment        = var.environment
  vpc_id             = module.networking.vpc_id
  private_subnet_ids = module.networking.private_subnet_ids
}
