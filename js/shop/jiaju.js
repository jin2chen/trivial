/**
 * 站点全局JS
 *
 * @author     mole <mole1230@gmail.com>
 * @version    $Id: jiaju.js 124 2011-06-24 08:38:32Z mole1230 $
 */
(function(window) {
	var document = window.document,
		alert = window.alert,
		confirm = window.confirm,
		$ = window.jQuery;
	var JJ = {
		Config: {},
		Widget: {},
		App: {},
		Static: {}
	};

	// JS 动态加载
	JJ.loadJs = function(sid, callback, dequeue) {
		JJ.loadJs.packages = JJ.loadJs.packages || {
			'jquery.thickbox': {
				'js': ['/js/jquery/jquery.thickbox.js'],
				'check': function() {
					return !!window.tb_show;
				}
			},
			'jquery.select': {
				'js': ['/js/jquery/jquery.select.js'],
				'check': function() {
					return !!$.fn.relateSelect;
				}
			},
			'data.city': {
				'js': ['/js/data/district.js'],
				'depends': ['jquery.select'],
				'check': function() {
					return !!window.DISTRICT;
				}
			},
			'data.category': {
				'js': ['/cate/category/'],
				'depends': ['jquery.select'],
				'check': function() {
					return !!window.CATEGORY;
				}
			}
		};

		if (!dequeue) {
			$(window).queue('loadJs', function() {
				JJ.loadJs(sid, callback, true);
			});
			$(window).queue('loadJsDone', function(){
				$(window).dequeue('loadJs');
			});
			if ($(window).queue('loadJsDone').length == 1) {
				$(window).dequeue('loadJs');
			}
			return;
		}

		function collect(sid) {
			var jsCollect =[], packages = JJ.loadJs.packages[sid], i, l;
			if (packages) {
				if (packages.depends) {
					l = packages.depends.length;
					for (i = 0; i < l; i++) {
						jsCollect = jsCollect.concat(collect(packages.depends[i]));
					}
				}

				if ($.isFunction(packages.check) && !packages.check()) {
					jsCollect = jsCollect.concat(packages.js);
				}
			}

			return jsCollect;
		}

		function load(url) {
			return jQuery.ajax({
				crossDomain: true,
				cache: true,
				type: "GET",
				url: url,
				dataType: "script",
				scriptCharset: "UTF-8"
			});
		}

		var js = collect(sid), deferreds = [], l = js.length, i;
		for (i = 0; i < l; i++) {
			deferreds.push(load(js[i]));
		}
		$.when.apply($, deferreds).then(function() {
			$(window).dequeue('loadJsDone');
			$.isFunction(callback) && callback.call(document);
		}, function() {
			$(window).dequeue('loadJsDone');
		});
	};

	// 提示语转换
	JJ.t = function(code) {
		if (window.MSG && window.MSG[code]) {
			return window.MSG[code];
		}

		return code;
	};

	// 字符串计数
	// 一个汉字当成两个字符
	JJ.strCount = function(str) {
		var byteLen = 0, strLen  = str.length, i;
		if (strLen) {
			for (i = 0; i < strLen; i++) {
				if(str.charCodeAt(i) > 255) {
					byteLen += 2;
				} else {
					byteLen += 1;	
				}
			}
		}
		return byteLen;
	};

	JJ.Widget = {
		// 下拉菜单显隐
		pop: function(s) {
			if ($(s).data('JJ_BIND_POP')) {
				return;
			}

			var $c = $(s),
				setting = $c.data('pop') || {};
			$c.bind({
				mouseover: function(e) {
					if (setting.pop) {
						$(setting.pop, $c).show();
					}
					if (setting.icon && setting.iconClass) {
						$(setting.icon, $c).addClass(setting.iconClass);
					}
				},
				mouseout: function(e) {
					if (setting.pop) {
						$(setting.pop, $c).hide();
					}
					if (setting.icon && setting.iconClass) {
						$(setting.icon, $c).removeClass(setting.iconClass);
					}
				}
			});

			$c.data('JJ_BIND_POP', true);
			$c.triggerHandler('mouseover');
			return;
		},

		// 打开 thickbox 遮罩层
		tbOpen: function(caption, url, imageGroup) {
			function show() {
				window.tb_show(caption, url, imageGroup);
			}

			JJ.loadJs('jquery.thickbox', show);
		},

		// 关闭 thickbox 遮罩层
		tbClose: function() {
			window.tb_remove();
		},

		// 用户登陆层
		login: function(backurl, reload) {
			var url;
			backurl = (typeof(backurl) === 'undefined' || !backurl) ? window.location.href : backurl;
			reload = (typeof(reload) === 'undefined') ? true : reload;
			url = '/login/ajax/?' + ($.param({url : backurl, reload: Number(reload)})) + '&TB_iframe&height=228&width=402';
			JJ.Widget.tbOpen('<strong>新浪家居商城</strong>', url, 'scrolling=no');
		},

		// 分类联动
		category: function(s, options) {
			function relateSelect() {
				var defaults = {
					data: window.CATEGORY
				};
				$(s).relateSelect($.extend(defaults, options || {}));
			}

			JJ.loadJs('data.category', relateSelect);
		},

		// 省市联动
		city: function(s, options) {
			function relateSelect() {
				var defaults = {
					data: window.DISTRICT
				};
				$(s).relateSelect($.extend(defaults, options || {}));
			}

			JJ.loadJs('data.city', relateSelect);
		}
	};

	JJ.App = {
		topSearch: function(s) {
			if ($(s).data('JJ_BIND_FOCUS')) {
				return;
			}

			var $e = $(s);
			$e.bind({
				'focusin': function(e) {
					$e.removeClass('search_goods');
				},
				'focusout': function(e) {
					if ($.trim($e.val()) === '') {
						$e.addClass('search_goods');
					}
				}
			});
			$e.data('JJ_BIND_FOCUS', true);
			$e.triggerHandler('focusin');
			return;
		}
	};

	window.JJ = JJ;
}(window));