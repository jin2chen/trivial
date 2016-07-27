function bin2dex(s, max) {
	var a = [],
		b = [],
		t = 0,
		i, j, k, l;

	max = parseInt(max, 10);
	isNaN(max) && (max = 100);

	s = s.split('').reverse();
	for (i = 0; i < max; i++) {
		a[i] = b[i] = 0;
	}

	a[0] = 1;
	for (k = 0, l = s.length; k < l; k++) {
		if (s[k] === '1') {
			for (i = t; i < k; i++) {
				for (j = 0; j < max; j++) {
					a[j] = 2 * a[j];
				}
				t = k;
				for (j = 0; j < max; j++) {
					if (a[j] >= 10) {
						a[j] -= 10;
						a[j + 1] += 1;
					}
				}
			}
			for (j = 0; j < max; j++) {
				b[j] = b[j] + a[j];
				if (b[j] >= 10) {
					b[j] -= 10;
					b[j + 1] += 1;
				}
			}
		}
	}

	return b.reverse().join('').replace(/^0+/, '');
}
//console.log(bin2dex('11010001100101010001001110001011010001110111001110110101011001011010111111001110000110111101001101000100101101111010010010110110'));

function ipRange(from, to, max, step) {
	var f = String.fromCharCode,
		ip6byte = 16,
		ret = [],
		ip, i, mode,
		isIpv4 = function(ip) {
			return (/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/.test(ip)) && (RegExp.$1 < 256 && RegExp.$2 < 256 && RegExp.$3 < 256 && RegExp.$4 < 256);
		},
		isIpv6 = function(ip) {
			return ip.match(/:/g) && ip.match(/:/g).length <= 7 && /::/.test(ip) ? (/^([\da-f]{1,4}(:|::)){1,6}[\da-f]{1,4}$/i.test(ip) || /::[\da-f]{1,4}/i.test(ip)) : /^([\da-f]{1,4}:){7}[\da-f]{1,4}$/i.test(ip);
		},
		ipMode = function(ip) {
			if (isIpv4(ip)) {
				return 4;
			} else if (isIpv6(ip)) {
				return 6;
			}
		},
		pton = function(ip) {
			var r = /^((?:[\da-f]{1,4}(?::|)){0,8})(::)?((?:[\da-f]{1,4}(?::|)){0,8})$/,
				m, j, i, x, c;
			
			ip = ip.toLowerCase();
			m = ip.match(r);
			for (j = 1; j < 4; j++) {
				if (j === 2 || m[j].length === 0) {
					continue;
				}
				m[j] = m[j].split(':');
				for (i = 0; i < m[j].length; i++) {
					m[j][i] = parseInt(m[j][i], 16);
					m[j][i] = f(m[j][i] >> 8) + f(m[j][i] & 0xFF);
				}
				m[j] = m[j].join('');
			}
			x = m[1].length + m[3].length;
			if (x === ip6byte) {
				c = m[1] + m[3];
			} else {
				c = m[1] + (new Array(ip6byte - x + 1)).join('\x00') + m[3];
			}

			return c;
		},
		ipadd = function(ip, step) {
			var c = [], i;

			for (i = 0; i < ip6byte; i++) {
				c[i] = ip.charCodeAt(i);
			}

			for (i = ip6byte - 1; i >= 0; i--) {
				if (i === ip6byte - 1) {
					c[i] += step;
				}

				if (c[i] >= 256) {
					c[i] -= 256;
					c[i - 1] += 1;
				} else {
					break;
				}
			}

			for (i = 0; i < ip6byte; i++) {
				c[i] = f(c[i]);
			}

			return c.join('');
		},
		ntop = function(ip) {
			var c = [],
				m = '',
				i;
			
			for (i = 0; i < ip6byte; i++) {
				c.push(((ip.charCodeAt(i++) << 8) + ip.charCodeAt(i)).toString(16));
			}
			
			return c.join(':').replace(/((^|:)0(?=:|$))+:?/g, function (t) {
					m = (t.length > m.length) ? t : m;
					return t;
				}).replace(m || ' ', '::');
		},
		ip2long = function(ip) {
			ip = ip.match(/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/);

			return ip[1] * 16777216 + ip[2] * 65536 + ip[3] * 256 + ip[4] * 1;
		},
		long2ip = function(ip) {
			return Math.floor(ip / Math.pow(256, 3)) + '.' + Math.floor((ip % Math.pow(256, 3)) / Math.pow(256, 2)) + '.' + Math.floor(((ip % Math.pow(256, 3)) % Math.pow(256, 2)) / Math.pow(256, 1)) + '.' + Math.floor((((ip % Math.pow(256, 3)) % Math.pow(256, 2)) % Math.pow(256, 1)) / Math.pow(256, 0));
		};

	mode = ipMode(from);
	if (!mode) {
		return 1;
	}

	if (mode !== ipMode(to)) {
		return 2;
	}
	
	step = parseInt(step, 10);
	isNaN(step) && (step = 1);
	if (mode === 4) {
		from = ip2long(from);
		to = ip2long(to);

		if (from > to) {
			return 3;
		}

		ip = from;
		i = 0;
		while (ip <= to) {
			ret.push(long2ip(ip));
			ip += step;

			if (max && ++i >= max) {
				return 4;
			}
		}
	} else {
		from = pton(from);
		to = pton(to);

		if (from > to) {
			return 3;
		}

		ip = from;
		i = 0;
		while (ip <= to) {
			ret.push(ntop(ip));
			ip = ipadd(ip, step);
			if (max && ++i >= max) {
				return 4;
			}
		}
	}

	return ret;
}

//console.log(ipRange('::1:ffff:fffe', '::2:0:ff'));
//console.log(ipRange('127.0.0.1', '127.0.1.25'));