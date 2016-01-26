# SinriSSA
SINRI Slow Sql log Analyzer

## Background
Do you have a large slow sql log file needing analyzing?

Even it has been too large that beyond the ability of the common script, such as `myprofi`?

Yes SinriSSA is designed for such bad situation. 

Anyway, it of course has limitations based on your hardware and software conditions.

## USAGE

*  php SinriSSA.php [-eE] [-s SORT=ave_time] [-v MIN,MAX] [-t TOP=10] -f FILE
*  -e show one sample sql
*  -E show all sample sqls
*  -f determine the log file, non-optional
*  -s determine the sort method, `ave_time` and `freq_sum` supported
*  -t the number of sqls to be displayed by order after sorting, 10 for default, 0 for all
*  -v sql average time range filter, such as `100,10000`, used as [100,10000)
 
## Sample

    php SinriSSA.php -f mysql-slow.log -v 100,150 -s ave_time -t 3 -e


The above command would be executed as following:

1. read file `mysql-slow.log` and find out all sql records;
1. group the sqls with their normalized format, for simple, use md5 for each as unique key;
1. compute average time for each normailized sql format group;
1. filter the group according the range setting;
1. sort according to the settings, and filter the tail;
1. display the result.

## Thinking

1. SQL is a `structured` query language, so that we could find that there are often sqls only parameters differ.
2. In SQL, parameters are of two type, numbers and strings.
3. Also there is one special form, The IN Operator.
4. If we turn the above into certain symbols, we could make out the structure of one sql.
5. Then we can use the sought structures of SQLs to group them!
