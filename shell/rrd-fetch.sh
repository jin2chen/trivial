#!/bin/bash

# 定义一个函数，用于收到 INT、TERM、QUIT、KILL 信号时清理临时文件

function cleanup () {

   # 定义临时文件

  final_output="/tmp/fetch.out"

  temp_output="/tmp/rrd_file_info_output"         

  fetch_output="/tmp/fetch.tmp"

  header_output="/tmp/header.out"

  date_output="/tmp/date.out"

  value_output="/tmp/value.out"

  paste_output="/tmp/paste.out"

  rm -f $final_output $temp_output $fetch_output $header_output $date_output $value_output $paste_output >/dev/null 2>&1

  exit 5
}


# 捕捉 INT、TERM、QUIT 、KILL 信号  

trap cleanup INT TERM QUIT KILL

#////////////////////////////////////////////////////////
#
# 下面开始主要流程
#
#/////////////////////////////////////////////////////////


echo && read -p "请输入 RRD 文件的名称:  " rrd_file_name

# 判断 RRD 文件是否为空或者 RRD 是否存在

if [ -z $rrd_file_name ] || [ ! -f $rrd_file_name ] ;then

        echo "ERROR:: 文件名为空，或者RRD 文件不存在"

        exit 1;

fi


# 检查是否对该 RRD 文件具备读权限

if [ ! -r $rrd_file_name ]; then

        echo "ERROR:: 你没有访问该 RRD 文件的权限 !!"

        exit 2;

fi

# 定义临时输出文件

final_output="/tmp/fetch.out"

temp_output="/tmp/rrd_file_info_output"        

fetch_output="/tmp/fetch.tmp"

header_output="/tmp/header.out"

date_output="/tmp/date.out"

value_output="/tmp/value.out"

paste_output="/tmp/paste.out"

rrdtool info $rrd_file_name > $temp_output

# 找出该 RRD 文件的版本。注意，该值不同于 RRDtool 的版本

rrd_file_ver=$(grep 'rrd_version = ' $temp_output | cut -d '=' -f 2|tr -d \"|tr -d '[:blank:]')

echo "文件 : [ $rrd_file_name ] , 文件版本 : [ $rrd_file_ver ]"

# 找出该 RRD 文件的 step 

rrd_file_step=$(grep 'step = ' $temp_output |cut -d '=' -f 2 |tr -d '[:blank:]')

echo && echo "Step : [ $rrd_file_step ]"

# 找出该 RRD 文件的最后更新时间

rrd_file_last_update=$(grep 'last_update = ' $temp_output |cut -d '=' -f 2|tr -d '[:blank:]')

echo && echo "最后更新 : [" $(date -d "1970-01-01 $rrd_file_last_update sec utc" '+%Y/%m/%d %H:%M:%S') "]"

# 找出该 RRD 文件中有多少个 DS
# 注 ：原来的脚本有误，应该是除以7，不是除以2，请注意
rrd_file_ds_num=$(($(grep 'ds\[.\+\]' $temp_output -c )/7))

echo && echo "DS 数量 : [ $rrd_file_ds_num ]"

# 并得出这些 DS 的名称 (DSN)

rrd_file_ds_name=$(grep 'ds\[.\+\]' $temp_output |cut -d '.' -f 1|sort -u)

# 输出 ds 部分信息的表头

echo && echo '编号------名称------------------类型------------------最小值------------------最大值------------------'

no=0

for i in $rrd_file_ds_name; do

        no=$((no+1))

        ds_name=$i

        ds_name=$(echo $ds_name|cut -d '[' -f 2 |cut -d ']' -f 1)        # 取出一个 DS 的名称

        # 取出该 DS 的类型（DST）
        ds_type=$(grep "ds\[$ds_name\].type = " $temp_output|cut -d '=' -f 2 |cut -d \" -f 2)        
        
        # 取出该 DS 的最小值
        ds_min=$(grep "ds\[$ds_name\].min = " $temp_output |cut -d '=' -f 2|cut -d \" -f 2)        
        
        # 取出该 DS 的最大值
        ds_max=$(grep "ds\[$ds_name\].max = " $temp_output |cut -d '=' -f 2|cut -d \" -f 2)

        printf "%-10d" $no
        printf "%-22s" $ds_name
        printf "%-22s" $ds_type
        printf "%-24s" $ds_min
        printf "%-24s" $ds_max
        echo

done

# 下面开始输出 RRA 的相关信息

# 下面的语句找出该 RRD 文件中的 RRA 数量

rrd_file_rra_num=$(grep 'rra\[[0-9]\+\]' $temp_output |cut -d '.' -f 1 |sort -u|wc -l|tr -d '[:blank:]')
echo && echo "RRA 数量 ： [ $rrd_file_rra_num ]"

echo

# 下面开始输出 RRA 部分信息的表头

echo "编号------统计类型--------------每CDP含PDP数量--------解释度------------------行数--------------------起始时间----------------------"

for ((i=0;i<$rrd_file_rra_num;i++));do

        printf "%-10d" $i

        # 取出该 RRA 的类型 （CF）
        rra_type=$(grep "rra\[$i\].cf = " $temp_output |cut -d '=' -f 2|tr -d '[:blank:]'|tr -d \")        

    # 把当前 RRA 的 CF 值放入数组 rra_type_array 中，后面在判断用户输入的 CF 是否存在时要用到
        rra_type_array[$i]=$rra_type

        printf "%-22s" $rra_type        

    # 找出每个 CDP 由多少个 PDP 组成
    pdps_per_cdp=$(grep "rra\[$i\].pdp_per_row = " $temp_output |cut -d '=' -f 2 |tr -d '[:blank:]') 
        printf "%-22s" $pdps_per_cdp                # 打印 RRA 中每个 CDP 由多少个 PDP 统计得出

    # 得出当前 RRA 的解释度（Resolution）
        rra_res=$((rrd_file_step * pdps_per_cdp))

    # 把当前 RRA 的 resolution 放入 rra_res_array 数组中，后面在查找合适的 RRA 时要用到
        rra_res_array[$i]=$rra_res        
        printf "%-25s" $rra_res

    # 得出该 RRA 的行数
        rra_rows=$(grep "rra\[$i\].rows = " $temp_output |cut -d '=' -f 2|tr -d '[:blank:]') 
    printf "%-22s" $rra_rows

    # 得出当前RRA第一个记录的时间戳
        rra_first=$(rrdtool first $rrd_file_name --rraindex $i)                
    
    # 转换为具体的时间
        rra_first_time=$(date -d "1970-01-01 $rra_first sec utc" '+%Y-%m-%d %H:%m:%S')        
         printf "%-19s" $rra_first_time
        
        # 得出该 RRA 的时间覆盖范围，也就是该 RRA 总共包含的秒数
        rra_time_range=$(($rra_res * $rra_rows))        
    
    # 把该 RRA 的时间覆盖范围存入 time_range 数组中，后面在查找合适的 RRA 时要用到
        time_range[$i]=$rra_time_range        

        echo

done

echo

# 下面提示用户输入起始时间,默认为1天前的这个时刻

read -p "起始时间 (YYYY-MM-DD HH:mm:ss) ：" fetch_start

# 检查用户输入的时间是否有效，如果无效则返回重新输入，是则转换为 timestamp 的格式

while true ; do

        if [ -z "$fetch_start" ];then        # 如果用户输入的时间为空，则默认为1天前

                fetch_start_timestamp=$(date -d '1 days ago' +%s) 

                echo && echo -en "\t默认开始时间 : "

                date -d "1970-01-01 $fetch_start_timestamp sec utc" '+%Y-%m-%d %H:%M:%S'

                break;        # 跳出开始时间的处理部分

        fi


        if [ ! -z "$fetch_start" ];then        # 如果输入的时间不为空

                fetch_start_timestamp=$(date -d "$fetch_start" +%s)        # 尝试把输入转换为时间戳

                if [ ! -z "$fetch_start_timestamp" ]; then        # 如果转换成功，则跳出开始时间处理部分

                        break;

                fi

        fi

# 如果输入不为空，且时间无效，则会重新提示输入

read -p "输入的时间无效，请重新输入 ：" fetch_start

done

# 下面提示用户输入结束时间

echo

read -p "结束时间 (YYYY-MM-DD HH:mm:ss）：" fetch_end

# 检查用户输入的时间是否有效，逻辑思路同上

while true ; do

        if [ -z "$fetch_end" ];then        # 如果输入为空，则默认的结束时间是当前

                fetch_end_timestamp=$(date +%s)

                echo && echo -en "\t默认截止时间: "

                date -d "1970-01-01 $fetch_end_timestamp sec utc" '+%Y-%m-%d %H:%M:%S'

                break        # 不并跳出结束时间处理部分

        fi

        if [ ! -z "$fetch_end" ];then         # 如果输入不为空

                fetch_end_timestamp=$(date -d "$fetch_end" +%s )         # 尝试转换为时间戳

                if [ ! -z "$fetch_end_timestamp"  ]; then        # 如果时间转换成功，则跳出结束时间处理部分

                        break;
                fi

        fi

read -p "输入的时间无效，请重新输入 ：" fetch_end

done

# 接下来检查 fetch_end 是否大于 fetch_start ，如果不是则报错

if (( fetch_end_timestamp <= fetch_start_timestamp )) ; then

        echo "ERROR:: 结束时间必须大于开始时间"

        exit 3

fi

# 接下来提示用户输入想要的统计类型（平均值、最大值、最小值、当前值）

cf_type_list="最大值 最小值 平均值 当前值"

PS3="
请选择一种类型 : "        # 修改默认的 select 提示字符串

echo && echo "请选择你想要的统计类型 : " && echo

select i in $cf_type_list ; do

        case $i in 

         "最大值") select_cf="MAX" ;break ;;
        
         "最小值") select_cf="MIN" ;break ;;

         "平均值") select_cf="AVERAGE" ;break ;;

         "当前值") select_cf="LAST" ;break ;;

         *) echo "无效选择"

        esac

done


# 接下来是选择合适的RRA,选择的根据是两方面 ：

###################################
#
# 第一是该 RRA 的 CF 必须等于用户指定的 CF
#
# 第二是该 RRA 的时间覆盖范围必须大于等于用户给出的范围
#
# 第三是该 RRA 的起始时间必须早于用户给出的起始时间
#
###################################


# 下面该行用于输出用户输入的起始/结束时间相差的时间范围（timestamp格式）

fetch_time_range=$((fetch_end_timestamp - fetch_start_timestamp))

# 下面开始按照上面的规则对每个 RRA 进行判断

for ((i=0;i<rrd_file_rra_num;i++)); do

        # 首先要判断当前 RRA 的 cf 类型是否符合用户的要求,如果不是，直接跳到下一个 RRA

        # 下面从 rra_type_array 数组中取出当前RRA 的 cf 类型，数组的 index 等于 RRA 的 index

        rra_type=$(echo ${rra_type_array[$i]})        

        if [ "$rra_type" != "$select_cf" ]; then        # 如果该 RRA 的 CF 和 用户选择（$select_cf）的不一样

                continue        # 则跳出该次循环，开始下一个 RRA 的测试

        fi

        # 如果当前 RRA 的 CF 符合用户选择的类新（ $select_cf ）则判断时间覆盖范围是否和上面的第2,3点

    # 下面从 time_ranges 数组中取出当前 RRA 的时间覆盖范围
         rra_time_range=$(echo ${time_range[$i]}) 

        # 下面首先判断当前 RRA 的时间覆盖范围是否大于用户的要求，如果不是则立即跳过该 RRA

        if (( $rra_time_range >= fetch_time_range ));then  # 如果当前 RRA 的时间覆盖范围大于等于用户指定的范围,就看是否满足第3点

                rra_first=$(rrdtool first --rraindex $i $rrd_file_name)        # 取出当前 RRA 的起始时间

                if (($fetch_start_timestamp >= $rra_first));then        # 如果当前 RRA 的起始时间早于用户指定的起始时间

                        rra_time_range_ok_list="$rra_time_range_ok_list RRA[$i]"  # 则把该 RRA 加入到合适的 RRA 的列表中

                fi

        fi


done        

# 到此已经对全部 RRA 进行了筛选了，但不保证一定至少有一个合适的 RRA 被选中。

if [ -z "$rra_time_range_ok_list" ];then        # 如果 ok 列表为空，说明没有一个合适的 RRA 

        echo && echo "对不起，此次操作没有符合指定要求的 RRA ，请检查所输入的要求是否合理"        

        exit 4        

else

        echo && echo "此次可供选择的 RRA 有 : " && echo        # 否则输出可供选择的 RRA 有那些

fi

# 下面开始提示用户从合适的 RRA 中选择一个

# 修改默认的 select 提示信息

PS3="
请选择一个 RRA : "

# 显示一个列表让用户选择

select select_rra in $rra_time_range_ok_list ; do

        if [ ! -z $select_rra ] ; then

                # 如果用户选择了一个 RRA ，则从 rra_res_array 数组中取出它的 resolution

                rra_chose=$(echo ${select_rra/'RRA['/})
                
                # 从 RRA 名称中得出该 RRA 的 index 编号，也就是 [ ] 中的数字
                rra_chose=$(echo ${rra_chose%']'})        

                # 从 rra_res_array 数组中取出被选择的 RRA 的解释度
                rra_res=$(echo ${rra_res_array[$rra_chose]})        

                #echo "该 RRA 的 resolution 是 : $rra_res"        # 并显示该值

                # 对起始时间进行取整运算，保证它刚好是指定的解释度的整数倍

                fetch_start_timestamp=$(((fetch_start_timestamp)/$rra_res*$rra_res-$rra_res))        
                
                # 对截止时间进行取证运算,保证它是指定的解释度的整数倍，这点是必须的，否则 90% 以上的机率不会得到你想要的结果

                fetch_end_timestamp=$(((fetch_end_timestamp)/$rra_res*$rra_res))        

                cmd="rrdtool fetch --start $fetch_start_timestamp --end $fetch_end_timestamp -r $rra_res $rrd_file_name $select_cf"

                # 询问是否需要保存文件,由用户输入目标位置。默认不保存。
                
                # 如果不保存，则输出到屏幕上。并对 fetch 的第一列进行处理，换成普通日期的格式，便于查看。

                echo && read -p  "是否把数据保存到文件? [Y/N] : " save_file

                if [ -z "$save_file" ] || ( [ "$save_file" != "Y" ] && [ "$save_file" != "y" ] ); then        

                        eval $cmd > $fetch_output        # 先把 fetch 的结果输出到临时文件

                        # 取出 fetch.out 的 标题部分，后面会用到

                        head -n 2 $fetch_output > $header_output

                        # 取出 fetch 结果的第一列（时间戳），送入变量 timestamp

                         timestamp=$(tail -n +3 $fetch_output |cut -d ':' -f 1 )

                        # 取出 fetch 结果中的数值部分，放入文件 /tmp/value.out         

                        tail -n +3 $fetch_output |cut -d ":" -f 2- |tr ' ' "\t" > $value_output 

                        # 对每个时间戳变换为具体时间，格式为 ‘年-月-日 小时:分:秒",结果写入 date.out 文件

                        for i in $timestamp; do

                                date -d "1970-01-01 $i sec utc" '+%Y-%m-%d %H:%M:%S' >> $date_output

                        done

                        # 把前面的 value.out 和现在的 date.out 合并成一个文件

                        paste $date_output $value_output > $paste_output

                        cat -n $header_output $paste_output |more


                else
                        eval $cmd > $final_output

                        chmod 600 $final_output

                        echo "结果保存于 $final_output"

                        ls -l $final_output

                fi        
                
                break;

        else

                echo "无效选择"         # 如果用户输入的 RRA 编号无效，则给出出错信息，重新选择

        fi        
done
        
# 下面删除临时文件

rm -f $temp_output $fetch_output $date_output $value_output $header_output $paste_output
