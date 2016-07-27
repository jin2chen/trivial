<?php

/**
 * 数据库差异对比工具
 * 
 * @author 朱华<54zhua@gmail.com>
 * @copyright www.joy999.com
 * @version v1.0
 */

?><html>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>数据库结构异同对比器</title>
<body>
<form method="POST">
<h3>源数据库信息（以此为母版）：</h3>
主机：<input type="text" name="host1" value="<?php echo $_REQUEST['host1']?>"><br>
用户名：<input type="text" name="user1" value="<?php echo $_REQUEST['user1']?>"><br>
密码：<input type="password" name="pwd1" value="<?php echo $_REQUEST['pwd1']?>"><br>
库名：<input type="text" name="db1" value="<?php echo $_REQUEST['db1']?>"><br>
<h3>目标数据库信息（待更新的）：</h3>
主机：<input type="text" name="host2" value="<?php echo $_REQUEST['host2']?>"><br>
用户名：<input type="text" name="user2" value="<?php echo $_REQUEST['user2']?>"><br>
密码：<input type="password" name="pwd2" value="<?php echo $_REQUEST['pwd2']?>"><br>
库名：<input type="text" name="db2" value="<?php echo $_REQUEST['db2']?>"><br>
<input type="submit" value="提交">
<input type="reset" value="重置">
<p><input type="checkbox" name="exec" value="1" <?php echo ( $_REQUEST['exec'] == 1 ) ? 'checked' : '' ?> >执行SQL更新操作</p>
</form>


<?php
/**
 * 数据库结构检查
 * 
 */

if ( ! isset( $_REQUEST['host1'] ) || ! isset( $_REQUEST['host2'] ) ) {
	echo '</form></body></html>';
    exit;
}

echo '<hr>';

set_time_limit( 0 );
ignore_user_abort( true );
ob_implicit_flush( true );

$link1 = mysql_connect(  $_REQUEST['host1'] , $_REQUEST['user1'] , $_REQUEST['pwd1'] ) 
			or die( '数据库1 连接失败！');
$link2 = mysql_connect( $_REQUEST['host2'] , $_REQUEST['user2'] , $_REQUEST['pwd2'] )
			or die( '数据库2 连接失败！');

//mysql_select_db( $_REQUEST['db1'] , $link1 )
//			or die( '数据库1 选择失败！');
//mysql_select_db( $_REQUEST['db2'] , $link2 )
//			or die( '数据库2 选择失败！');
$ts = array();
$tStruct = array();
$tCreate = array();
$tIndex = array();
foreach( array( $_REQUEST['db1'] => $link1 , $_REQUEST['db2'] => $link2 ) as $db => $link ) { 
	mysql_select_db( $db , $link )
		or die( '数据库：' . $db . '选择失败！');
 
    mysql_query( 'set names "utf8";' , $link )
    	or die( $db . ' set names "utf8" 执行失败！');
    
    //查询表结构
    $m = mysql_query( 'show tables;' , $link )
    	or die ( $db . 'show tables 执行失败！');
    
    
    while( $r = mysql_fetch_array( $m ) ) {
        $tn = $r[0];
        
        $rm = mysql_query( 'show full columns from `' . $tn . '`' , $link );
        while( $v = mysql_fetch_assoc( $rm ) ) {
            $ts[$db][$tn][] = $v['Field'];
            $tStruct[$db][$tn][$v['Field']] = $v;
        }
        if ( $db == $_REQUEST['db1'] ) {
	        $rm = mysql_query( 'show create table `' . $tn . '`' , $link );
	        $v = mysql_fetch_array( $rm );
	        $tCreate[$v[0]] = $v[1] ;
        }
        
        //查询表结构
        $rm = mysql_query( 'SHOW INDEX FROM `' . $tn . '`' , $link );
        $t = array();
        while( $v = mysql_fetch_assoc( $rm ) ) {        	 
        	if ( $v['Key_name'] == 'PRIMARY' ) {
        		//主键
        		$t['key'][$v['Key_name']][] = $v['Column_name'];
        		continue;
        	}
        	if ( $v['Non_unique'] == 1 ) {
        		//索引
        		if ( $v['Index_type'] == 'FULLTEXT' ) {
        			//全文索引
        			$t['fulltext'][$v['Key_name']][] = $v['Column_name'];
        		} else {
        			//一般索引
        			$t['index'][$v['Key_name']][] = $v['Column_name'];
        		}
        	} else {
        		//唯一
        		$t['uni'][$v['Key_name']][] = $v['Column_name'];
        	}
        }
//        if ( $tn == 'access_log') {
//        	var_dump( $t );
//        }
        foreach( $t as $type => $ta ) {
	        foreach( $ta as $kn => $ka ) {
	        	sort( $ka );
//	        	var_dump( $ka );
	        	$tmp = implode( '`,`' , $ka );
	        	if ( ! empty( $tmp ) ) {
	        		$tmp = '`' . $tmp . '`' ;
	        	}
	        	$ta[$kn] = $tmp;
	        }
	        $t[$type] = $ta;
        }
        $tIndex[$db][$tn] = $t;
    }
}

$sql = array();
$msg = array();

if ( is_array( $ts[$_REQUEST['db1']] ) ) foreach( $ts[$_REQUEST['db1']] as $tn => $tbs ) {
    if ( ! isset( $ts[$_REQUEST['db2']][$tn] ) ) {
        //创建新表       
        $sql[] = $tCreate[$tn];
    }
    $last = '首';
    foreach( $tbs as $v ) {
        if ( ! isset( $ts[$_REQUEST['db2']][$tn] ) ) {
             $msg[$tn][] = '新添（表添加） ' . $v . ' 于 ' . $last . ' 之后';
             $last = $v;
             continue;
        }
        
        $fieldUpdateMethod = '' ;
        if ( ! in_array( $v , $ts[$_REQUEST['db2']][$tn] ) ) {
            //新添字段
            $msg[$tn][] = '新添 ' . $v . ' 于 ' . $last . ' 之后';
            $fieldUpdateMethod = 'ADD';
        } else {
            //更新字段
            
            if ( $tStruct[$_REQUEST['db2']][$tn][$v] != $tStruct[$_REQUEST['db1']][$tn][$v] ) {
            	
                $msg[$tn][] = '更新 ' . $v . ' 于 ' . $last . ' 之后';
                $fieldUpdateMethod = 'CHANGE `' . $v . '` ' ;
            }
        }
        
        if ( $fieldUpdateMethod ) {
            $T = $tStruct[$_REQUEST['db1']][$tn];
            $tt = $T[$v];
            
            $tSql = 'alter table `' . $tn .'` ' . $fieldUpdateMethod . ' `' . $v .'` ' . $tt['Type'] . ' ';
            if ( $tt['Null'] == 'NO' ) {
                $tSql .= ' NOT NULL ';
            }
            
            $tSql .= ' ' . $tt['Extra'] . ' ';
           
            if ( $tt['Default'] != '' ) {
            	if ( $tt['Default'] == 'CURRENT_TIMESTAMP' ) {
            		$tSql .= ' default CURRENT_TIMESTAMP ';
            	} else {
                	$tSql .= ' default \'' . addslashes( $tt['Default'] ) . '\' ';
            	}
            }
            $tSql .= ' COMMENT \'' . mysql_escape_string( $tt['Comment'] ) .'\' ';
            if ( $last == '首' ) {
                $tSql .= ' FIRST ';
            } else {
                if ( in_array( $last , $ts[$_REQUEST['db2']][$tn] ) ) {
                    $tSql .= ' AFTER `' . $last . '`';
                }
            }
            
            //由最后进行统一的索引更新，所以此处将注释掉
            
            if ( ! empty( $tt['Extra'] ) ) {
	            
	            if ( $tt['Key'] == 'PRI' ) {
	                //主键,需要先放弃之前的主键，再重新创建
	                $tSql .= ' , DROP PRIMARY KEY ';
	                //查询当前的所有主键的字段名
	                $tSql .= ' , ADD PRIMARY KEY ( ';
	                foreach( $T as $tName => $vv ) {
	                    $priKey = array();
	                    if ( $vv['Key'] == 'PRI' ) {
	                        $priKey[] = '`' . $tName . '`';
	                    }
	                    $tSql .= implode( ',' , $priKey );
	                }
	                $tSql .= ' ) ';
	            }
	            
	            
	            if ( $tt['Key'] == 'MUL' ) {
	                //索引
	                $tSql .= ' , ADD INDEX ( ' . $v . ' )';
	            }
	            
	            if ( $tt['Key'] == 'UNI' ) {
	                //索一
	                $tSql .= ' , ADD UNIQUE ( ' . $v . ' )';
	            }
            }
            $sql[] = $tSql;
        }
        $last = $v;
    }
   
}

if ( is_array( $ts[$_REQUEST['db2']] ) ) foreach( $ts[$_REQUEST['db2']] as $tn => $tbs ) {    
    foreach( $tbs as $v ) {
        
        if ( ! in_array( $v , $ts[$_REQUEST['db1']][$tn] ) ) {
            $msg[$tn][] = '删除 ' . $v;
            $sql[] = 'alter table `' . $tn . '` drop column `' . $v . '`';      
        }
    } 
}

$typeMap = array(
	'key' => '主键' ,
	'index' => '索引' ,
	'uni' => '唯一' ,
	'fulltext' => '全文索引' ,
);

//这里开始进行索引比对
echo '<hr><h2>数据库的索引对比</h2>';
$indexMsg = array();
foreach( $tIndex[$_REQUEST['db2']] as $tableName => $indexs ) {	
	$str = '';
	foreach( $indexs as $type => $arr ) {
		foreach( $arr as $iName => $iVal ) {
			if ( isset( $tIndex[$_REQUEST['db1']][$tableName][$type][$iName] ) ) {
				$srcVal = $tIndex[$_REQUEST['db1']][$tableName][$type][$iName];
				if ( $srcVal == $iVal ) {
					//相等，跳过
					continue;
				} else {
					//不相等，重新生成
					$msg[$tableName][] = '<li>' . $typeMap[$type] .' [' . $iName .' : ' . $iVal . '] 异同';
					
					//删除原来的索引
					switch( $type ) {
						case 'key' :
							$sql[] = 'ALTER TABLE `'. $tableName . '` DROP PRIMARY KEY ';
							$sql[] = 'ALTER TABLE `' . $tableName . '` ADD PRIMARY KEY ( ' . $srcVal . ' )';
							break;
						case 'index' :
							$sql[] = 'ALTER TABLE `'. $tableName . '` DROP INDEX `' . $iName .'`';
							$sql[] = 'ALTER TABLE `' . $tableName . '` ADD INDEX `' . $iName .'` ( ' . $srcVal . ' )';
							break;
						case 'uni' :
							$sql[] = 'ALTER TABLE `'. $tableName . '` DROP INDEX `' . $iName .'`';
							$sql[] = 'ALTER TABLE `' . $tableName . '` ADD UNIQUE `' . $iName .'` ( ' . $srcVal . ' )';
							break;
						case 'fulltext' :
							$sql[] = 'ALTER TABLE `'. $tableName . '` DROP INDEX `' . $iName .'`';
							$sql[] = 'ALTER TABLE `' . $tableName . '` ADD FULLTEXT `' . $iName .'` ( ' . $srcVal . ' )';
							break;
					}
					
				}
			} else {
				//不存在此键，删除
				$msg[$tableName][] = '<li>'. $typeMap[$type] .' [' . $iName .' : ' . $iVal . '] 需要删除';
				switch( $type ) {
					case 'key' :
						$sql[] = 'ALTER TABLE `'. $tableName . '` DROP PRIMARY KEY ';						
						break;
					case 'index' :
					case 'uni' :
					case 'fulltext' :
						$sql[] = 'ALTER TABLE `'. $tableName . '` DROP INDEX `' . $iName .'`';						
						break;					
				}
			}
		}
	}
	
}

foreach( $tIndex[$_REQUEST['db1']] as $tableName => $indexs ) {		
	foreach( $indexs as $type => $arr ) {
		foreach( $arr as $iName => $iVal ) {
			if ( isset( $tIndex[$_REQUEST['db2']][$tableName][$type][$iName] ) ) {
				
			} else {
				//不存在此键，需要添加
				$msg[$tableName][] = '<li>'. $typeMap[$type] .' [' . $iName .' : ' . $iVal . '] 需要添加';
				switch( $type ) {
					case 'key' :						
						$sql[] = 'ALTER TABLE `' . $tableName . '` ADD PRIMARY KEY ( ' . $iVal . ' )';
						break;
					case 'index' :
						$sql[] = 'ALTER TABLE `' . $tableName . '` ADD INDEX `' . $iName .'` ( ' . $iVal . ' )';
						break;
					case 'uni' :
						$sql[] = 'ALTER TABLE `' . $tableName . '` ADD UNIQUE `' . $iName .'` ( ' . $iVal . ' )';
						break;
					case 'fulltext' :
						$sql[] = 'ALTER TABLE `' . $tableName . '` ADD FULLTEXT `' . $iName .'` ( ' . $iVal . ' )';
						break;
				}
			}
		}
	}
	
}


//索引比对结束


foreach( $msg as $tn => $arr ) {
    echo '<hr><p>表名：' . $tn . '</p><ul>';
    if ( !empty( $arr ) ) foreach( $arr as $v ) {
        echo '<li>' . $v . '</li>';
    } else {
        echo '<li>没有更新</li>';
    }
    echo '</ul>';
}

if ( ! empty( $sql ) ) {

echo '<textarea style="width:100%;height:500px">';

$result = '';
foreach( $sql as $s ) {
    echo htmlspecialchars( $s . ";\n\n\n" );
//    if ( $_REQUEST['exec'] == 1 ) {
//    	echo '<p><b>执行：</b>' . $s . ' ' .  ( mysql_query( $s , $link2 ) ? '<font color=green>成功</font>' : '<font color=red>失败</font>' );
//    	$result .= "</p>\r\n";
//    }
}
echo '</textarea>';
if ( $_REQUEST['exec'] == 1 ) {
	$totalSqls = count( $sql );
	foreach( $sql as $i => $s ) {
		echo '===========================完成度：' . ( $i + 1 ) . ' / ' . $totalSqls .'=================================<br />';
    	echo '<p><b>执行：</b>' . $s . ' ' .  ( mysql_query( $s , $link2 ) ? '<font color=green>成功</font>' : '<font color=red>失败</font>' );
    	echo "</p>\r\n";
    }
}

?>
<table><tr><td>
<form method="POST">
<input type="hidden" name="exec" value="1">
<input type="hidden" name="host1" value="<?php echo $_REQUEST['host1']?>"><br>
<input type="hidden" name="user1" value="<?php echo $_REQUEST['user1']?>"><br>
<input type="hidden" name="pwd1" value="<?php echo $_REQUEST['pwd1']?>"><br>
<input type="hidden" name="db1" value="<?php echo $_REQUEST['db1']?>"><br>
<input type="hidden" name="host2" value="<?php echo $_REQUEST['host2']?>"><br>
<input type="hidden" name="user2" value="<?php echo $_REQUEST['user2']?>"><br>
<input type="hidden" name="pwd2" value="<?php echo $_REQUEST['pwd2']?>"><br>
<input type="hidden" name="db2" value="<?php echo $_REQUEST['db2']?>"><br>
<input type="submit" value="执行SQL以便更新表结构">
</form>
</td><td>
<form method="POST">
<input type="hidden" name="host1" value="<?php echo $_REQUEST['host1']?>"><br>
<input type="hidden" name="user1" value="<?php echo $_REQUEST['user1']?>"><br>
<input type="hidden" name="pwd1" value="<?php echo $_REQUEST['pwd1']?>"><br>
<input type="hidden" name="db1" value="<?php echo $_REQUEST['db1']?>"><br>
<input type="hidden" name="host2" value="<?php echo $_REQUEST['host2']?>"><br>
<input type="hidden" name="user2" value="<?php echo $_REQUEST['user2']?>"><br>
<input type="hidden" name="pwd2" value="<?php echo $_REQUEST['pwd2']?>"><br>
<input type="hidden" name="db2" value="<?php echo $_REQUEST['db2']?>"><br>
<input type="submit" value="再次查询（不执行SQL）">
</form>
</td></tr></table>
<?php 
} else {
	echo '<font color=green>数据库之间没有差异！</font>';
}

mysql_close( $link1 );
mysql_close( $link2 );

?>
</body>
</html>