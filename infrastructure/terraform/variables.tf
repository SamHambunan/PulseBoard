variable "aws_region" {
  description = "The AWS region to deploy into"
  type        = string
  default     = "ap-southeast-1"
}

variable "environment" {
  description = "The deployment environment (e.g., staging, production)"
  type        = string
}

variable "vpc_cidr" {
  description = "The CIDR block for the VPC"
  type        = string
  default     = "10.0.0.0/16"
}
