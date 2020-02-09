# level_up_task1.5
First set database name and port in .env then in the root folder of project 
run `php ./bin/console d:d:c` for creating the database and then run 
`./bin/console d:m:m` for running database migrations.

note: if you wanted to run commands in another environment you can add 
`--env=ENVIRONMENT_NAME` to console commands.

the number of users, products, sources,  and ... are defined as 
environment variable(take a look at .env or .env.test).

for loading fixture to test database you should run `php bin/console doctrine:fixtures:load --env=test`
for running migration command on test database you should run `./bin/console app:data-migration --env=test`


 