function rechange(oresult) {
	return oresult.replace(/document.writeln\("/g, "").replace(/"\);/g, "").replace(/\\\"/g, "\"").replace(/\\\'/g, "\'").replace(/\\\//g, "\/").replace(/\\\\/g, "\\")
}
function change(osource) {
	return "document.writeln(\"" + osource.replace(/\\/g, "\\\\").replace(/\//g, "\\/").replace(/\'/g, "\\\'").replace(/\"/g, "\\\"").split('\r\n').join("\");\ndocument.writeln(\"") + "\");"
} 
//³ÌÐò¿ªÊ¼
var input = "";
while (!WScript.StdIn.AtEndOfStream) {
	input += WScript.StdIn.ReadAll();
}
var formated_code = '';
if (input) {
	formated_code = WScript.Arguments.length ? rechange(input) : change(input);
}
formated_code.length?WScript.Echo(formated_code):WScript.Echo('Are you sure your input is javascript source file?');
