//author: 沙加 darkangle.cnblogs.com QQ 5364241
//初始化选项
var insertNewLine = false; //在各个项目之间插入空行.
//程序开始.
var input = "";
while (!WScript.StdIn.AtEndOfStream) {
	input += WScript.StdIn.ReadAll();
}
var output = input.replace(/ {2,}|[\f\n\r\t\v]+/g, "");
output = output.replace(/(\*\/|})/g, '$1\r\n');
output = output.replace(/( )?(:|}|{|;)( )?/g, '$2');
if (insertNewLine) output = output.replace(/(\*\/|})/g, '$1\r\n');
WScript.StdOut.Write(output);