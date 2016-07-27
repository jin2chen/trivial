/*!
 * jQuery extention.
 */
(function($, undefined) {

// Test debug.
$.debug = (function() {
	var scripts = document.getElementsByTagName('script'),
		script = scripts[scripts.length - 1];
	return !(/\.min\.js$/.test(script.getAttribute('src')));
})();

// Extend console some function to jQuery.
$.each(['log', 'trace', 'error', 'warn'], function(i, name) {
	if ($.debug && window.console && window.console[name]) {
		if ($.browser.mozilla || $.browser.msie) {
			$[name] = window.console[name];
		} else if ($.browser.webkit) {
			$[name] = $.proxy(window.console[name], window.console);
		} else {
			$[name] = $.noop;
		}
	} else {
		$[name] = $.noop;
	}
});

// Add php function.
$.php = $.php || {};
(function() {
	var php = new window.PHP_JS(),
		rdashAlpha = /_([a-z]|[0-9])/ig,
		fcamelCase = function(all, letter) {
			return (letter + '').toUpperCase();
		},
		key;

	for (key in php) {
		if ($.isFunction(php[key]) && key !== 'constructor') {
			$.php[key.replace(rdashAlpha, fcamelCase)] = php[key];
		}
	}
})();

// Add ax special function.
$.ax = $.ax || {};
$.ax.CONST = $.ax.CONST || {};

$.extend($.ax.CONST, {
	STATUS_PENDING: 0,
	STATUS_ALERT_FAILURE_PENDING: -2,
	STATUS_ALERT_FAILURE: -1,
	STATUS_ALERT_SUCCESS: 1,
	STATUS_ALERT_SUCCESS_PENDING: 2,
	STATUS_TEMPLATE: 3,
	STATUS_RELOAD: 4,
	STATUS_REDIERCT: 5,
	VMODE_NORMAL: 'v-normal',
	VMODE_PORTLET: 'v-portlet',
	VMODE_XFORM_DEPEND_SAVE: 'v-xform-depend-save',
	VMODE_GRID_SUB_LIST: 'v-grid-sub-list',
	SCENARIO_INSERT: 'insert',
	SCENARIO_UPDATE: 'update',
	PEM_ANY: 0,
    PEM_SHOW: 1,
    PEM_READ: 2,
    PEM_READ_WRITE: 4,
    PEM_ALL: 4
});

$.extend($.ax, {
	t: function(message) {
		return message;
	},

	asset: function(url) {
		// @todo Remove the global variables.
		return '/skin/' + window.global_theme + '/' + window.global_skin + '/' + url;
	},

	msg: function(msg, delay, method) {
		var self = $.ax;

		self.msg.element = self.msg.element || $('<div class="gmsg"></div>').axMsg({classPrefix: 'gmsg-'}).prependTo('body');
		self.msg.element.axMsg(method, msg, delay);
	},

	success: function(msg, delay) {
		var self = $.ax;
		self.msg(msg, delay, 'success');
	},

	error: function(msg, delay) {
		var self = $.ax;
		self.msg(msg, delay, 'error');
	},

	info: function(msg, delay) {
		var self = $.ax;
		self.msg(msg, delay, 'info');
	},

	failure: function(msg, delay) {
		var self = $.ax;
		self.msg(msg, delay, 'error');
	},

	close: function() {
		var self = $.ax;
		self.msg('', 0, 'close');
	},

	clearUrl: function(url) {
		return (url.match(/^([^#]+)/)||[])[1];
	},

	format: $.validator && $.validator.format || $.noop,

	string: function() {
		var i, len, ret = '';

		for (i = 0, len = arguments.length; i < len; i++) {
			if (arguments[i] !== undefined && arguments[i] !== null) {
				ret = arguments[i];
				break;
			}
		}

		if (typeof ret === 'boolean') {
			ret = ret ? '1' : '';
		} else if ($.isNumeric(ret)) {
			ret += '';
		}

		return ret;
	},

	rebuildUrl: function(url, params, remove) {
		var path, query, fragment,
			pos, tmp, i, len,
			pair = {};

		path = query = fragment = '';
		if ((pos = url.indexOf('?')) !== -1) {
			path = url.substring(0, pos);
			query = url.substr(pos + 1);
			if ((pos = query.lastIndexOf('#')) !== -1) {
				tmp = query;
				query = tmp.substring(0, pos);
				fragment = tmp.substr(pos + 1);
			}
		} else {
			path = url;
		}

		if (!!query) {
			$.php.parseStr(query, pair);

			if (typeof remove === 'string') {
				remove = remove.split(/\s+/);
			}

			if ($.isArray(remove)) {
				for (i = 0, len = remove.length; i < len; i++) {
					delete pair[remove[i]];
				}
			}
		}

		if (!$.isPlainObject(params)) {
			params = null;
		}
		query = $.php.httpBuildQuery($.extend(true, pair, params || {}));
		query = !!query ? ('?' + query) : '';
		fragment = !!fragment ? ('#' + fragment) : '';

		return path + query + fragment;
	},

	addCssRules: function(cssString) {
		var self = $.ax.addCssRules,
			cssText, heads;

		if (!self.style) {
			self.style = document.createElement('style');
			self.style.setAttribute('type', 'text/css');
			heads = document.getElementsByTagName('head');
			if (heads.length) {
				heads[0].appendChild(self.style);
			} else {
				document.documentElement.appendChild(self.style);
			}
		}

		if (self.style.styleSheet) {// IE
			self.style.styleSheet.cssText += cssString;
		} else {// W3C
			cssText = document.createTextNode(cssString);
			self.style.appendChild(cssText);
		}
	},

	callback: function(namespace, handler, defaults) {
		var self = $.ax.callback,
			key, keys = [], ret;

		self.callbacks = self.callbacks || {};
		if (typeof namespace === 'string') {
			if ($.isFunction(handler)) {
				self.callbacks[namespace] = {
					defaults: defaults || {},
					handler: handler
				};

				ret = self.callbacks;
			} else if (typeof handler === 'object') {
				self.callbacks[namespace] = handler;
				ret = self.callbacks;
			} else {
				ret = self.callbacks[namespace];
			}
		} else if (typeof namespace === 'object') {
			$.extend(self.callbacks, namespace);
			ret = self.callbacks;
		} else if (typeof namespace === 'boolean') {
			for (key in self.callbacks) {
				if (self.callbacks.hasOwnProperty(key)) {
					keys.push(key);
				}
			}
			ret = keys.join('\n');
		} else {
			ret = self.callbacks;
		}

		return ret;
	},

	isIpv4: function(ip) {
		return (/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/.test(ip)) && (RegExp.$1 < 256 && RegExp.$2 < 256 && RegExp.$3 < 256 && RegExp.$4 < 256);
	},

	isIpv6: function(ip) {
		return ip.match(/:/g) && ip.match(/:/g).length <= 7 && /::/.test(ip) ? (/^([\da-f]{1,4}(:|::)){1,6}[\da-f]{1,4}$/i.test(ip) || /::[\da-f]{1,4}/i.test(ip)) : /^([\da-f]{1,4}:){7}[\da-f]{1,4}$/i.test(ip);
	},

	ipMode: function(ip) {
		var self = $.ax;

		if (self.isIpv4(ip)) {
			return 4;
		} else if (self.isIpv6(ip)) {
			return 6;
		}

		return 0;
	},

	ipRange: function (from, to, max, step) {
		var self = $.ax,
			f = String.fromCharCode,
			ip6byte = 16,
			mode = self.ipMode(from),
			ret = [],
			ip, i,
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

		if (!mode) {
			return 1;
		}

		if (mode !== self.ipMode(to)) {
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
	},

	dialog: function(id, options) {
		var self = $.ax,
			$el = $('#' + id),
			promise = {};

		self.dialog.list = self.dialog.list || {};
		self.dialog.list[id] = {};
		$.each(options.buttons || {}, function(name, props) {
			self.dialog.list[id][name] = $.Callbacks('once memory stopOnFalse');
			promise[name] = self.dialog.list[id][name].add;
			if (!props.click) {
				self.dialog.list[id][name].add(function() {
					$(this).axDialog('close');
				});
			} else {
				self.dialog.list[id][name].add(props.click);
			}
		});

		if (!$el.length) {
			$.each(options.buttons || {}, function(name, props) {
				options.buttons[name].click = function(e) {
					self.dialog.list[id][name].fireWith(this);
					e.stopPropagation();
					e.preventDefault();
				};
			});
			$el = $('<div id="' + id + '"></div>')
				.appendTo('body')
				.axDialog(options);
		}

		promise.element = $el;
		return promise;
	},

	confirm: function(message) {
		var self = $.ax,
			promise = self.dialog('JQ-AX-CONFIRM', {
				modal: true,
				title: 'Come from page message',
				autoOpen: false,
				resizable: false,
				width: 480,
				minWidth: 480,
				minHeight: 180,
				buttons: {
					confirm: {text: 'Confirm'},
					cancel: {text: 'Cancel'}
				}
			});

		promise.element.html(message || 'Please give a message.').axDialog('open');
		return promise;
	},

	alert: function(message) {
		var $el = $('#JQ-AX-ALERT');

		if (!$el.length) {
			$el = $('<div id="JQ-AX-ALERT"></div>').appendTo('body');
			$el.axDialog({
				modal: true,
				title: 'Come from page message',
				autoOpen: false,
				resizable: false,
				minWidth: 480,
				minHeight: 180,
				buttons: [
					{
						text: 'Confirm',
						click: function(e) {
							$(this).axDialog('close');
							e.stopPropagation();
							e.preventDefault();
						}
					}
				]
			});
		}

		$el.html(message || 'Please give a message.').axDialog('open');
	},
	
	tidyButtons: function(buttons, predefine) {
		var i, l, button, res = [];
		
		for (i = 0, l = buttons.length; i < l; ++i) {
			button = buttons[i];
			if (button.indexOf('<') > -1) {
				res.push(button);
			} else {
				if (predefine[button]) {
					res.push(predefine[button]);
				} else {
					$.warn('Not defined button "' + button + '".');
				}
			}
		}
		
		return res;
	}
});

// Exend jQuery.fn
$.extend($.fn, {
	axAjaxSubmit: function(options) {
		var form;
		
		form = this.axIsValid(true);
		if (form !== false) {
			// using jquery.form submit the form.
			if (!form.is('form')) {
				options.semantic = true;
			}
			
			options.type = options.type || 'post';
			form.ajaxSubmit(options);
		}
		
		return this;
	},
	
	axSerializeJSON: function() {
		var arr = this.serializeArray(),
			i, l, data = {};
		
		for (i = 0, l = arr.length; i < l; ++i) {
			data[arr[i].name] = arr[i].value;
		}
		
		return $.toJSON(data);
	},
	
	axIsValid: function(pvt) {
		var $el, form,
			isValid = true;
		
		if (this.is('form')) {
			form = this;
			isValid = form.valid();
		} else if (($el = this.find('form:first')) && $el.length) {
			form = $el;
			isValid = form.valid();
		} else if (($el = this.closest('form')) && $el.length) {
			form = this;
			isValid = this.find(':input').not(':button').not(':hidden').filter('[name]').valid();
		} else {
			form = this;
			isValid = true;
		}
		
		if (!isValid) {
			form.validate().focusInvalid();
		}
		
		if (pvt && isValid) {
			return form;
		}
		
		return !!isValid;
	}
});

// Extend jQuery.Widget.
$.extend($.Widget.prototype, {
	_getCreateOptions: function() {
		return (this.element.data('config') || {})[this.widgetName];
	}
});

// Extract to window
// Deprecate
$.extend(window, {
	asset: $.ax.asset,
	t: $.ax.t
});

// Ajax global settings.
$.ajaxPrefilter(function(options, originalOptions, jqXhr) {
	var doneList = $.Callbacks('once memory stopOnFalse'),
		failList = $.Callbacks('once memory stopOnFalse'),
		$el, count;

	jqXhr.success = doneList.add;
	jqXhr.error = failList.add;

	jqXhr.done(function(data, status, jqXhr) {
		if (typeof data === 'object') {
			if (data) {
				if (data.info && data.info !== '') {
					$.log(data.info);
				}

				status = parseInt(data.status, 10);
				data = data.data;
			} else {
				$.error('Ajax returning data is empty');
				return false;
			}
		}

		try {
			doneList.fireWith(this, [data, status, jqXhr]);
		} catch (e) {
			$.error(e);
		}

	});

	jqXhr.fail(function() {
		try {
			failList.fireWith(this, arguments);
		} catch (e) {
			$.error(e);
		}

	});

	jqXhr.error(function(jqXhr, status, error) {
		if (status === 'parsererror') {
			$.ax.error('Response parse error.');
			$.error(error);
			return false;
		}
	});

	jqXhr.success(function(json, status, error) {
		if (status === $.ax.CONST.STATUS_ALERT_FAILURE) {
			$.isArray(json) && (json.length === 0) && (json = '');
			$.ax.error(json);
			return false;
		}
		if (status === $.ax.CONST.STATUS_ALERT_SUCCESS) {
			$.isArray(json) && (json.length === 0) && (json = '');
			$.ax.success(json);
			return false;
		}
	});

	if (options.element) {
		$el = $(options.element);
		count = $el.data('JQ_AJAX_COUNT') || 0;

		if (count > 0) {
			jqXhr.abort();
		} else {
			$el.data('JQ_AJAX_COUNT', ++count);
			jqXhr.complete(function() {
				$el.data('JQ_AJAX_COUNT', --count);
			});
		}
	}
});

$.ajaxSetup({
	dataType: 'json',
	statusCode: {
		'400': function(jqXhr, status, error) {
			$.ax.error('Params error.');
		},
		'403': function(jqXhr, status, error) {
			$.ax.error('Forbidden.');
		},
		'404': function(jqXhr, status, error) {
			$.ax.error('Page is not found');
		},
		'500': function(jqXhr, status, error) {
			$.ax.error('Server internal error.');
		},
		'401': function(jqXhr, status, error) {
			var id = 'JQ-LOGIN-FORM',
				$login = $('#' + id);

			if (!$login.length) {
				$login = $('<div id="' + id + '"></div>');
				$login.appendTo('body').hide();

				$login.axDialog({
					modal: true,
					autoOpen: false,
					href: '/login',
					width: 630,
					height: 200,
					title: 'Please Login',
					resizable: false,
					buttons: [
						{
							text: 'Login',
							click: function(e) {
								$login.find('form').axAjaxSubmit({
									dataType: 'json',
									resetForm: true,
									success: function() {
										$login.axDialog('close');
									}
								});
								e.stopPropagation();
								e.preventDefault();
							}
						},
						'cancel'
					]
				})
				.bind('keydown', function(e) {
					if (e.keyCode === $.ui.keyCode.ENTER) {
						$(this).parent().find(':button:first').click();
					}
				});
			}

			$login.axDialog('open');
		}
	}
});

})(jQuery);
