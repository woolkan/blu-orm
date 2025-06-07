# Blu DB ORM

Lekka implementacja ORM w architekturze Data Mapper.

```php
require 'vendor/autoload.php';

use Blu\DB\Storage\Connection;
use Blu\DB\Cache\RedisCache;
use Blu\DB\Mapper\UserMapper;
use Blu\DB\Model\User;

Connection::configure('mysql:host=localhost;dbname=app', 'root', '');
$redis = new Redis();
$redis->connect('127.0.0.1');
$cache = new RedisCache($redis);
new UserMapper($cache);

$user = UserMapper::findById('uuid');
foreach ($user->posts() as $post) {
    // ...
}
```
