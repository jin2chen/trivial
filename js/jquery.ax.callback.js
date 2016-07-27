/*!
 * For all element data-callback.
 *
 * Depends:
 *  jquery.ui.widget.js
 */
(function($, undefined) {

var rfunction = /^\s*function\s*\(/i;

$.widget('ui.axCallback', {
	options: {
		eventTypes: 'click',
		disabled: 'ui-state-disabled',
		owner: {}
	},

	_create: function() {
		var self = this,
			o = this.options;

		self.uiOwner = {};

		self._bindEvents();
		self.owner(o.owner);
	},

	_bindEvents: function() {
		var self = this,
			o = this.options,
			types, i, len,
			handler = function(e) {
				var $this = $(this),
					type = e.type,
					config = $this.data('callbacks') || {},
					isInit = $this.data('callbacks.isInit'),
					args, props, tmp;

				if (typeof config === 'string') {
					tmp = config;
					config = {};

					if (rfunction.test(tmp)) {
						config['click'] = {
							handler: tmp,
							options: {}
						};
					} else {
						config['click'] = tmp;
					}
				}
				if (!config[type]) {
					$.error(__('No handler is defined for ') + type);
					return;
				}

				props = config[type];
				if (!props.IS_TIDIED) {
					if (typeof props === 'string' || $.isFunction(props)) {
						props = {handler: props, options: {}};
					}

					if (!props.handler) {
						$.error(__('No handler is defined'));
						return false;
					}

					if (typeof props.handler === 'string') {
						if (rfunction.test(props.handler)) {
							try {
								props.handler = (new Function('return ' + props.handler))();
							} catch (e) {
								$.error(__('Function parse error'));
								return false;
							}
						} else {
							tmp = $.ax.callback(props.handler);
							if (!tmp) {
								$.error(props.handler + __(' is not exist'));
								return false;
							}
							props.handler = tmp.handler;
							props.options = $.extend(true, {}, tmp.defaults, props.options);
						}
					}

					if (!$.isFunction(props.handler)) {
						$.error(__('Callback is not a function'));
						return false;
					}

					props.IS_TIDIED = true;
					config[type] = props;
					$this.data('callbacks', config);
				}

				args = [e, props.options, self];
				if (!isInit) {
					(tmp && $.isFunction(tmp.init)) && tmp.init.apply(this, args);
					$this.data('callbacks.isInit', true);
				}

				if ($this.hasClass(o.disabled)) {
					return false;
				} else {
					return props.handler.apply(this, args);
				}
			};

		types = o.eventTypes.split(/\s+/);
		for (i = 0, len = types.length; i < len; ++i) {
			this.element.on(types[i] + '.' + this.widgetName, '[data-callbacks]', handler);
		}
	},

	owner: function(owner) {
		if (owner === undefined) {
			return this.uiOwner;
		}

		this.uiOwner = owner;
	}

});

})(jQuery);

/*!
 * <code>
 * // Get all callbacks name
 * $.ax.callback(true);
 * </code>
 */
(function($, undefined) {

var
Methods = {
	sConfirm: function(e, options, ui) {
		var $this = $(this),
			owner = ui.owner() || {},
			ajax = $.extend({}, options.ajax),
			done = function() {
				Methods._ajaxDone(ajax, owner);
			};
			
		ajax.url = ajax.url || $this.attr('href');
		if ($.isFunction(owner.collect)) {
			ajax.data = $.extend(ajax.data, owner.collect());
		}
		if (options.isConfirm) {
			$.ax.confirm(options.tip).confirm(done);
		} else {
			done();
		}

		return false;
	},
	
	gConfirm: function(e, options, ui) {
		var $this = $(this),
			owner = ui.owner() || {},
			ajax = $.extend({}, options.ajax),
			done = function() {
				Methods._ajaxDone(ajax, owner);
			};

		if (!$.isFunction(owner.collect)) {
			$.error(owner.widgetName + __('.collect must be implement'));
			return false;
		}
		if (!owner.collect(true)) {
			$.ax.alert('Not selected');
			return false;
		}
		
		ajax.url = ajax.url || $this.attr('href');
		ajax.data = $.extend(ajax.data, owner.collect());
		$.ax.confirm(options.tip).confirm(done);
		return false;
	},
	
	_ajaxDone: function(ajax, owner) {
		$.ajax(ajax).success(function(json, status, jqXhr) {
			switch (status) {
				case $.ax.CONST.STATUS_TEMPLATE:
					owner.replace(json);
					break;
				case $.ax.CONST.STATUS_RELOAD:
					owner.reload(json);
					break;
				default:
					//$.log('Nothing to do.');
					break;
			}
		});
	},
	
	gDialog: function(e, options, ui) {
		var owner = ui.owner(),
			$wrapper = $('<div/>'), 
			data = options.isGetKeys ? owner.collect(): null;

		if (!$.isFunction(owner.collect)) {
			$.error(owner.widgetName + __('.collect must be implement'));
			return false;
		}

		if (!owner.collect(true)) {
			$.ax.alert(__('Not selected'));
			return false;
		}

		$wrapper.axDialog({
			modal: true,
			href: {
				url: this.href,
				type: 'get',
				data: data
			},
			buttons: ['save', 'remove'],
			open: function() {
				$(this).find('form').axForm({isInnerForm: true}).axHideFormHeader();
			}
		})
		.bind('save', function(e) {
			var $this = $(this),
				$form = $this.find('form'),
				o, _success;

			_success = options.ajax.success;
			options.ajax.success = function(json, status, jqXhr) {
				$.isFunction(_success) && _success.apply(this, [json, status, jqXhr, {form: $form}]);
				$this.remove();
			};

			o = $.extend(options.ajax, {
				data: owner.collect()
			});

			$form.axAjaxSubmit(o);
		});
		
		return false;
	}
},
i18n = {
	sTip: {
		'delete': __('Are you sure to delete?'),
		'clear': __('Are you sure to clear?'),
		'enable': __('Are you sure to enable?'),
		'disable': __('Are you sure to disable?')
	},
	gTip: {
		'delete': __('Are you sure to delete all the selected items?'),
		'clear': __('Are you sure to clear statistics of all the selected items?'),
		'enable': __('Are you sure to enable all the selected items?'),
		'disable': __('Are you sure to disable all the selected items?')
	}
};

$.each([
	'System.grid.popup.delete', 
	'System.grid.popup.enable',
	'System.grid.popup.disable',
	'System.grid.popup.clear' 
	], function(i, name) {
	var action = name.replace(/.+\.([^.]+)/, '$1');
	$.ax.callback(name, {
		defaults: {
			tip: i18n.sTip[action],
			isConfirm: true,
			ajax: {}
		},
		handler: Methods.sConfirm
	});
});

$.each([
	'System.grid.operate.delete', 
	'System.grid.operate.enable',
	'System.grid.operate.disable',
	'System.grid.operate.clear'
	], function(i, name) {
	var action = name.replace(/.+\.([^.]+)/, '$1');
	$.ax.callback(name, {
		defaults: {
			tip: i18n.gTip[action],
			ajax: {type: 'post'}
		},
		handler: Methods.gConfirm
	});
});

$.ax.callback('System.grid.operate.group', {
	defaults: {
		tpl: '',
		isGetKeys: false,
		ajax: {}
	},
	handler: function(e, options, ui) {
		var $this = $(this);

		options.ajax = options.ajax || {};
		options.ajax.success = function(json, status, jqXhr) {
			var $ul = $this.siblings('ul'),
				name = json,
				href = $.ax.rebuildUrl($ul.find('li:first > a').attr('href'), {group: name}),
				tpl = options.tpl.replace(/\{name\}/g, name).replace(/\{href\}/, href);

			$ul.find('a:contains(' + name + ')').length || $ul.append(tpl);
		};
		
		Methods.gDialog.apply(this, [e, options, ui]);
		$.isFunction($.ax.dropdown) && $.ax.dropdown();
		return false;
	}
});

$.ax.callback('System.grid.operate.group-delete', {
	defaults: {},
	handler: function(e, options, ui) {
		var $target = $(e.target),
			href = $target.attr('href');
		
		if ($target.hasClass('delete')) {
			$.ax.confirm(__('Are you sure to delete?'))
			.confirm(function() {
				$.ajax({
					url: href
				}).success(function(json, status, jqXhr) {
					$target.closest('li').remove();
				});
			});
			
			return false;
		}
	}
});

$.ax.callback('System.grid.operate.filter', {
	defaults: {
	},
	handler: function(e, options, ui) {
		$(this).axFilter({
			grid: ui.owner()
		});
		return false;
	}
});

// download function
function download(url) 
{
	if (typeof (download.iframe) == "undefined") {
		var iframe = document.createElement("iframe");
		download.iframe = iframe;
		document.body.appendChild(download.iframe);
	}
	download.iframe.src = url;
	download.iframe.style.display = "none";
}

$.ax.callback('System.download', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var owner = ui.owner(), self = this,
			url = self.href;
		url += '&keys=' + owner.collect()['keys'];
		download(url);
		return false;
	}
});

$.ax.callback('System.grid.refresh', {
	defaults: {
		interval: 0
	},
	handler: function(e, options, ui) {
		var $target = $(e.target),
			owner = ui.owner(),
			href = $target.attr('href'),
			$i, interval;

		if (!$target.is('a') || !href) {
			return false;
		}

		interval = parseInt(href.replace(/^\s*#/, ''), 10);

		if (interval) {
			$i = $target.siblings('.ui-icon-check');

			if ($i.is(':visible')) {
				owner.refresh(0);
				$i.hide();
			} else {
				owner.refresh(interval);
				$target.closest('ul').find('.ui-icon-check').hide();
				$i.show();
			}
		} else {
			$target.parent().find('.ui-icon-check').hide();
			owner.refresh(0);
		}

		$.isFunction($.ax.dropdown) && $.ax.dropdown();
		return false;
	}
});

$.ax.callback('System.clear', {
	
	defaults: {
		tip: __('Are you sure to clear all statistics?'),
		isConfirm: true,
		ajax: {}
	},
	handler: function(e, options, ui) {
		var $this = $(this),
		owner = ui.owner() || {},
		ajax = $.extend({}, options.ajax),
		done = function() {
			Methods._ajaxDone(ajax, owner);
		};
	
		ajax.url = ajax.url || $this.attr('href');
		$.ax.confirm(options.tip).confirm(done);
		return false;
	}
});

$.ax.callback('System.changeStatus', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var $this = $(this),
			$target = $(e.target);

		if (!$target.is('a')) {
			return false;
		}

		$this.prevAll('em').html($target.text());
		try {
			$this.prevAll('input:first').val($target.attr('href').replace(/^\s*#/, ''));
			$target.siblings('i').removeClass('hide').closest('li').siblings().find('i').addClass('hide');
		} catch (e) {
		}
	}
});

$.ax.callback('System.changeByRemoteStatus', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var $this = $(this),
			$target = $(e.target);

		if (!$target.is('a')) {
			return false;
		}

		$.get($target.attr('href'), function(json, status, jqXhr, data) {
			if (status === $.ax.CONST.STATUS_PENDING) {
				$this.prevAll('.dropdown-toggle').find('span').html($target.text());
				if ($this.hasClass('single')) {
					$target.siblings('i').removeClass('hide').closest('li').siblings().find('i').addClass('hide');
				} else {
					$target.siblings('i').toggleClass('hide');
				}
			}
			$this.slideUp();
		});

		return false;
	}
});



$.ax.callback('System.messageDropdown', {
	defaults: {
	},
	handler: function(e, options, ui) {
		if ( ! $( e.target ).is('a') ) return false;
	}
});

$.ax.callback('System.changePartition', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var $this = $(this),
			$target = $(e.target);

		if (!$target.is('a')) {
			return false;
		} else if($target.hasClass('create')) {
			return true;
		}

		$.get($target.attr('href'), function(json, status, jqXhr, data) {
			if (status === $.ax.CONST.STATUS_ALERT_SUCCESS_PENDING) {
				AX.router.href( location.href );
				$target.closest('li').siblings('li').find('i').insertAfter($target);
				$this.siblings('span').find('span').text($target.text());
			}
			$this.fadeOutUp();
		});

		return false;
	}
});

// main menu action
$.ax.callback('System.menu.action', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var $target = $(e.target);

		var promise = $.ax.dialog('JQ-AX-ACTION-MODE', {
			modal: true,
			title: options.title,
			autoOpen: false,
			resizable: false,
			width: 480,
			minWidth: 480,
			minHeight: 180,
			position: {
				my: 'center',
				at: 'center'
			},
			buttons: {
				save_reload: {
					text: __('Save & Continue'), 
					click: function(e) {
						var $el = $target;
						if ( ! $target.is('a') ) {
							$el = $target.parent();
						} else if($target.hasClass('create')) {
							return true;
						}
						
						var postData = {'save': 1}; 

						$.get($el.attr('href'), postData, function(json, status, jqXhr, data) {
							if (status === $.ax.CONST.STATUS_ALERT_SUCCESS_PENDING) {
								AX.widgets.shutdown.show();
							}
						});
						$(this).axDialog('close');
					}
				},
				not_save_reload: {
					text: __('Don\'t Save & Continue'),
					click: function(e) {
						var $el = $target;
						if (!$target.is('a')) {
							$el = $target.parent();
						} else if($target.hasClass('create')) {
							return true;
						}

						var postData = {'save': 0}; 
						
						$.get($el.attr('href'), postData, function(json, status, jqXhr, data) {
							if (status === $.ax.CONST.STATUS_ALERT_SUCCESS_PENDING) {
								AX.widgets.shutdown.show();
							}
						});                     
						$(this).axDialog('close');
					}
				},
				cancel: {
					text: __('Cancel'), 
					click: function(e) {
						$(this).axDialog('close');
					}
				}
			}
		});
		promise.element.html(options.message).axDialog('open');

		//$target.axPopout('hide');
		return false;
	}
});

// main menu popup wizard 
$.ax.callback('System.menu.wizard', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var $hash = location.hash ? location.hash : 'slb.http';
		
		get_wizard($hash);
		return false;
	}
});

$.ax.callback('System.changeOnlineStatus', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var $this = $(this),
			$target = $(e.target);

		if (!$target.is('a')) {
			return false;
		}

		$.get($target.attr('href'), function(json, status, jqXhr, data) {
			if (status === $.ax.CONST.STATUS_PENDING) {
				$target.siblings('.ui-icon-check').toggleClass('hide');

				if ($this.find('.hide').length === $this.find('li').length) {
					$this.prevAll('.dropdown-toggle').find('span').text('Standby');
				} else {
					$this.prevAll('.dropdown-toggle').find('span').text('Active');
				}

				//$.ax.success('Change Group Status Success');
			}
			$this.slideUp();
		});

		return false;
	}
});



$.ax.callback('System.note.create', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var $target = $(e.target), 
			$form = $target.closest('form');
			
			$form.axAjaxSubmit({
				success: function(json, status, jqXhr) {
					if (status === $.ax.CONST.STATUS_PENDING) {
						$form.siblings('.note-list').append(json);
					}               
				}
			});

		return false;
	}
});

$.ax.callback('System.note.delete', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var $target = $(e.target),
			id = $target.attr('href'),
			done = function(){
				$.get('/utils/noteDelete', {id: id}, function(json, status, jqXhr, data) {
					if (status === $.ax.CONST.STATUS_PENDING) {
						$target.closest('li').slideUp(function(){
							$(this).remove();
							$.ax.success(__('Delete note Success'));
						});
					}
				});             
			};
			
		$.ax.confirm(__('Sure to delete this note?')).confirm(done);
		return false;
	}
});

$.ax.callback('System.tool.save', {
	defaults: {
	},
	handler: function(e, options, ui) {
		$('.xform .btn-save').click();
		return false;
	}
});

$.ax.callback('System.tool.csave', {
	defaults: {
	},
	handler: function(e, options, ui) {
		$('.xform .btn-savenew').click();
		return false;
	}
});

$.ax.callback('System.tool.cancel', {
	defaults: {
	},
	handler: function(e, options, ui) {
		$('.xform .btn-cancel').click();
		//$('.xform').get(0).reset();
		return false;
	}
});


// export class list
$.ax.callback('System.tool.export', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var self = this,
			owner = ui.owner() || {},
			data, url;

		data = owner.collect();
		url = self.href + '?keys=' + data['keys'].join(',');
		download(url);

		return false;
	}
});

//import class list
$.ax.callback('System.tool.import', {
	defaults: {
	},
	handler: function(e, options, ui) {
			
		return false;
	}
});

//Command Line
$.ax.callback('System.tool.command', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var $wrapper = $('<div/>'),
			prefix = 'axdialog';
		
		$wrapper
		.axDialog({
			width: 800,
			height: 600,
			modal: true,
			title: 'Command Line',
			position: ['center', 100],
			href: this.href,
			buttons: [],
			close: function(e) {
				$(this).remove();
			}
		}).bind(prefix+'loaded', function(e){
			$(this).closest('.ui-dialog').css('backgroundColor', '#000');
			$('.cli', this).terminal("/sys/tools/command/", {
				login: true,
				greetings: __("Welcome login AX "),
				prompt: 'AX2600>',
				tabcompletion: true
			});
		});
		return false;
	}
});

// Settings
$.ax.callback('System.tool.config', {
	defaults: {
	},
	handler: function(e, options, ui) {
		AX.router.reload($(this).attr('href'));
		return false;
	}
});


$.ax.callback('System.tool.editListSearch', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var _keywords = $(this).prev('span').find(".input-small").val(),
			_keyFields = $(this).prev('span').find("[name='keyFields']").val(),
			_href = $(this).attr('href');
		if (!_keywords || !_keyFields) {
			$.ax.error(__('Please fill keywords and key fields'));
			return false;
		}
		$.get(_href, {keyFields:_keyFields,editKeywords:_keywords}, function(data, status){
			if (status === $.ax.CONST.STATUS_PENDING) {
				var _content = $(data).find('.list-items');
				$('.list-content .list-items').replaceWith(_content);
			} else {
				$.ax.error(__('Internal Error'));
			}
			return;
		});
		return false;
	}
});


$.ax.callback('System.tool.addChart', {
	defaults: {
	},

	handler: function(e, options, ui) {
		console.log( ui, ui.owner() );
		return false;
	}
});


$.ax.callback('System.help', {
	defaults: {
		$helper: null
	},

	init: function(e, options, ui) {
		var $dom = $( this );
		options.$helper = $('#config-helper');
		options.$helper.find('.config-helper-closeBtn').on('click', function() {
			$dom.click();
		});
	},

	handler: function(e, options, ui) {
		var $dom = $( this );
		$dom.toggleClass( 'toolbar-item-active' );
		options.$helper.animate({width: 'toggle'}, 300);
		return false;
	}
});

$.ax.callback('System.tool.indexer', {
	defaults: {
	},
	handler: function(e, options, ui) {
		var self = this,
			owner = ui.owner() || {},
			url = self.href;

		var $el = $('#JQ-AX-LOADING'), jqXhr;

		if (!$el.length) {
			$el = $('<div id="JQ-AX-LOADING"></div>').appendTo('body');
			$el.axDialog({
				modal: true,
				title: __('Indexing...'),
				autoOpen: false,
				resizable: false,
				width: 480,
				minWidth: 480,
				minHeight: 180,
				dialogClass: 'no-close',
				formMode: false,
				buttons: [
					{
						text: __('Cancel'),
						click: function(e) {
							jqXhr.abort();
							$(this).axDialog('close');
							AX.router.reload(url);
							e.stopPropagation();
							e.preventDefault();
						}
					}
				],
				position: {
					my: 'center',
					at: 'center'
				}, 
				open: function() {
					jqXhr = $.ajax(url, {async:true, complete: function(){
						$el.axDialog('close');
						AX.router.reload(url);
					}});
					$.ax.hideLoading( jqXhr );
				}
			});
		}
		message = "Indexing all data, click Cancel button to stop indexing ";
		$el.html( '<div class="dialog-msg">' + ( message || __('Please give a message.') ) + '</div>' ).axDialog('open');

		return false;
	}
});

$.ax.callback('System.tool.reload', {
	defaults: {},

	handler: function(e, options, ui) {
		var 
			$menu = $(this),
			$target = $( e.target ),
			axToolbar = $.ax.axToolbar,
			second = $target.data('second');

		if ( AX.util.isNumber( second ) ) {
			var $item = $menu.parent();
			if ( second == 0 ) {
				axToolbar.stopReload($menu, $item);
			}
			else {
				axToolbar.stopReload( $menu );
				axToolbar.set($item.find('>a'), { second: second }, $item);
				$target.next().show();
				axToolbar.startReload( second * 1000 );
			}
		}
		else {
			axToolbar.stopReload( $menu );
			AX.router.reload();
		}
		
	}
});

$.ax.callback('System.tool.print', {
	defaults: {},

	handler: function(e, options, ui) {
		AX.widgets.printer.show( $( this ).data('config') );
		return false;
	}
});

$.ax.callback('System.grid.operate.settings.columns', {
	defaults: {},

	handler: function(e, options, ui) {
		return false;
	}
});


})(jQuery);

function get_wizard($solution)
{
	$.get('/wizard/default/index', {'initialize':true, 'solution': $solution}, function(json,status, jqXhr, data){
		var $mask, $wrapper;
		
		// initial
		$('body').css({overflow:'hidden'});
		
		// mask
		$mask = $('<div class="wizard-mask"></div>').appendTo('body');
		
		// content
		$.ui.axWizard.prototype.options.popup = true;
		$wrapper = $(json).appendTo('body').show().find('.xform, .info').css({
			'maxHeight': $(window).height()-220 + 'px',
			'overflow': 'auto'
		});
	});
}