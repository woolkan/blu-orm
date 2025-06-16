# Blu DB ORM

Lekka implementacja ORM w architekturze Data Mapper.

```php
require 'vendor/autoload.php';

use Blu\DB\Cache\RedisCache;use Blu\DB\Mapper\UserMapper;use Blu\DB\Model\User;use Blu\DB\Storage\Connection;use Predis\Client as PredisClient;

Connection::configure('mysql:host=localhost;dbname=app', 'root', '');

// z rozszerzeniem ext-redis
$redis = new Redis();
$redis->connect('127.0.0.1');
$cache = new RedisCache($redis);

// lub z klientem Predis
// $predis = new PredisClient();
// $cache = new PredisCache($predis);

new UserMapper($cache);

$user = UserMapper::findById('uuid');
foreach ($user->posts() as $post) {
    // ...
}
```
