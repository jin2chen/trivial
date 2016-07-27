function isIPv6(ip) {
	var patrn = /^([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}$/i,
		r = patrn.exec(ip),
		cLength = function(str) {
			var reg = /([0-9a-f]{1,4}:)|(:[0-9a-f]{1,4})/gi,
				temp = str.replace(reg, ' ');
			return temp.length;
		};

	//CDCD:910A:2222:5498:8475:1111:3900:2020
	if (r) return true;

	if (ip == '::') return true;

	//F:F:F::1:1 F:F:F:F:F::1 F::F:F:F:F:1格式
	patrn = /^(([0-9a-f]{1,4}:){0,6})((:[0-9a-f]{1,4}){0,6})$/i;
	r = patrn.exec(ip);
	if (r) {
		var c = cLength(ip);
		if (c <= 7 && c > 0) return true;
	}

	//F:F:10F::
	patrn = /^([0-9a-f]{1,4}:){1,7}:$/i;
	r = patrn.exec(ip);
	if (r) return true;

	//::F:F:10F
	patrn = /^:(:[0-9a-f]{1,4}){1,7}$/i;
	r = patrn.exec(ip);
	if (r) return true;

	//F:0:0:0:0:0:10.0.0.1格式
	patrn = /^([0-9a-f]{1,4}:){6}(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/i;
	r = patrn.exec(ip);
	if(r && r[2] <= 255 && r[3] <= 255 &&r[4] <= 255 && r[5] <= 255) return true;

	//F::10.0.0.1格式
	patrn = /^([0-9a-f]{1,4}:){1,5}:(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/i;
	r = patrn.exec(ip);
	if (r && r[2] <= 255 && r[3] <= 255 && r[4]<= 255 && r[5] <= 255) return true;

	//::10.0.0.1格式
	patrn = /^::(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/i;
	r = patrn.exec(ip);
	if(r && r[1] <= 255 && r[2] <= 255 && r[3] <= 255 && r[4] <= 255) return true;

	return false;
}