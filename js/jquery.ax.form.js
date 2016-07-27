/*!
* Form edit.
*
* Depends:
*  jquery.ui.widget.js
*/
// closure start
(function() {

function showCloseConfirm(name, callback, title) {
	var msg = __('Some items changed, are you sure to cancel' );
	title = title || __( name );
	$.ax.confirm(msg, null, title, 'icon-edit').confirm( callback );
}

(function($, undefined) {

var CONST = $.ax.CONST,
	Buttons = {
		'save': '<button type="button" class="btn btn-green btn-save" data-callbacks="System.Widget.axForm.save"><i class="icon-save"></i> '+ __('Apply') + '</button>',
		'save&new': '<button type="button" class="btn btn-blue btn-savenew" data-callbacks="System.Widget.axForm.save&new"><i class="icon-plus-circle"></i>  '+ __('Apply & New') + '</button>',
		'cancel': '<button type="button" class="btn btn-default btn-cancel" data-callbacks="System.Widget.axForm.cancel"><i class="icon-ban"></i>  '+ __('Cancel') + '</button>',
		'preview': '<button type="button" class="btn btn-gold" data-callbacks="System.Widget.axForm.preview"> '+ __('Preview') + '</button>',
		'generate': '<button type="button" class="btn btn-sea" data-callbacks="System.Widget.axForm.generate"> '+ __('Save & Generate Elements') + '</button>'
	};

$.ax.callback({
	'System.Widget.axForm.save': {
		handler: function(e, options, ui) {
			ui.owner().save();
		}
	},
	'System.Widget.axForm.save&new': {
		handler: function(e, options, ui) {
			ui.owner().saveNew();
		}
	},
	'System.Widget.axForm.cancel': {
		handler: function(e, options, ui) {
			ui.owner().cancel();
		}
	},
	'System.Widget.axForm.preview': {
		handler: function(e, options, ui) {
			ui.owner().preview();
		}
	},
	'System.Widget.axForm.generate': {
		handler: function(e, options, ui) {
			ui.owner().generate();
		}
	}
});

$.widget('ui.axForm', {
	options: {
		buttons: null,
		isInnerForm: false,
		innerMode: null,
		level: 0,
		scenario: CONST.SCENARIO_INSERT,
		fieldsetHeader: '.box-header',
		fieldsetContent: '.box-content',
		toolbar: '.box-toolbar',
		expander: '.icon-minus, .icon-plus',
		saveRedirectUrl: '',
		cancelRedirectUrl: '',
		detachedClass: 'detached',
		oneFieldsetMode: false,
		submitOptions: {},
		submitHandler: null
	},

	_create: function() {
		var self = this,
			o = this.options;
		
		// init properties
		self.form = self.element.is('form') ? self.element : self.element.find('form:first');
		
		self.element.axFormGui();
		if (self.form.length) {
			self._formEvents();
			self._initSkin();
			self.form.addClass('axform').validate();
		}
		
		if (!o.isInnerForm) {
			self._initButtons();
			$.ax.cache.initFormCache(self, function() {
				self.element
					.find(o.fieldsetHeader)
					.find( o.toolbar )
					.find( o.expander )
					.trigger('click');
			});
		}
		
		// form builder
		if (self.element.hasClass('axFb')) {
			self.element.axFormBuilder();
		}
		
		$('.form-tabs', self.element).axTabs({
			beforeActivate: function(e, ui) {
				return ui.oldPanel.axIsValid();
			}
		});

		self.element.find('textarea').css({
			width: '',
			height: ''
		});

		//redirect to history 
		//if (document.referrer) o.cancelRedirectUrl = document.referrer;

		self.element.find('.ui-datepicker-trigger').addClass('btn').html('<i class="icon-calendar-o"></i>');
	},
	
	_formEvents: function() {
		var self = this;
		
		self.form.on({
			'submit': function() { return false; },
			'form-pre-serialize': function(e, form, options, veto) {
				var $this = $(this),
					$valWrapper = $this.data($.ax.CONST.SUB_VALUE_WRAPPER);
				
				if (!$valWrapper) {
					$valWrapper = $('<div class="' + $.ax.CONST.SUB_VALUE_WRAPPER + '" style="display:none"/>').appendTo($this);
					$this.data($.ax.CONST.SUB_VALUE_WRAPPER, $valWrapper);
				}
				
				$valWrapper.empty();console.log(self.element.find('.js-form-pre-serialize'));
				self.element.find('.js-form-pre-serialize').trigger('preSerialize', [$valWrapper]);
			}
		});
	},
	
	_init: function() {
		this.changePrimaryStyle();
	},
	
	changePrimaryStyle: function() {
		var self = this,
		o = this.options;
	
		// primary element
		if (o.scenario === CONST.SCENARIO_UPDATE) {
			self.element
				.find('[primary],[candidate]')
				.each(function() {
					var $this = $(this),
						$wrapper;
					
					if ($this.is(':text:visible')) {
						$this.before($('<span class="span-text"></span>').text($this.val())).hide();
					} else if ($this.is(':radio:checked:visible')) {
						$wrapper = $this.parent().parent();
						$wrapper.before($('<span class="span-text"></span>').text($this.parent().text())).hide();
					} else if ($this.is('select:visible')) {
						$this.before($('<span class="span-text"></span>').text($this.find('option:selected').text())).hide();
					}
				});
		}
		
	},
	
	_initSkin: function() {
		var self = this,
			o = this.options,
			token = self.form.axSerializeJSON();
			
		// fielset expander, now there code are not used.
		// @todo remove

		self.element
			.find(o.fieldsetHeader)
			//.disableSelection()
			// .on('dblclick.' + self.widgetName, function() {
			// 	$(this).find(o.expander).trigger('click');
			// })
			.find( o.toolbar )
			.find(o.expander)
			.on('click.' + self.widgetName, function(e, pvt) {
				var $this = $(this),
					$visible,
					$panel = $this.closest('fieldset').find(o.fieldsetContent);

				if (pvt) {
					$this.hasClass('icon-plus') && $panel.hide();
					return false;
				}
				
				$visible = $panel.is(':hidden');
				if (o.oneFieldsetMode) {
					$this.closest('fieldset').siblings().find(o.fieldsetContent).slideUp().end()
						.find('.icon-minus').removeClass('icon-minus').addClass('icon-plus');
				}

				if ($visible) {
					$panel.slideDown();
					$this.removeClass('icon-plus').addClass('icon-minus');
				} else {
					$panel.slideUp();
					$this.removeClass('icon-minus').addClass('icon-plus');
				}

				$.ax.cache.saveFormCache(self, $visible);
			})
			.trigger('click', [true]);

		if ( o.isInnerForm && o.innerMode === 'simple' ) return;
		
		// trace the form data change.
		var 
			$form,
			dependUI,
			timerId = null,
			buttons = $('#gtoolbar').find('.icon-save, .icon-plus-circle, .icon-minus-circle').hide();

		self.form.on('keypress click', function(e) {
			var $target = $( e.target );
			if ( $target.is(':input') ) {
				timerId && clearTimeout(timerId);
				timerId = setTimeout(function() {
					$form = $target.closest('.axform');
					if ( $form.hasClass('form-depend-content') ) {
						if ( dependUI = $form.data('dependUI') ) {
							if ( $form.axSerializeJSON() !== $form.data('token') ) {
								dependUI.isChanged = true;
							}
							else {
								dependUI.isChanged = false;
							}
						}
					}
					else {
						if ( $form.axSerializeJSON() !== token ) {
							self.isChanged = true;
							buttons.show();
						} else {
							self.isChanged = false;
							buttons.hide();
						}
					}
					timerId && clearTimeout(timerId);
				}, 200);
			}
		});
	},
	
	_initButtons: function() {
		var self = this,
			o = this.options,
			$boxs,
			boxAmount = 0;
		
		if (o.buttons === null) {
			switch (o.scenario) {
				case 'insert':
					o.buttons = ['save', 'save&new', 'cancel'];
					break;
				case 'update':
				case 'copy':
					o.buttons = ['save', 'cancel'];
					break;
				case 'view':
					o.buttons = ['cancel'];
					break;
				default:
					o.buttons = [];
					$.error('Unknown scenario ' + o.scenario);
					break;
			}
		}
		
		$boxs = self.form.find('.box');
		boxAmount = $boxs.length;

		self.buttons = $('<div class="form-actions">' + $.ax.tidyButtons(o.buttons, Buttons).join('') + '</div>');
		self.buttons.appendTo( ( boxAmount === 1 )? $boxs : self.form )
			.axCallback({owner: self})
			.find('button:first')
			.addClass('btn-primary');
	},	
	
	redirect: function( url ) {

		var 
			referer,
			actionOptions = AX.action.getOptions(),
			currentUri = AX.router.getUri(),
			referUri = AX.history.getReferUri();

		if ( referUri === '' || currentUri === referUri || ( actionOptions.data && actionOptions.data.refer ) ) {
			referer = url;
		}
		else {
			referer = AX.router.getRefer();
		}

		if (!referer && document.referrer && document.referrer.indexOf('/login') === -1) {
			referer = document.referrer;
		}

		if (!referer) {
			$.error(__('No rediret url given'));
		}

		//window.location.href = referer;
		AX.router.href( referer );
	},
	
	save: function(dialog) {
		var self = this;
		
		if (self._trigger('beforeSave')) {
			var o = this.options, options = {
				dataType: 'json',
				resetForm: false,
				success: function(json, status, jqXhr) {
					$.ax.success( __('Saved Successfully') );
					$('#writeMemory').show();
					
					if (dialog) {
						$(dialog).axDialog('close');
						return;
					}

					if ( ! o.saveRedirectUrl ) {
						return;
					}
					
					if (status === CONST.STATUS_REDIERCT) {
						AX.router.href( json );
					} else {
						self.redirect($.ax.rebuildUrl(o.saveRedirectUrl, json));
					}
				}
			};		

			$.extend(options, o.submitOptions);
			if (o.submitHandler) {
				o.submitHandler.call(self);
			} else {
				self.form.axAjaxSubmit(options);
			}
			
		}
		
		return false;
	},
	
	saveNew: function() {
		var o = this.options, self = this,
			options = {
				dataType: 'json',
				resetForm: false,
				success: function() {
					AX.router.reload();
					$.ax.success( __('Saved Successfully') );
					$('#writeMemory').show();
				}
			};			
		
		$.extend(options, o.submitOptions);
		if (o.submitHandler) {
			o.submitHandler.call(self);
		} else {
			self.form.axAjaxSubmit(options);
		}
		return false;
	},
	
	cancel: function( callback ) {
		var 
			o = this.options,
			$depends = this.form.find('.axform'),
			editedCount = 0;

		$depends.each(function(i, el) {
			var depend = $( el ).data('dependUI');
			if ( depend.isChanged ) ++editedCount;
		});
		
		this.beforeCancel(callback, ( editedCount > 0 ));
		return false;
	},

	beforeCancel: function(callback, isEdit) {
		var 
			self = this,
			o = this.options;

		callback = callback || function() {
			self.redirect( o.cancelRedirectUrl );
		};

		if ( this.isChanged || isEdit ) {
			showCloseConfirm(this.form.data('formInfo').title, callback);
			return false;
		}
		else {
			callback();
		}
	},

	preview: function() {
		var self = this;
		var o = this.options;
		var options = {
				url: o.previewRedirectUrl,
				dataType: 'json',
				resetForm: false,
				success: function(json, status, jqXhr) {
					if (typeof json === 'string') {
						json = $.parseJSON(json);
					}
					$('<div/>').append(json.data).appendTo('body').axDialog();
				}
			};	
		$.extend(options, o.submitOptions);
		self.form.axAjaxSubmit(options);
		return false;
	},
	
	generate: function() {
		var self = this;
		var o = this.options;
		var options = {
				url: o.generateRedirectUrl,
				dataType: 'json',
				resetForm: false,
				success: function(json, status, jqXhr) {
					if (typeof json === 'string') {
						json = $.parseJSON(json);
					}
					$('<div/>').append(json.data).appendTo('body').axDialog();
				}
			};	
		$.extend(options, o.submitOptions);
		self.form.axAjaxSubmit(options);
		return false;
	}
});

})(jQuery);

(function($) {

$.widget('ui.axFormGui', {
	_create: function() {
		var self = this;
		
		self.eselect();
		self.combobox();
		self.grid();
		self.tip();
	
		this.element
			.find('[data-config]')
			.each(function() {
				var $this = $(this),
					events = $this.data('config'),
					inited = $this.data('GUI_INITED') || {},
					type;

				for (type in events) {
					if (events.hasOwnProperty(type) && !inited[type]) {
						if ($.isFunction(self[type + 'Event'])) {
							self[type + 'Event']($this, events[type]);
							inited[type] = true;
						} else if ($.ui[type]) {
							$this.not(':data(' + type + ')')[type]();
							inited[type] = true;
						} else {
							$.warn(__('Event type ') + type + __(' is not defined.'));
						}
					}
				}
				
				$this.data('GUI_INITED', inited);
			});
	},
	
	toggleEvent: function($el, options) {
		var self = this,
			eventType,
			defaults = {
				'type': 'display', // display, filter, switch, change, disable
				'switch': 'unknown',
				'disable': false,
				'container': null
			};
		
		eventType = ($el.is(':input') ? 'change.' : 'click.') + self.widgetName;
		options = $.extend(defaults, options);
		$el.off(eventType).on(eventType, function(e) {
			var $this = $(this),
				$panel, $sub,
				labels, value,
				change = function($panel, value, disable) {
					var $sub = $panel.filter('[data-switch~="' + options['switch'] + '-' + value + '"]');

					$panel.hide();
					$sub.show();
					if (disable) {
						disableEl($panel, true);
						disableEl($sub, false);
					}
				},
				disableEl = function($panel, value) {
					$panel.each(function() {
						var $this = $(this);
						
						if ($this.is(':input')) {
							$this.prop('disabled', value);
						} else {
							$this.find(':input')
								.prop('disabled', value)
								.each(function() {
									var $this = $(this),
										status = $this.data('GUI_INITED') || {};
										
									if (status.toggle) {
										$this.triggerHandler('change.' + self.widgetName);
									}
								});
						}
					});
				};
					
			if (options.type === 'display') {
				if (options.container) {
					$panel = $this.closest(options.container).find('[data-switch|="' + options['switch'] + '"]');
				} else {
					$panel = self.element.find('[data-switch|="' + options['switch'] + '"]');
				}
				
				if ($this.is(':checkbox')) {
					value = options.reverse ? !this.checked : this.checked;
					
					if (value) {
						$panel.show();
					} else {
						$panel.hide();
					}
					if (options.disable) {
						disableEl($panel, !value);
					}
				} else if ($this.is('a')) {
					if ($this.data('expanded') === false) {
						$panel.show();
						$this.addClass('advance-expanded').data('expanded', true);
					} else {
						$panel.hide();
						$this.removeClass('advance-expanded').data('expanded', false);
					}
				} else if ($this.is('select') || $this.is(':radio:checked')) {
					value = $this.val();
					change($panel, value, options.disable);
				} else if ($this.is(':text')) {
					value = $this.val();
					if (value) {
						$panel.show();
					} else {
						$panel.hide();
					}
					if (options.disable) {
						disableEl($panel, this.checked);
					}
				}
			} else if (options.type === 'filter') {
				if ($this.is('select') || $this.is(':radio:checked')) {
					$panel = self.element.find('[data-switch-' + options['switch'] + ']');
					labels = $this.is('select') ? (self.element.find(':selected').data('switch') || '') : ($this.data('switch') || '');
					labels = labels.split(/\s*,\s*/);
					$sub = $panel.filter(function() {
						return $.inArray($(this).attr('data-switch-' + options['switch']), labels) > -1;
					});
					$panel.hide();
					$sub.show();
					if (options.disable) {
						disableEl($panel, true);
						disableEl($sub, false);
					}
				}
			} else if (options.type === 'switch') {
				$panel = self.element.find('[data-switch|="' + options['switch'] + '"]');
				if ($this.is('select') || $this.is(':radio:checked')) {
					value = $this.val();
					change($panel, value, options.disable);
				} else if ($this.is(':checkbox')) {
					value = $this.is(':checked') ? '1' : '0';
					change($panel, value, options.disable);
				}
			} else if (options.type === 'change') {
				$panel = self.element.find('[data-switch|="' + options['switch'] + '"]');
				if ($this.is('select')) {
					value = $this.val();
					if (value === '') {
						$panel.hide();
					} else {
						$panel.show();
					}
				}
			} else if (options.type === 'disable') {
				if ($this.is(':checkbox')) {
					$panel = self.element.find('[data-switch|="' + options['switch'] + '"]');
					value = options.reverse ? !this.checked : this.checked;
					disableEl($panel, value);
				}
			}

			//e.stopPropagation();
		}).trigger(eventType);
	},
	
	aclEvent: function($el) {
		var self = this,
			config = $el.data('config') || {};
		
		if (config.toggle && config.toggle['switch']) {
			$el.on('change.' + self.widgetName, function() {
				self.element.find('[data-switch|="' + config.toggle['switch'] + '"]')
					.find('select')
					.axFormDepend('close');
			});				
		}
	},
	
	redirectEvent: function($el, options) {
		var self = this,
			defaults = {
				url: ''
			};
		
		if (typeof options === 'string') {
			options = {url: options};
		}
		options = $.extend(defaults, options);
		
		// Bind event
		$el.off('change.' + self.widgetName).on('change.' + self.widgetName, function() {
			var $this = $(this),
				params = {};
			
			if ($this.is('input')) {
				AX.router.href( options.url );
			} else if ($this.is('select')) {
				params[this.name] = $this.val();
				AX.router.href( $.ax.rebuildUrl(options.url, params) );
			}
		});
	},

	replaceEvent: function($el, options) {
		$el.not(':data(axFormReplace)').axFormReplace(options);
	},
	
	dependEvent: function($el, options) {
		$el.not(':data(axFormDepend)').axFormDepend(options);
	},
	
	combobox: function() {
		var self = this;
		
		self.element
			.find('.combobox')
			.not(':data(axCombobox)')
			.axCombobox();
	},
		
	tip: function() {
		var 
			self = this,
			$tipEl = self.element
					.find(':input[name][data-tips], .radio[data-tips], .checkbox[data-tips]')
					.not('[type="hidden"]');

		if ( $tipEl.length ) {
			$tipEl
				.off('mouseleave.' + self.widgetName)
				.off('mouseenter.' + self.widgetName)
				.on('mouseenter.' + self.widgetName, function() {
					var tips = $(this).data('tips');
					if (tips) {
						var $elementContainer = $(this).closest('.controls').children('div:first'),
							$element = $elementContainer.find('.inplace-tips');
						if (!$element.length) {
							$element = $('<span class="inplace-tips"/>').appendTo($elementContainer);	
						}
						$element.html(tips).show();
					}
				})
				.on('mouseleave.' + self.widgetName, function() {
					var $elementContainer = $(this).closest('.controls').children('div:first');
					$elementContainer.children('.inplace-tips').hide();
				});
		}
	},
	
	grid: function() {
		var self = this;
		
		self.element
			.find('.v-grid-xform-list')
			.not(':data(axGrid)')
			.each(function() {
				var $this = $(this);

				$this.axGrid({
					toolbar: $(this).find('ul.toolbar'),
					mode: 'form'
				});
			});
	},
	
	eselect: function() {
		var self = this;
		
		self.element
			.find('.eselect')
			.not(':data(axEselect)')
			.axEselect();
	}
});

})(jQuery);

(function($, undefined) {

var Buttons = {
		'save': '<button type="button" class="btn btn-sea form-depend-action" role="save"><i class="icon-save"></i>'+ __('Apply') + '</button>',
		'cancel': '<button type="button" class="btn btn-default form-depend-action" role="close"><i class="icon-ban-circle"></i>' + __('Cancel') + '</button>'
	};

$.widget('ui.axFormDepend', {
	options: {
		level: 0,
		iconSimple: '<i class="icon-assist icon-plus"></i>',
		iconAdvance: '<i class="icon-assist icon-plus-square"></i>',
		wrapper: '<div class="box form-depend js-form-pre-serialize"></div>',
		toolbar: '<ul class="box-toolbar form-depend-buttons"><li class="toolbar-link"><a class="form-depend-action icon-remove" role="close"></a></li></ul>',
		content: '.fieldset-content > *',
		headerClass: 'box-header form-depend-header'
	},

	_create: function() {
		// extend options
		$.extend(this.options, this.element.data('depend'));

		this.level = this.options.level;
		this.header = this.element.closest('div.box-row');
		this.iconsList = $();
		this.iconSimple = null;
		this.iconAdvance = null;
		this.buttonsWrappler = null;
		this.form = null;
		this.identity = {};

		this._bindEvents();
		this._initDepender();
		// this._onHeaderEvent();
	},

	_bindEvents: function() {
		var self = this;

		this.element
			.on('change.' + this.widgetName, function( e ) {
				self.form && self.closeAll( e );
			});
	},

	_initDepender: function() {
		var 
			self = this,
			o = this.options,
			tip;

		self.tip = self.element.closest('.control-group').find('.control-label').text().replace(/\*/g,'') || '';
		tip = __('Add') + ' ' + self.tip;

		// simple
		if ( o.simple ) {
			this.iconSimple = $('<button class="btn tip" data-title="'+ tip +'" data-href="'+ this._testHref( o.simple ) +'">' + o.iconSimple + '</button>');
			this.iconSimple
				.on('click.' + this.widgetName, function(e) {
					if ( ! self.form ) {
						self._loadDepForm( $(this).data('href'), 'simple' );
						$(this).trigger('mouseleave');
					}
					return false;
				});
			this.icons('add', this.iconSimple);
		}

		// advance
		if ( o.advance ) {
			this.iconAdvance = $('<button class="btn tip" data-title="'+ tip +'" data-href="'+ this._testHref( o.advance ) +'">' + o.iconAdvance + '</button>');
			this.iconAdvance
				.on('click.' + this.widgetName, function(e) {
					self.form && self.form.trigger('close');
					self._loadDepForm( $(this).data('href'), 'advance' );
					return false;
				});
			this.icons('add', this.iconAdvance);
		}

		if ( this.iconsList.length ) {
			$btns = $('<span class="btns"></span>').html( this.iconsList );
			this.element.wrap('<div class="btn-group btn-group-select"></div>')
						.after( $btns );
			// this.element.wrap('<div class="btn-group btn-group-select"></div>')
			// 			.after( this.iconsList );
		}
		$btns.find('.tip').tooltip();

	},

	_testHref: function( href ) {
		if ( ! /^\/+/.test( href ) ) {
			href = '/' + href;
		}
		return href;
	},

	_delegateAction: function( $actionBtns ) {
		var 
			self = this,
			from = self.form,
			buttons = {
				'save': function(e) {
					from && from.trigger('save');
					return false;
				},
				'close': function(e) {
					self.closeAll( e );
					return false;
				},
				'help': function(e) {
					// @todo
					return false;
				}
			},
			$actions = self.header.add( $actionBtns );

		$actions.off('click.depend-action').on('click.depend-action', '.form-depend-action', function( e ) {
			var role = $( this ).attr('role');
			buttons[role]( e );
		});

	},

	_loadDepForm: function(href, mode) {
		var self = this,
			o = this.options;

		return $.ajax({
				url: href,
				element: self.element,
				data: {depend: mode}
			})
			.success(function(json, status, jqXhr) {
				var $form,
					$wrapper = $('<div/>').appendTo('body').hide(),
					$header,
					options,
					identity,
					formInfo,
					$content,
					$actionBtns, render, html;

				self.isChanged = false;
				// Fixed bug, when response html has script, the script not execute.
				render = new FormRender($.parseJSON(json.content));
				html = render.render();console.log(html)
				$form = $wrapper.append(html).children();
				formInfo = $form.data('formInfo');
				self.identity = $.extend({parent: self.element.attr('name')}, formInfo);
				
				if (mode === 'simple') {
					//self._offHeaderEvent();
					self.icons( 'hide' );
					$actionBtns = $('<div class="form-actions">' + $.ax.tidyButtons(['save', 'cancel'], Buttons).join('') + '</div>');
					$content = $( '<form class="form-depend-content"/>' );
					$content.html( $form.find( o.content ) );
					$content.append( $actionBtns );
					self.form = self.createWrapper();
					self.form
						.insertAfter(self.header)
						.append(self.header.addClass(o.headerClass))
						.append( $content )
						.hide()
						.slideDown();

					self._delegateAction( $actionBtns );
					$content.data({
						token: $content.axSerializeJSON(),
						dependUI: self
					});
					self.$content = $content;
					//self.onChange( $content );
				} else if (mode === 'advance') {
					options = $.extend({
						modal: true,
						resizable: false,
						buttons: ['save', 'cancel'],
						beforeClose: function() {
							var uiAxForm = $form.data('uiAxForm');
							if ( uiAxForm ) {
								return uiAxForm.cancel(function() {
									self.closeAll();
								});
							}
						}
					}, $form.data('dialog'));
					self.form = $form
						.appendTo('body')
						.axDialog(options)
						.axHideFormHeader();
					$header = self.form.find('fieldset:visible');
					if ($header.size() === 1) {
						$header.find('.box-header').hide();
					}
				}

				$wrapper.remove();
				self.form
					.data('formInfo', formInfo)
					.data('FORM_DEPEND_TYPE', mode)
					.on('save', self.getCallbacks('save'))
					.on('cancel close', {self: self}, self.closeAll)
					.axForm({
						isInnerForm: true,
						innerMode: mode,
						level: self.level + 1
					});
			});
	},

	getCallbacks: function( type ) {
		if ( ! this.callbacks ) {
			this.setCallbacks();
		}

		// return type
		if (type) {
			return this.callbacks[type];
		}

		return this.callbacks;
	},

	setCallbacks: function() {
		var 
			self = this,
			o = this.options;

		this.callbacks = {};

		// save action
		this.callbacks['save'] = function(e) {
			var formInfo = self.form.data('formInfo'),
				$form = (self.form.data('FORM_DEPEND_TYPE') === 'advance') ? self.form : self.form.find('.form-depend-content');

			if (!formInfo.action) {
				$.ax.error(__('Not defined action'));
				return false;
			}

			$form.axAjaxSubmit({
				url: $.ax.rebuildUrl(formInfo.action, {_vmode: $.ax.CONST.VMODE_XFORM_DEPEND_SAVE}),
				success: function(json, status, jqXhr) {
					var label, value;
					self.isChanged = false;

					if (self.element.is('select')) {
						if (typeof json === 'object') {
							label = json.label;
							value = json.value;
						} else {
							label = value = json;
						}

						if (!self.element.find('[value="' + value + '"]').length) {
							self.element.append('<option value="' + value + '">' + label + '</option>');
						}
						self.element.val(value);
						self.element.change();
					}
				}
			});

			return false;
		};

		// close action
		this.callbacks['close'] = function(e, callback, fast) {
			self.isChanged = false;
			if ( self.form ) {
				if ( self.form.data('FORM_DEPEND_TYPE') === 'advance' ) {
					self.form.remove();
					self.form = null;
					callback && callback();
				}
				else {
					var destoryFn = function() {
						self.header.find('.form-depend-buttons').remove(),
						self.header.find('.form-depend-header-label').remove();
						self.header
							.removeClass( o.headerClass )
							.insertBefore( self.form );
							
						self.form.remove();
						self.icons( 'show' );
						self.form = null;
						callback && callback();
					};
					if ( fast ) {
						self.form.hide();
						destoryFn();
					}
					else {
						self.form.slideUp( destoryFn );
					}
				}
			}
		};

		// help action
		this.callbacks['help'] = function (e) {
			var url = window.help_url + '&keywords=' + self.depender.attr('name');

			window.open(url, 'help');
			return false;
		};
	},

	createWrapper: function() {
		var self = this,
			o = this.options,
			$el = this.element,
			$parent = $el.closest( '.form-depend' ),
			$wrapper = $(o.wrapper),
			$header = self.header,
			grayCls = 'form-depend-gray';
			
		( ! $parent.length || ! $parent.hasClass( grayCls ) ) && $wrapper.addClass( grayCls );
		( ! $parent.length ) && $wrapper.addClass( 'form-depend-parent' );
		
		$header.prepend('<span class="label label-green form-depend-header-label">'+ __('ADD') +'</span>');
		$header.prepend( o.toolbar );
		
		self._on($wrapper, {
			'preSerialize': function(e, $valWrapper) {
				self.identity.value = $(e.target).find('form:first').axFormSerialize(true);
				$valWrapper.axAddHidden('__depends[]', self.identity);
				return false;
			}
		});
		return $wrapper;
	},

	closeAll: function(e, callback, fast) {
		var 
			self = ( e && e.data )? e.data.self : this,
			callback = AX.util.isFunction( callback )? callback : AX.util.getEmptyFunction(),
			close = true,
			editedCount = 0,
			depends,
			closeFn,
			confirmTitle;

		if ( ! self.form ) {
			callback();
			return;
		}
		
		depends = self.form.find('.axform.form-depend-content').get().reverse();

		$( depends ).each(function(i, el) {
			var dependUI = $( el ).data('dependUI');
			if ( dependUI.isChanged ) {
				++editedCount;
				confirmTitle = dependUI.tip;
			}
		});

		//if ( self.isChanged ) ++editedCount;

		closeFn = function() {
			$( depends ).each(function(i, el) {
				$( el ).data('dependUI').close(e, null, ( i + 1 !== depends.length ) );
			});
			( self.form ) && self.close( e );
			callback();
		};

		if ( editedCount > 0 ) {
			showCloseConfirm(confirmTitle, closeFn);
		}
		else {
			closeFn();
		}
	},
	
	close: function(e, callback, fast) {
		this.getCallbacks('close')(e, callback, fast);
	},

	icons: function(act, el) {
		switch (act) {
			case 'add':
				this.iconsList = this.iconsList.add(el);
				//$(el).hide();
				break;
			case 'remove':
				this.iconsList = this.iconsList.not(el);
				//$(el).hide();
				break;
			case 'show':
				this.iconsList.show();
				
				break;
			case 'hide':
				this.iconsList.hide();
				break;
			default:
				break;
		}
	}
});

})(jQuery);

(function($, undefined) {

$.widget('ui.axFormDraggable', {
	options: {
		name: '',
		idPrefix: '_',
		data: []
	},
	
	_create: function() {
		this.items = null;
		this.table = this.element.find('.draggable-table');
		this.thead = this.table.find('thead:first');
		this.tbody = this.table.find('tbody:first');
		this.serializeInput = $('<input type="hidden" name="' + this.options.name + '" value="" />');
		this.columns = this.tbody.find('ul');
		
		this._initSkin();
	},
	
	_initSkin: function() {
		var self = this,
			o = this.options,
			i, l;
		
		self.element.append(self.serializeInput);
		self._items();
		for (i = 0, l = o.data.length; i < l; ++i) {
			self._addItems(self.columns.eq(i), o.data[i]);
		}
		
		self.columns
			.sortable({
				connectWith: self.columns,
				containment: self.element,
				opacity: 0.75,
				stop: function() {
					self.serialize();
				}
			})
			//.disableSelection()
			.width(o.width)
			.height(o.height)
			.on('click.' + self.widgetName, 'a.icon-minus, a.icon-plus', function() {
				var $this = $(this);
				
				if ($this.hasClass('icon-plus')) {
					$this.closest('li').children('table').show();
					$this.removeClass('icon-plus').addClass('icon-minus');
				} else {
					$this.closest('li').children('table').hide();
					$this.removeClass('icon-minus').addClass('icon-plus');
				}
				return false;
			});
			
		self.serialize();
	},
	
	_addItems: function($col, data) {
		var self = this,
			i, l;
		
		for (i = 0, l = data.length; i < l; ++i) {
			$col.append(self.items[data[i]]);
		}
	},
	
	_items: function() {
		var self = this,
			o = this.options;
		
		if (self.items === null) {
			self.items = {};
			
			self.element
				.find('.hide-items > li')
				.each(function() {
					var id = this.id.replace(o.idPrefix, '');
					self.items[id] = $(this);
				});
		}
		
		return self.items;
	},
	
	serialize: function() {
		var self = this,
			o = this.options,
			res = [];
			
		self.columns
			.each(function() {
				var $this = $(this),
					ids = $this.sortable('toArray'),
					i, l;
				for (i = 0, l = ids.length; i < l; ++i) {
					ids[i] = ids[i].replace(o.idPrefix, '');
				}
				
				res.push(ids);
			});
			
		self.serializeInput.val($.toJSON(res));
		return res;
	}
});

})(jQuery);

(function($, undefined) {

$.widget('ui.axFormReplace', {
	options: {
		url: '',
		wrapper: ''
	},

	_create: function() {
		var o = this.options;
		
		o = $.extend(o, this.element.data('replace'));
		this.types = {};
		this.form = this.element.closest('form');

		this.current = this.form.find('[data-wrapper="' + o.wrapper + '"]');//.removeClass('row-wrapper-normal');
		this.types[this.element.val()] = this.current;

		this._bindEvents();
	},

	_bindEvents: function() {
		var o = this.options,
			self = this;

		this.element
			.on('change.' + this.widgetName, function(e) {
				var $this = $(this),
					val = $this.val();

				if (self.types[val]) {
					self.types[val].insertBefore(self.current);
					self.current.detach();
					self.current = self.types[val];
				} else {
					$.ajax({
						url: $.ax.rebuildUrl(o.url, {_vmode: $.ax.CONST.VMODE_XFORM_REPLACE}),
						type: 'get',
						data: {selected: val}
					}).success(function(json, status, jqXhr) {
						var $content = $(json).find('[data-wrapper="' + o.wrapper + '"]');

						$content.insertBefore(self.current);
						self.current.detach();
						self.current = $content;
						self.types[val] = $content;
						$content.axForm({isInnerForm: true});
					});
				}
			});
	}
});

})(jQuery);

// closure end
})();