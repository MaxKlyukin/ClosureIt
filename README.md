# ClosureIt

## Please do not use it in production!

ClosureIt is a PHP library that can generate SQL statement from PHP closures.

It can be used for query builders, for example:
```php
$users = $userCollection->createQueryBuilder()->where(function(User $user) {
	return $user->name == 'John' && $user->age >= 18;
})->sortBy('age')->getAll();
```
It has many limitations, for example, it doesn't work with obfuscated code and it doesn't work with inline nested closures.

This library is not for production environment, because it is slow,
and it is slow because it uses reflections and PHP code parsing with token_get_all function.

With another Dumper class it can generate code for another technology, for example MongoDB.

Examples you can find in test directory.