/**
 * SHOP CUSTOM
 *
 * @author     mole<mole1230@gmail.com>
 * @version    $Id: design.js 112 2011-04-08 06:44:11Z mole1230 $
 */
(function($) {
	$.log = function() {
		if (window.console && window.console.log) {
			window.console.log.apply(this, arguments);
		} else {
			var len = arguments.length;
			for (var i = 0; i < len; i++) {
				window.alert(arguments[i]);
			}
		}
	};

	// wait for prev request
	var __ajax = $.ajax;
	$.ajax = function(options) {
		if (options.element) {
			var $elm = $(options.element),
				_beforeSend = options.beforeSend,
				_complete = options.complete;
			options.beforeSend = function() {
				if ($.isFunction(_beforeSend) && _beforeSend.apply(this, arguments) === false) {
					if (options.noQueue !== true) {
						$.dequeue($.ajax, 'ajaxDown');
					}
					return false;
				}

				if ($elm.data('__AJAX-UNCOMPLETE__')) {
					if (options.noQueue !== true) {
						$.dequeue($.ajax, 'ajaxDown');
					}
					//TODO for warning
					return false;
				} else {
					$elm.data('__AJAX-UNCOMPLETE__', true);
				}
			};
			options.complete = function() {
				if ($.isFunction(_complete)) {
					_complete.apply(this, arguments);
				}

				$elm.data('__AJAX-UNCOMPLETE__', false);
			};
		}

		return __ajax(options);
	};

	// ajax queue
	var _ajax = $.ajax;
	$.ajax = function(options) {
		if (options.noQueue === true) {
			return $.ajax(options);
		}

		var _complete = options.complete;
		options.complete = function() {
			if ($.isFunction(_complete)) {
				_complete.apply(this, arguments);
			}

			$.dequeue($.ajax, 'ajaxDown');
		};
		$.queue($.ajax, 'ajax', function() {
			_ajax(options);
		});
		$.queue($.ajax, 'ajaxDown', function() {
			$.dequeue($.ajax, "ajax");
		});

		if ($.queue($.ajax, 'ajaxDown').length == 1) {
			$.dequeue($.ajax, 'ajax');
		}
	};
})(jQuery);

window.LANG = {
	'A00001': '成功',
	'A00010': '操作成功',
	
	'B00001': '失败',
	'B00010': '操作失败',

	'L00001': '添加',
	'L00002': '删除',
	'L00003': '编辑',
	'L00004': '上移',
	'L00005': '下移',
	'L00006': '展开',
	'L00007': '收起',
	'L00100': '编辑自定义模块',
	'L00101': '添加新模块',
	'L00102': '创建新模板',

	'M00001': '数据处理开始',
	'M00002': '数据处理中',
	'M00003': '数据处理失败',
	'M00100': '此模块不能被删除',
	'M00101': '确定要删除此模块吗？',
	'M00200': '确定要删除此模版吗？',
	'M00300': '模版名称为3-10个字符'
};

window.Page = {};
(function(Page, L, $) {
	var C = Page.C = {
		idPattern: /.+_(\d+)$/,
		moduleIdPrefix: 'module_',
		mainLocation: '/my/design/',
		pageLoation: '/my/design/?tid=',
		moduleDataApi: '/my/ajax/design/?action=getmodulehtml',
		pageDataSaveApi: '/my/ajax/design/?action=savetemplate',
		publishTemplateApi: '/my/ajax/design/?action=publishtemplate'
	};
	var Q = Page.Q = {
		modules: {},
		moduleThumbs: {}
	};
	Page.Widget = {
		Tbox: {}
	};
	Page.Frame = {};
	Page.Custom = {
		Suit: {},
		Module: {},
		Advance: {},
		Style: {},
		Page: {}
	};

	Page.init = function() {
		// Don't rearrange the order
		Page._iniJqo();
		Page._initAjaxGlobal();
		Page.Frame.init();
		Page.Custom.init();
		Page.setSerialize();
	};
	Page.setSerialize = function() {
		Q.customBody.data('__initSerialize__', Page.serialize(true));
	};
	Page.getSerialize = function() {
		return Q.customBody.data('__initSerialize__');
	};
	Page.serialize = function(noAttrs) {
		var d = {}, i, j, clen, ilen,
			$s = Q.sortable,
			s = $s.eq(0).sortable('option', 'items');

		d.template_id = parseInt(Q.contentBody.data('attrs'), 10);
		d.style_id = parseInt(Q.frameStyle.data('attrs'), 10);
		d.page_data = Q.pageContainer.data('attrs');
		d.banner = Q.bannerContainer.data('attrs');
		d.frame = Q.frameContainer.data('attrs');
		d.module_data = [];
		for (i = 0, clen = $s.length; i < clen; i++) {
			var $items = $s.eq(i).find(s),
				o = {};
			for (j = 0, ilen = $items.length; j < ilen; j++) {
				var $item = $items.eq(j),
					a = $item.data('attrs');
				o[a.id] = (noAttrs === true) ? 1 : a;
			}
			d.module_data.push(o);
		}

		return d;
	};
	Page._iniJqo = function() {
		Q.frameStyle = $('#frameStyle');
		Q.ajaxMsgBox = $('#ajaxMsgBox');
		Q.designMsgBox = $('#designMsgBox');
		Q.customBody = $('#customBody');
		Q.customMenu = $('#customMenu');
		Q.customContent = $('#customContent');
		Q.shrinkButton = $('#shrinkButton');
		Q.dressSuit = $('#dress_suit');
		Q.dressStyle = $('#dress_style');
		Q.dressModule = $('#dress_module');
		Q.dressPage = $('#dress_page');
		Q.contentBody = $('#contentBody');
		Q.bannerContainer = $('#bannerContainer');
		Q.pageContainer = $('#pageContainer');
		Q.bannerEdit = $('#bannerEdit');
		Q.frameContainer = $('#frameContainer');
	};
	Page._initAjaxGlobal = function() {
		Q.ajaxMsgBox.ajaxStart(function() {Page.Widget.ajaxMsgBox(L.M00001, false);})
			.ajaxSend(function() {Page.Widget.ajaxMsgBox(L.M00002, false);})
			.ajaxError(function() {Page.Widget.ajaxMsgBox(L.M00003, false);})
			.ajaxStop(function() {Page.Widget.ajaxMsgBox('', true);})
			.ajaxSuccess(function(e, j, o, s) {
				if ($.isPlainObject(s)) {
					if (s.message) {
						Page.Widget.ajaxMsgBox(s.message, false);
					}
				}
			});
	};
})(window.Page, window.LANG, jQuery);

// Page.Widget
(function(Page, L) {
	var wid = Page.Widget, ajaxMsgBoxTimer, msgTimer,
		C = Page.C,
		Q = Page.Q,
		$ = jQuery;

	wid.Tbox.close = function() {
		window.tb_remove();
	};
	wid.Tbox.open = function(t, a, g) {
		window.tb_show(t, a, g);
	};
	wid.ajaxMsgBox = function(msg, hide, delay) {
		if (msg) {
			Q.ajaxMsgBox.find('a').text(msg).end().show();
		}

		if (hide !== false) {
			try {
				clearTimeout(ajaxMsgBoxTimer);
			}
			catch(e) {}
			ajaxMsgBoxTimer = setTimeout(function() {Q.ajaxMsgBox.fadeOut('slow');}, delay || 5000);
		}

		return Q.ajaxMsgBox;
	};
	wid.msg = function(msg, delay) {
		if (msg) {
			try {
				clearTimeout(msgTimer);
			}
			catch(e) {}
			Q.designMsgBox.find('a').text(msg).end().show();
			msgTimer = setTimeout(function() {Q.designMsgBox.fadeOut('slow');}, delay || 5000);
		}

		return Q.designMsgBox;
	};
})(window.Page, window.LANG);

// Page.Frame
(function(Page, L) {
	var fra = Page.Frame,
		C = Page.C,
		Q = Page.Q,
		$ = jQuery;

	fra.init = function() {
		// init sortable
		var $cols =  Q.frameContainer.children(), i, j, clen, ilen, len;
		Q.sortable = $cols.sortable({
			tolerance: 'pointer',
			connectWith: $cols,
			distance: 10,
			beforeStart: function(e, u) {
				var hs = [], max;

				for (i = 0, len = $cols.length; i < len; i++) {
					hs.push($cols.eq(i).height());
				}

				max = Math.max.apply(Math, hs);

				for (i = 0, len = $cols.length; i < len; i++) {
					$cols.eq(i).height(max);
				}
			},
			receive: function(e, u) {
				var d = u.item.data('attrs');
				if (d.draggable == '1' && u.sender[0] != this) {
					u.sender.sortable('cancel');
				}
			},
			stop: function(e, u) {
				for (var i = 0, len = $cols.length; i < len; i++) {
					$cols.eq(i).height('auto');
				}

				u.item.mouseout();
			}
		}).disableSelection();

		if ($cols.length < 1) {
			return;
		}

		// init modules
		var itemSelector = $cols.eq(0).sortable('option', 'items');
		for (i = 0, clen = $cols.length; i < clen; i++) {
			var $items = $(itemSelector, $cols.eq(i));
			for (j = 0, ilen = $items.length; j < ilen; j++) {
				fra.initModule($items.eq(j));
			}
		}
	};
	fra.initModule = function($o) {
		if ($o.data('__inited__')) {
			return $o;
		}

		var jqo = {mask: {}},
			id = $o.data('attrs').id;

		jqo.mask.container = $o.children(':last');
		jqo.mask.background= jqo.mask.container.children(':first');

		// bind event
		$o.bind({
			mouseover: function(e) {
				var ow = $o.width(),
					oh = $o.height();

				jqo.mask.background.width(ow - 4).height(oh - 14);
				jqo.mask.container.css({top: 0 - oh + 'px'}).show();
			},
			mouseout: function(e) {
				jqo.mask.container.hide();
			},
			click: function(e) {
				var $t = $(e.target);
				switch ($t.attr('action')){
					case 'up':
						// move up
						if ($o.prev().length > 0) {
							$o.insertBefore($o.prev());
							$o.mouseout();
							$(document).scrollTo($o, {axis: 'y', duration: 800, offset: -100});
						}
						break;
					case 'down':
						// move down
						if ($o.next().length > 0) {
							$o.insertAfter($o.next());
							$o.mouseout();
							$(document).scrollTo($o, {axis: 'y', duration: 800, offset: -100});
						}
						break;
					case 'del':
						// delete
						var a = $o.data('attrs');
						if (a.disabled == '1') {
							window.alert(L.M00100);
						} else {
							$o.detach();
							Q.moduleThumbs[id].trigger('detach.shop');
						}
						break;
					case 'edit':
						// edit
						//TODO
						break;
					default:
						break;
				}
				return false;
			}
		});

		Q.modules[id] = $o;
		$o.data('__inited__', true);
		return $o;
	};
})(window.Page, window.LANG);

// Page.Custom
(function(Page, L) {
	var cus = Page.Custom,
		mod = Page.Custom.Module,
		pag = Page.Custom.Page,
		sty = Page.Custom.Style,
		sui = Page.Custom.Suit,
		wid = Page.Widget,
		C = Page.C,
		Q = Page.Q,
		$ = jQuery;
	var cc = {
		idbanner: '#idEditBanner'
	};

	cus.init = function() {
		cus.initTabs();
		cus.initShrink();
		cus.initBanner();
		sui.init();
		mod.init();
		pag.init();
		sty.init();
	};
	cus.initTabs = function() {
		// expires unit minute
		Q.customBody.tabs({cookie: {expires: 20}});
		Q.dressSuit.tabs({cookie: {expires: 20}});
		Q.dressModule.tabs({cookie: {expires: 20}});
	};
	cus.initShrink = function() {
		Q.shrinkButton.click(function(e) {
			if (Q.customContent.css('display') == 'none') {
				Q.customContent.slideDown('slow');
				Q.shrinkButton.removeClass('off').addClass('on');
				Q.shrinkButton.find('a').text(L.L00007);
			} else {
				Q.customContent.slideUp('slow');
				Q.shrinkButton.removeClass('on').addClass('off');
				Q.shrinkButton.find('a').text(L.L00006);
			}
			return false;
		});
	};
	cus.initBanner = function() {
		var mask = {};
		mask.container = Q.bannerEdit;
		mask.background= mask.container.children(':first');
		
		// bind event for mask
		Q.bannerContainer.bind({
			mouseover: function(e) {
				var ow = Q.bannerContainer.width(),
					oh = Q.bannerContainer.height();

				mask.background.width(ow - 4).height(oh - 4);
				mask.container.css({top: 0 - oh + 'px'}).show();
			},
			mouseout: function(e) {
				mask.container.hide();
			}
		});

		// bind envent for eidtor button
		$(cc.idbanner).click(function() {
			wid.Tbox.open(this.title, this.href);
			return false;
		});
	};
	cus.save = function() {
		var json = $.toJSON(Page.serialize());
		$.ajax({
			element: document,
			url: C.pageDataSaveApi,
			dataType: 'json',
			data: {json: json},
			type: 'post',
			success: function(json) {
				if (json.status == '0') {
					Page.setSerialize();
					window.location = C.pageLoation + json.data;
					return;
				}
			}
		});
	};
	cus.publish = function() {
		var json = $.toJSON(Page.serialize());
		$.ajax({
			element: document,
			url: C.publishTemplateApi,
			dataType: 'json',
			data: {json: json},
			type: 'post',
			success: function(json) {
				if (json.status == '0') {
					Page.setSerialize();
					window.location = C.pageLoation + json.data;
					return;
				}
			}
		});
	};
})(window.Page, window.LANG);

// Page.Custom.Suit
(function(Page, L) {
	var sui = Page.Custom.Suit,
		wid = Page.Widget,
		C = Page.C,
		Q = Page.Q,
		$ = jQuery;
	var cc = {
		blockSel1: '#dress_suit_custom',
		idSel1Prefix: '#suit_1_1_',
		idSel2Prefix: '#suit_1_2_',
		idSel3Prefix: '#suit_1_3_',
		idAddTemplate: '#suit_1_20',
		suit1TempeditApi: '/my/ajax/design/?action=tempedit',
		suit1TempdelApi: '/my/ajax/design/?action=tempdel',
		suit1Tempcopy: '/my/ajax/design/?action=tempcopy',
		suit1TempAdd: '/my/design/box/?height=150&width=340&action=addTemp&TB_iframe=true'
	};

	sui.init = function() {
		var $items = $('tbody > tr', cc.blockSel1), i, len, $item,
			tid = parseInt(Q.contentBody.data('attrs'), 10);

		for (i = 0, len = $items.length; i < len; i++) {
			$item = $items.eq(i);
			sui.initCustomThumb($item);
		}

		$(document).click(function(e) {
			var a = $(cc.blockSel1).data('__active__');
			if (a && a.id) {
				var id = a.id, $o = a.context;
				$(cc.idSel3Prefix + id, $o).blur();
				$(cc.idSel2Prefix + id, $o).hide();
				$(cc.idSel1Prefix + id, $o).show();
				$(cc.blockSel1).data('__active__', null);
			}
		});
		$(cc.idAddTemplate, cc.blockSel1).click(function(e) {
			wid.Tbox.open(L.L00102, cc.suit1TempAdd);
			return false;
		});
	};
	sui.initCustomThumb = function($o) {
		if ($o.data('__inited__')) {
			return $o;
		}
		var id = $o.attr('id').replace(C.idPattern, "$1");

		$o.bind({
			click: function(e) {
				var $t = $(e.target),
					a = $t.attr('action');

				switch (a){
					case 'show':
						var ac = $(cc.blockSel1).data('__active__');
						if (ac && ac.id && id != ac.id) {
							$(document).trigger('click');
						}
						$(cc.idSel1Prefix + id, $o).hide();
						$(cc.idSel2Prefix + id, $o).show();
						$(cc.idSel3Prefix + id, $o).data('__oldValue__', $(cc.idSel1Prefix + id, $o).text()).focus();
						$(cc.blockSel1).data('__active__', {id: id, context: $o});
						break;
					case 'save':
						var cc1 = $(cc.idSel1Prefix + id, $o),
							cc2 = $(cc.idSel2Prefix + id, $o),
							cc3 = $(cc.idSel3Prefix + id, $o),
							val = cc3.val();
						if (val.length < 3 || val.length > 10) {
							wid.msg(L.M00300);
						} else if (cc3.data('__oldValue__') === cc3.val()) {
							$(document).trigger('click');
						} else {
							$.ajax({
								element: $t,
								url: cc.suit1TempeditApi,
								dataType: 'json',
								data: {tid: id, tname: val},
								success: function(json) {
									if (json.status == '0') {
										cc1.text(val);
										$(document).trigger('click');
									}
								}
							});
						}
						break;
					case 'del':
						if (window.confirm(L.M00200)) {
							$.ajax({
								element: $t,
								url: cc.suit1TempdelApi,
								dataType: 'json',
								data: {tid: id},
								success: function(json) {
									if (json.status == '0') {
										if (Q.contentBody.data('attrs') == id) {
											window.location = C.mainLocation;
										} else {
											$o.remove();
										}
									}
								}
							});
						}
						break;
					case 'copy':
						$.ajax({
							element: $t,
							url: cc.suit1Tempcopy,
							dataType: 'json',
							data: {tid: id},
							success: function(json) {
								if (json.status == '0') {
									window.location = C.pageLoation + json.data;
								}
							}
						});
						break;
					case 'edit':
						window.location = C.pageLoation + id;
						break;
					case 'cancel':
						// cancel button
						// use event bubble up
						return true;
					case 'prevent':
						// prevent input event bubble up
						break;
					default:
						return true;
				}

				return false;
			}
		});

		$o.data('__inited__', true);
		return $o;
	};
})(window.Page, window.LANG);

// Page.Custom.Module
(function(Page, L) {
	var mod = Page.Custom.Module,
		wid = Page.Widget,
		fra = Page.Frame,
		C = Page.C,
		Q = Page.Q,
		$ = jQuery;
	var cc = {
		thumbTpl: '<li data-attrs=\'{"id":"${id}"}\'><div><em>${name}</em><i class="i1"><a class="f_5e6c89" href="javascript:;" action="edit">\u7f16\u8f91</a></i><i><a class="cc0000" href="javascript:;" action="del">\u5220\u9664</a></i><input type="checkbox" class="input1" action="toggle"></div></li>',
		module1EditApi: '/my/design/box/?height=450&width=510&action=editCustomModule&id={id}&TB_iframe=true',
		module1AddApi: '/my/design/box/?height=450&width=510&action=addCustomModule&TB_iframe=true',
		module1DelApi: '/my/ajax/Design/?action=delCustomModule',
		idSys: '#dress_module_sys',
		idCustom: '#dress_module_custom',
		idCustomList: '#dress_module_custom_list',
		idAddModule: '#module_1_20'
	};

	mod.init = function() {
		var $items, i, len;
		$items = $('li', cc.idSys).add('li', cc.idCustom);

		for (i = 0, len = $items.length; i < len; i++) {
			mod.initThumb($items.eq(i));
		}

		// bind add button event
		$(cc.idAddModule).click(function(e) {
			wid.Tbox.open(L.L00101, cc.module1AddApi);
			return false;
		});
	};
	mod.initThumb = function($o) {
		if ($o.data('__inited__')) {
			return $o;
		}

		var d = $o.data('attrs'), id = d.id,
			$c = $o.find('input:checkbox');
		Q.moduleThumbs[d.id] = $o;
		if ($('#' + C.moduleIdPrefix + id, Q.frameContainer).length > 0) {
			$c.attr('checked', true);
		} else {
			$c.attr('checked', false);
		}

		$o.bind({
			click: function(e) {
				var $t = $(e.target);
				switch ($t.attr('action')) {
					case 'toggle':
						mod.toggleModule($o);
						e.stopPropagation();
						return true;
					case 'edit':
						wid.Tbox.open(L.L00100, cc.module1EditApi.replace(/\{id\}/, id));
						break;
					case 'del':
						if (!window.confirm(L.M00101)) {
							return false;
						}
						$.ajax({
							element: $o,
							url: cc.module1DelApi,
							//dataType: 'json',
							data: {id: id},
							type: 'post',
							success: function(json) {//TODO unify ajax response
								if (json == 'ok') {
									if (Q.modules[id]) {
										Q.modules[id].remove();
										delete Q.modules[id];
									}

									Q.moduleThumbs[id].remove();
									delete Q.moduleThumbs[id];
									wid.ajaxMsgBox(L.A00010);
									return;
								}

								wid.ajaxMsgBox(L.B00010);
							}
						});
						break;
					default:
						break;
				}

				return false;
			},
			'detach.shop': function(e) {
				$c.attr('checked', false);
			},
			'prepend.shop': function(e) {
				$c.attr('checked', true);
			}
		});

		$o.data('__inited__', true);
		return $o;
	};
	mod.addThumb = function(json) {
		var tpl = $.template(cc.thumbTpl).apply(json),
			$o = $(tpl);
		mod.initThumb($o.appendTo(cc.idCustomList));

		return $o;
	};
	mod.updateThumb = function(json) {
		var id = json.id, has = false,
			tpl = $.template(cc.thumbTpl).apply(json),
			$o = $(tpl), $n;
		
		// remove old
		if (Q.modules[id]) {
			Q.modules[id].remove();
			delete Q.modules[id];
			has = true;
		}
		
		$n = Q.moduleThumbs[id].next();
		Q.moduleThumbs[id].remove();
		delete Q.moduleThumbs[id];

		// add new
		if ($n.length > 0) {
			$o.insertBefore($n);
		} else {
			$o.appendTo(cc.idCustomList);
		}
		mod.initThumb($o);
		if (has) {
			$o.find('input:checkbox').click();
		}
	};
	mod.toggleModule = function($o) {
		var d = $o.data('attrs'), id = d.id, c = 0,
			$m = $('#' + C.moduleIdPrefix + id, Q.frameContainer);

		// function
		function find(s, f) {
			var a = (s + '').split(''), m;

			if (f) {
				m = Math.max.apply(Math, a);
			} else {
				m = Math.min.apply(Math, a);
			}

			for (var i = 0, len = a.length; i < len; i++) {
				if (m == a[i]) {
					break;
				}
			}

			return i;
		}

		if ($m.length > 0) {
			$m.detach();
			$o.trigger('detach.shop');
		} else {
			if (Q.modules[id]) {
				$m = Q.modules[id];
				c = parseInt(($m.data('attrs').position || 0), 10) ? find(Q.frameContainer.data('attrs'), true)
					: find(Q.frameContainer.data('attrs'), false);
				$m.prependTo(Q.sortable.eq(c));
				$o.trigger('prepend.shop').effect('transfer', {to: $m, duration: 500});
			} else {
				$.ajax({
					element: $o,
					url: C.moduleDataApi,
					dataType: 'json',
					data: {id: id},
					success: function(json) {
						if (json.status != '0') {
							Q.moduleThumbs[id].attr('checked', false);
							return;
						}
						$m = $(json.data);
						c = parseInt(($m.data('attrs').position || 0), 10) ? find(Q.frameContainer.data('attrs'), true)
							: find(Q.frameContainer.data('attrs'), false);
						fra.initModule($m).prependTo(Q.sortable.eq(c));
						$o.trigger('prepend.shop').effect('transfer', {to: $m, duration: 500});
					}
				});
			}
		}

		return;
	};
})(window.Page, window.LANG);

// Page.Custom.Page
(function(Page, L) {
	var pag = Page.Custom.Page,
		C = Page.C,
		Q = Page.Q,
		$ = jQuery;

	pag.init = function() {

	};
})(window.Page, window.LANG);

// Page.Custom.Style
(function(Page, L) {
	var sty = Page.Custom.Style,
		C = Page.C,
		Q = Page.Q,
		$ = jQuery;

	var cc = {
		idPreview: '#dress_style_preview'
	};

	sty.init = function() {
		var $items = $('li.color', Q.dressStyle), i, len, $item;

		for (i = 0, len = $items.length; i < len; i++) {
			$item = $items.eq(i);
			sty.initThumb($item);
			if ($item.data('attrs').id == Q.frameStyle.data('attrs')) {
				$(cc.idPreview).attr('src', $item.data('attrs').thumb);
			}
		}
	};
	sty.initThumb = function($o) {
		if ($o.data('__inited__')) {
			return $o;
		}

		$o.bind({
			click: function(e) {
				var $l = Q.frameStyle,
					d = $o.data('attrs');

				if ($l.data('attrs') == d.id) {
					return;
				}
				$(cc.idPreview).attr('src', d.thumb);
				$l.attr('href', $l.data('href').replace(/\{id\}/, d.id));
				$l.data('attrs', d.id);
			}
		});

		$o.data('__inited__', true);
		return $o;
	};
})(window.Page, window.LANG);

jQuery(function($) {
	window.Page.init();
	window.onbeforeunload = function() {
		// $.active fixed for ie
		// ajax trigger onbeforeunload event
		if ($.active > 0 || $.toJSON(window.Page.serialize(true)) == $.toJSON(window.Page.getSerialize())) {
			return;
		}

		return '';
	};
});