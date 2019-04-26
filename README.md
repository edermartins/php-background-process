# php-process-backgroud
This is a very simple classes to run and manage processes Windows or Linux in background using old php &lt;= 5.6

You can run any kind of process in backgroud or PHP files as only the PHP >= 7 can do it using [pthreads](https://www.php.net/manual/pt_BR/book.pthreads.php)

Unfortunatelly some systems has a lot of support limitation if we upgrade the PHP version. To solve some problems I did these simples classes to help me. I tryed to keep it as simple as I could.

# How to use it

Just clone the project or download it in any folder and run the ```composer update```. The composer will generate the autoload inside de ```vendor``` folder. Include it on your project and use as you wish.

```
require_once(__DIR__.'/vendor/autoload.php');

use EderMartins\BackgroudProcess\TaskManager;
$tasks = new TaskManager();
```

Methods available:
- **add**: Add the process in the TaskManager list and start it on background
- **check**: Check if the process is runnig by PID. If not remove it from the list
- **checkAll**: Do the same as ```check``` but for all process in the list
- **count**: Count how process are in the TaskManager list
- **remove**: Kill one process and check if it is running. If not remove if from the list. The default is graceful kill, but you can use a boolean ```true``` and kill the process immediately. Also if the process take time to be killed, it can continue in the list, so you need to use a combination of ```count``` and ```check``` to garantee that all processes was not running.
