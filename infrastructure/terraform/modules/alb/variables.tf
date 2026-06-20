variable "environment" { type = string }
variable "vpc_id" { type = string }

# ALBs MUST be in public subnets so the internet can reach them
variable "public_subnet_ids" { type = list(string) }
