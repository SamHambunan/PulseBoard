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
