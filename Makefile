up:
	docker compose up -d

down:
	docker compose down

shell:
	docker compose exec app bash

test:
	docker compose exec app php artisan test

migrate:
	docker compose exec app php artisan migrate --seed

fresh:
	docker compose exec app php artisan migrate:fresh --seed

logs:
	docker compose logs -f app consumer reverb

kafka-topics:
	docker compose exec kafka /opt/kafka/bin/kafka-topics.sh \
		--bootstrap-server localhost:9092 \
		--create --topic metrics.events \
		--partitions 3 --replication-factor 1
