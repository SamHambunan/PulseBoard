resource "aws_security_group" "alb" {
  name        = "pulseboard-${var.environment}-alb-sg"
  description = "Allow public HTTP traffic"
  vpc_id      = var.vpc_id

  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"] # the entire internet
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_lb" "main" {
  name               = "pulseboard-${var.environment}-alb"
  internal           = false # Public-facing
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb.id]
  subnets            = var.public_subnet_ids
}

# the Target Group tells the ALB where our laravel API servers are
resource "aws_lb_target_group" "api" {
  name        = "pulseboard-${var.environment}-api-tg"
  port        = 8080 # The port Nginx listens on
  protocol    = "HTTP"
  vpc_id      = var.vpc_id
  target_type = "ip"

  health_check {
    path                = "/health" # The health endpoint we created in Nginx in Phase 0
    interval            = 30
    healthy_threshold   = 3
    unhealthy_threshold = 3
  }
}

resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.main.arn
  port              = "80"
  protocol          = "HTTP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.api.arn
  }
}
