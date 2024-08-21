# 手机号码归属地查询 - 仅适用于中国大陆

此项目主要用于需要对手机号进行本地验证和查询归属地，或防止联网请求第三方 `API` 造成可能的隐私泄露问题。欢迎PR！

Inspired by [@ls0f/phone](https://github.com/ls0f/phone/tree/master)


### 数据文件 `phone.dat` 内部格式

    
    | 4 bytes |                     <- phone.dat 版本号
    ------------
    | 4 bytes |                     <-  第一个索引的偏移
    -----------------------
    |  offset - 8            |      <-  记录区
    -----------------------
    |  index                 |      <-  索引区
    -----------------------

 - 头部 头部为8个字节，版本号为4个字节，第一个索引的偏移为4个字节(<4si)。
 - 记录区 中每条记录的格式为"<省份>|<城市>|<邮编>|<长途区号>\0"。 每条记录以'\0'结束。
 - 索引区 中每条记录的格式为"<手机号前七位><记录区的偏移><卡类型>"，每个索引的长度为9个字节(<iiB)。

#### 解析步骤

 - 解析头部8个字节，得到索引区的第一条索引的偏移。
 - 在索引区用二分查找得出手机号在记录区的记录偏移。
 - 在记录区从上一步得到的记录偏移处取数据，直到遇到'\0'


### 使用方法

#### 1. 安装包

```bash
composer require swatchion/phone:~1.0.0
```

#### 2. 查询归属地

```php

$num = 1521147;
    
$query = new \Swatchion\Phone\PhoneLocation();
$locate = $query->find($num);

echo $locale;

```




## License
#### [MIT](https://opensource.org/licenses/mit-license.php)