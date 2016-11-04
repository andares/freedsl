# FreeDSL 简介

一个简单的DSL生成器，可使用回调的方式生成DSL查询语句。

查询语句为elasticsearch的官方php包（composer: elasticsearch/elasticsearch）生成，未在其他elasticsearch客户端库中验证过。

## 创建新查询

每次创建一个新查询时new一个builder对象出来：

```
$index   = 'myindex-*';
$type    = 'some_entity';
$builder = new FreeDSL\Builder($index, $type);
```

当查询参数设置完毕后通过invoke魔术方法获得生成的查询数组：

```
// 生成 dsl 查询
$dsl = $builder();

// 使用其进行查询

// 这是创建elasitsearch官方的php客户端
$client = Elasticsearch\ClientBuilder::create()->build();
$result = $client->search($dsl);
```

## 直接设置查询语句

利用属性重载进行直接参数设置，如根据id获取文档：

```
$builder->id = $id;
$result = $client->get($builder());
```

或者进行一次查询：

```
$builder->body = [
  'query' => [
    'match' => ['name' => $name],
  ],
];
$result = $client->search($builder());
```

## 使用方法重载设置参数

使用```body()```方法可以更具扩展性的设置参数：

```
$builder->body()
  ->query()->bool()
    ->must()
      ->term(['user.name' => $name]);
$result = $client->search($builder());
```

每次调用```body()```方法，会重置构造指针移动到查询语句的body根位置。此时每调用一次重载的虚拟方法都会向下推进一层。

>
  除了魔术方法外，Builder对象只有```body()```，```named()```和```yaml()```这三个全小写的实体方法。其他的虚拟方法均通过重载用于构建dsl的键值。

### push指令模式

有部分dsl指令的下级允许是一个数组，比如```must```指令。如果不使用push模式，你不得不在```must()```中以参数形式传入一个数组来描述，如下所示：

```
$builder->body()
  ->query()->bool()
    ->must([
        'term' => ['parsed.base.name' => 'user_login'],
    ], [
        'term' => ['parsed.extra.user_id' => $id],
    ]);
```

使用push模式，可以使某些指令以数组形式添加子项，可以写成如下形式：

```
$builder
  ->body()
    ->query()->bool()
      ->must()
        ->term(['parsed.base.name' => 'user_login'])
  ->body()
    ->query()->bool()
      ->must()
        ->term(['parsed.extra.user_id' => $id]);
```

目前默认为push模式的指令有：

- must
- should
- must_not

你也可以使用```setPushKey()```方法来添加需要使用push模式的指令：

```
$builder->setPushKey('custom_push_key');
```

## 创建带符号的查询指令

一些查询key值带符号不方便使用方法名使用```named()```方法可以创建带符号的查询

```
$builder
  ->body()
    ->query()
      ->prefix()
        ->named('@name')
          ->value('ki');
```

## 复杂一些的查询构建范例

以下是一个在当前项目中用到的聚合查询范例

```
$builder
  ->body()->query()->bool()->must()
    ->match_phrase()
    ->body_field()
    ->query('room/enter')
  ->body()->query()->bool()->must()
    ->range()
    ->named('@timestamp', [
      'gte'       => $start_time,
      'lt'        => $end_time,
      'format'    => 'yyyy-MM-dd',
      'time_zone' => '+08:00',
    ]);

$builder
  ->body()->aggs()->watch_count()
    ->terms()
    ->field('response.data.master.id')
  ->body()->aggs()->watch_count()
    ->terms()
    ->size($limit);

$builder->body()
    ->sort(['@timestamp' => 'desc'])
  ->body()
    ->from(0)
  ->body()
    ->size(1);

$dsl = $builder();
// ...执行查询
```

## yaml支持

builder类也支持使用yaml来描述查询语句的一部分，如下所示：

```
$builder->body()->yaml_test()->yaml("
# yaml for dsl

query:
  bool:
    must:
      - term:
          gender: $gender
      - prefix:
          name: $prefix
");

$dsl = $builder();
```
