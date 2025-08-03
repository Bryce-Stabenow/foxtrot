hot:
	composer run dev

dbfreshseed:
	php artisan migrate:fresh --seed

test:
	php artisan test
	
tinker:
	php artisan tinker