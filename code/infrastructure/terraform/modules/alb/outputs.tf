output "target_group_arn" {
  value = aws_lb_target_group.api.arn
}

output "dns_name" {
  # this gives us the URL we type into our browser to test the app!
  value = aws_lb.main.dns_name
}
