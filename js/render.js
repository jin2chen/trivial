(function($, undefined) {
var
jsonEncode = window.JSON.stringify,
jsonDecode = window.JSON.parse,
attributeOrder = ['type', 'id', 'class', 'name', 'value', 'href', 'src', 'action', 'method', 'selected', 'checked', 'readonly', 'disabled', 'multiple', 'size', 'maxlength', 'width', 'height', 'rows', 'cols', 'alt', 'title', 'rel', 'media'],
voidElements = {'area': 1, 'base': 1, 'br': 1, 'col': 1, 'command': 1, 'embed': 1, 'hr': 1, 'img': 1, 'input': 1, 'keygen': 1, 'link': 1, 'meta': 1, 'param': 1, 'source': 1, 'track': 1, 'wbr': 1},
xstring = function(str) {
	if (str === null || str === undefined) {
		str = '';
	} else {
		str = String(str);
	}
	
	return str;
},
ucfirst = function(str) {
	var f = str.charAt(0).toUpperCase();
	return f + str.substr(1);
},
uuid = function() {
	if (uuid.id === undefined || uuid.id > 9999999) {
		uuid.id = 0;
	} else {
		uuid.id += 1;
	}
	
	return uuid.id;
},
isPlainObject = $.isPlainObject,
isEmptyObject = $.isEmptyObject,
isArray = $.isArray,
isFunction = $.isFunction,
extend = $.extend,
inArray = $.inArray,
unique = $.unique,
trim = $.trim,
Html = {
	encode: function(str) {
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		str = div.innerHTML;
		str = str.replace(/"/g, "&quot;");
		str = str.replace(/'/g, "&#039;");
		return str;
	},
	
	decode: function(str) {
		var div = document.createElement('div');
		div.innerHTML = str;
		return div.innerText || div.textContent;
	},
	
	tag: function(name, content, options) {
		var html = '<' + name + (Html.renderTagAttributes(options));
		
		content = content || '';
		if (voidElements[name.toLowerCase()]) {
			html += ' />';
		} else {
			html += '>' + content + '</' + name + '>';
		}
		
		return html;
	},
	
	beginTag: function(name, options) {
		return '<' + name + (Html.renderTagAttributes(options)) + '>'; 
	},
	
	endTag: function(name) {
		return '</' + name + '>';
	},
	
	label: function(content, for_, options) {
		options = extend({}, options);
		options['for'] = for_;
		return Html.tag('label', content, options);
	},
	
	input: function(type, name, value, options) {
		options = extend({}, options);
		options['type'] = type;
		options['name'] = name;
		options['value'] = xstring(value);
		
		return Html.tag('input', '', options);
	},
	
	textarea: function(name, value, options) {
		options = extend({}, options);
		options['name'] = name;
		
		return Html.tag('textarea', Html.encode(value), options);
	},
	
	radio: function(name, checked, options) {
		var hidden = '',
			label, labelOptions, value,
			container, content;
		
		options = extend({}, options);
		options['checked'] = Boolean(checked);
		value = (options['value'] !== undefined) ? options['value'] : '1';
		
		if (options['uncheck'] !== undefined) {
			hidden = Html.input('hidden', name, options['uncheck']);
			delete options['uncheck'];
		}
		if (options['label'] !== undefined) {
			label = options['label'];
			labelOptions = options['labelOptions'] || {};
			Html.addCssClass(labelOptions, 'radio');
			container = options['container'] !== undefined ? options['container'] : false;
			delete options['label'];
			delete options['labelOptions'];
			delete options['container'];
			
			content = Html.label(hidden + Html.input('radio', name, value, options) + label, null, labelOptions);
			if (isPlainObject(container)) {
				return Html.tag('div', content, container);
			} else {
				return content;
			}
		} else {
			return hidden + Html.input('radio', name, value, options);
		}
	},
	
	checkbox: function(name, checked, options) {
		var hidden = '',
			label, labelOptions, value,
			container, content;
		
		options = extend({}, options);
		(typeof checked === 'string') && (/[0-9]+/.test(checked)) && (checked = parseInt(checked, 10));
		options['checked'] = Boolean(checked);
		value = options['value'] !== undefined ? options['value'] : '1';
		
		if (options['uncheck'] !== undefined) {
			hidden = Html.input('hidden', name, options['uncheck']);
			delete options['uncheck'];
		}
		if (options['label'] !== undefined) {
			label = options['label'];
			labelOptions = options['labelOptions'] || {};
			Html.addCssClass(labelOptions, 'checkbox');
			container = options['container'] !== undefined ? options['container'] : false;
			delete options['label'];
			delete options['labelOptions'];
			delete options['container'];
			content = Html.label(hidden + Html.input('checkbox', name, value, options) + label, null, labelOptions);
			if (isPlainObject(container)) {
				return Html.tag('div', content, container);
			} else {
				return content;
			}
		} else {
			return hidden + Html.input('checkbox', name, value, options);
		}
	},
	
	dropDownList: function(name, selection, items, options) {
		var selectOptions;
		
		options = extend({}, options);
		if (options['multiple'] !== undefined) {
			return Html.listBox(name, selection, items, options);
		}
		options['name'] = name;
		selectOptions = Html.renderSelectOptions(selection, items, options);
		return Html.tag('select', selectOptions, options);
	},
	
	listBox: function(name, selection, items, options) {
		var hidden = '',
			selectOptions;
		
		options = extend({}, options);
		if (options['size'] === undefined) {
			options['size'] = 4;
		}
		if (options['multiple'] !== undefined && name.substring(name.length - 2)) {
			name += '[]';
		}
		options['name'] = name;
		if (options['unselect'] !== undefined) {
			if (name.substring(name.length - 2)) {
				name = name.substring(0, name.length - 2);
			}
			hidden = Html.input('hidden', name, options['unselect']);
			delete options['unselect'];
		}
		selectOptions = Html.renderSelectOptions(selection, items, options);
		return hidden + Html.tag('select', selectOptions, options);
	},
	
	checkboxList: function(name, selection, items, options) {
		var hidden = '',
			formatter, itemOptions, encode, 
			lines, index, value, label, checked,
			tag, separator, name2;
		
		options = extend({}, options);
		if (name.substring(name.length - 2)) {
			name += '[]';
		}
		
		formatter = options['item'] !== undefined ? options['item'] : null;
		itemOptions = options['itemOptions'] !== undefined ? options['itemOptions'] : {};
		encode = options['encode'] !== undefined ? Boolean(options['encode']) : true;
		lines = [];
		index = 0;
		for (value in items) {
			label = items[value];
			if (selection === undefined || selection === null) {
				checked = false;
			} else if (typeof selection !== 'object') {
				checked = String(selection) === String(value);
			} else if (isArray(selection)){
				checked = inArray(value, selection) !== -1;
			} else {
				checked = selection[value] !== undefined;
			}
			
			if (formatter !== null) {
				lines.push(formatter(index, label, name, checked, value));
			} else {
				lines.push(Html.checkbox(name, checked, extend(itemOptions, {'value': value, 'label': encode ? Html.encode(label) : label})));
			}
			
			index++;
		}
		
		if (options['unselect'] !== undefined) {
			name2 = name.substring(name.length - 2) === '[]'
				? name.substring(0, name.length - 2) : name;
			hidden = Html.input('hidden', name2, options['unselect']);
		}
		separator = options['separator'] !== undefined ? options['separator'] : '';
		
		tag = options['tag'] !== undefined ? options['tag'] : 'div';
		delete options['tag'];
		delete options['unselect'];
		delete options['encode'];
		delete options['separator'];
		delete options['item'];
		delete options['itemOptions'];
		
		return Html.tag(tag, hidden + lines.join(separator), options);
	},
	
	radioList: function(name, selection, items, options) {
		var hidden = '',
			formatter, itemOptions, encode, 
			lines, index, value, label, checked,
			tag, separator, name2, item;
		
		options = extend({}, options);
		formatter = options['item'] !== undefined ? options['item'] : null;
		itemOptions = options['itemOptions'] !== undefined ? options['itemOptions'] : {};
		encode = options['encode'] !== undefined ? Boolean(options['encode']) : true;
		lines = [];

		for (value in items) {
			if (isArray(items[value])) {
				item = items[value];
				value = item[0];
				label = item[1];
			} else {
				label = items[value];
			}
			
			if (selection === undefined || selection === null) {
				checked = false;
			} else if (typeof selection !== 'object') {
				checked = String(selection) === String(value);
			}
			
			if (formatter !== null) {
				lines.push(formatter(index, label, name, checked, value));
			} else {
				lines.push(Html.radio(name, checked, extend({}, itemOptions, {'value': value, 'label': encode ? Html.encode(label) : label})));
			}
			
			index++;
		}
		
		if (options['unselect'] !== undefined) {
			name2 = name.substring(name.length - 2) === '[]'
				? name.substring(0, name.length - 2) : name;
			hidden = Html.input('hidden', name2, options['unselect']);
		}
		separator = options['separator'] !== undefined ? options['separator'] : '';
		
		tag = options['tag'] !== undefined ? options['tag'] : 'div';
		delete options['tag'];
		delete options['unselect'];
		delete options['encode'];
		delete options['separator'];
		delete options['item'];
		delete options['itemOptions'];
		
		return Html.tag(tag, hidden + lines.join(separator), options);
	},
	
	tr: function(columns, tag, htmlOptions) {
		var cols = [], 
			k, column, defaults, asterisk;
		
		tag = tag || 'td';
		htmlOptions = extend({}, htmlOptions);
		for (k in columns) {
			defaults = {
				'content': '',
				'htmlOptions': {}
			};
			column = extend(defaults, columns[k]);
			if (column['required']) {
				asterisk = '<span class="asterisk">*</span>';
			} else {
				asterisk = '';
			}
			cols.push(Html.tag(tag, asterisk + column['content'], column['htmlOptions']));
		}
		return Html.tag('tr', cols.join(''), htmlOptions);
	},
	
	addCssClass: function(options, class_) {
		var classes = [],
			tmp, i;
		
		tmp = (options['class'] || '') + ' ' + class_;
		tmp = tmp.split(/\s+/);
		for (i in tmp) {
			if (tmp[i] !== '') {
				classes.push(tmp[i]);
			}
		}
		
		classes = unique(classes);
		if (classes.length) {
			options['class'] = classes.join(' ');
		} else {
			delete options['class'];
		}
	},
	
	removeCssClass: function(options, class_) {
		var classes = [],
			tmp, i;
		
		if (options['class'] !== undefined) {
			tmp = options['class'].split(/\s+/);
			for (i in tmp) {
				if (tmp[i] !== '' && tmp[i] !== class_) {
					classes.push(tmp[i]);
				}
			}
			
			if (classes.length) {
				options['class'] = classes.join(' ');
			} else {
				delete options['class'];
			}
		}
	},
	
	renderSelectOptions: function(selection, items, tagOptions) {
		var lines = [],
			prompt, options, groups, content,
			groupAttrs, attrs, key;
		
		if (tagOptions['prompt'] !== undefined) {
			prompt = Html.encode(tagOptions['prompt']).replace(' ', '&nbsp;');
			lines.push(Html.tag('option', prompt, {'value': ''}));
		}
		
		options = tagOptions['options'] !== undefined ? tagOptions['options'] : {};
		groups = tagOptions['groups'] !== undefined ? tagOptions['groups'] : {};
		delete tagOptions['prompt'];
		delete tagOptions['options'];
		delete tagOptions['groups'];
		
		for (key in items) {
			if (isArray(items[key]) || isPlainObject(items[key])) {
				groupAttrs = groups[key] !== undefined ? groups[key] : {};
				groupAttrs['label'] = key;
				attrs = {'options': options, 'groups': groups};
				content = Html.renderSelectOptions(selection, items[key], attrs);
				lines.push(Html.tag('optgroup', content, groupAttrs));
			} else {
				attrs = options[key] !== undefined ? options[key] : {};
				attrs['value'] = String(key);
				if (selection === undefined || selection === null) {
					attrs['selected'] = false;
				} else if (typeof selection !== 'object') {
					attrs['selected'] = String(selection) === String(key);
				} else if (isArray(selection)) {
					attrs['selected'] = inArray(key, selection) !== -1;
				} else {
					attrs['selected'] = selection[key] !== undefined;
				}
				lines.push(Html.tag('option', Html.encode(items[key]).replace(' ', '&nbsp;'), attrs));
			}
		}
		
		return lines.join('');
	},
	
	renderTagAttributes: function(attributes) {
		var sorted = {},
			html = [], 
			i;
		
		attributes = attributes || {};
		for (i in attributeOrder) {
			if (attributes[attributeOrder[i]]) {
				sorted[attributeOrder[i]] = null;
			}
		}
		
		for (i in attributes) {
			sorted[i] = attributes[i];
			
			if (sorted[i] === null || sorted[i] === undefined) {
				delete sorted[i];
			} else if (typeof sorted[i] === 'object') {
				sorted[i] = jsonEncode(sorted[i]);
			}
			
			if (i === 'data-tip' && sorted[i] === '') {
				delete sorted[i];
			} else if (i === 'data-config' && sorted[i] === '{}') {
				delete sorted[i];
			} else if (i === 'data-validate' && sorted[i] === '{}') {
				delete sorted[i];
			}
		}
		
		html = [];
		for (i in sorted) {
			if (typeof sorted[i] === 'boolean') {
				if (sorted[i]) {
					html.push(i);
				}
			} else if (sorted[i] !== null && sorted[i] !== undefined) {
				html.push(i + '="' + (Html.encode(sorted[i])) + '"');
			}
		}
		
		return html.length ? ' ' + html.join(' ') : '';
	}
},

FormRender = function(schema) {
	this.schema = schema || {};
	
};

FormRender.prototype = {
	render: function() {
		if (isEmptyObject(this.schema)) {
			return '';
		}
		
		return this.renderForm();
	},
	
	renderForm: function() {
		var defaults = {
			'fieldsets': [],
			'htmlOptions': {}
			},
			content, title = '',
			form = extend(defaults, this.schema),
			htmlOptions = form['htmlOptions'];
		
		if (htmlOptions['data-dialog'] && htmlOptions['data-dialog']['title']) {
			title = htmlOptions['data-dialog']['title'];
		}
		
		htmlOptions['data-form-info'] = {
			'action': htmlOptions['action'],
			'title': title
		};
		Html.addCssClass(htmlOptions, 'xform form-horizontal fill-up');
		content = this.renderFieldsets(form['fieldsets']);
		return Html.tag('form', content, htmlOptions);
	},
	
	renderFieldsets: function(fieldsets) {
		if (isEmptyObject(fieldsets)) {
			return '';
		} else if (!isArray(fieldsets)) {
			console.error('The fieldsets is not []');
			console.dir(fieldsets);
			return '';
		}
		
		var groups = {},
			sections = [],
			navs,
			panels,
			i, k, kk, tmp,
			fieldset, idPrefix, method,
			tools, fieldsetClass;
		
		i = 0;
		for (k in fieldsets) {
			fieldset = fieldsets[k];
			if (!isEmptyObject(fieldset)) {
				if (fieldset['type'] === undefined) {
					fieldset['type'] = 'normal';
				}
				if (fieldset['group'] === undefined) {
					fieldset['group'] = '_' + (++i) + '_';
				}
				if (groups[fieldset['group']] === undefined) {
					groups[fieldset['group']] = [];
				}
				groups[fieldset['group']].push(fieldset);
			}
		}
		
		for (k in groups) {
			idPrefix = 'xform-fieldset-tabs-' + uuid() + '-';
			navs = [];
			panels = [];
			for (kk in groups[k]) {
				fieldset = groups[k][kk];
				method = this['createFieldset' + ucfirst(fieldset['type'])];
				if (!isFunction(method)) {
					method = 'createFieldset' + ucfirst(fieldset['type']);
					console.error('Method `' + method + '` is not implement.');
					continue;
				} else {
					tmp = method.call(this, fieldset, idPrefix + kk);
					navs.push(tmp['nav']);
					panels.push(tmp['panel']);
				}
			}
			
			if (navs.length) {
				fieldsetClass = navs.length > 1 ? 'box form-tabs' : 'box';
				navs = Html.tag('ul', navs.join(''), {'class': 'nav nav-tabs nav-tabs-left'});
				tools = '<ul class="box-toolbar"><li class="toolbar-link"><a class="icon-minus"></a></li></ul>';
				navs = Html.tag('div', navs + tools, {'class': 'box-header'});
				panels = Html.tag('div', panels.join(''), {'class': 'fieldset-content box-content padded'});
				sections.push(Html.tag('fieldset', navs + panels, {'class': fieldsetClass}));
			}
		}
		
		return sections.join('');
	},
	
	renderRows: function(rows) {
		if(isEmptyObject(rows)) {
			return '';
		} else if (!isArray(rows)) {
			console.error('The rows is not []');
			console.dir(rows);
			return '';
		}
		
		var html = [],
			k, row, method;
		for (k in rows) {
			row = rows[k];
			if (isEmptyObject(row)) {
				continue;
			}
			
			if (row['type'] === undefined) {
				row['type'] = 'normal';
			}
			method = this['createRow' + ucfirst(row['type'])];
			if (!isFunction(method)) {
				method = 'createRow' + ucfirst(row['type']);
				console.error('Method `' + method + '` is not implement.');
				continue;
			} else {
				html.push(method.call(this, row));
			}
		}
		
		return html.join('');
	},
	
	renderElements: function(elements) {
		if (isEmptyObject(elements)) {
			return '';
		} else if (!isArray(elements)) {
			console.error('The elements is not []');
			console.dir(elements);
			return '';
		}
		
		var html = [],
			k, element, method;
		for (k in elements) {
			element = elements[k];
			if (element['type'] === undefined) {
				console.error('The type of element is not defined');
				console.dir(element);
				continue;
			}

			method = this['create' + ucfirst(element['type'])];
			if (!isFunction(method)) {
				method = 'create' + ucfirst(element['type']);
				console.error('Method `' + method + '` is not implement.');
				console.dir(element);
				continue;
			} else {
				html.push(method.call(this, element));
			}
		}
		
		return html.join('');
	},
	
	createFieldsetNormal: function(fieldset, id, active) {
		var defaults = {
			'title': '',
			'rows': [],
			'htmlOptions': {}
			},
			rows;
		
		active = active || '0';
		fieldset = extend(defaults, fieldset);
		rows = this.renderRows(fieldset['rows']);
		return {
			'nav': '<li><a href="#' + id + '">' + fieldset['title'] + '</a></li>', 
			'panel': '<div id="' + id + '">' + rows + '</div>'
		};
	},
	createFieldsetForm: function() {},
	
	createRowNormal: function(row) {
		var defaults = {
			'label': {},
			'elements': [],
			'htmlOptions': {}
			},
			labelDefaults = {
			'text': '',
			'required': false,
			'htmlOptions': {}
			},
			cells = [],
			label, labelOptions, rowOptions,
			prepend, append;
			
		row = extend(defaults, row);
		rowOptions = row['htmlOptions'];
		label = extend(labelDefaults, row['label']);
		labelOptions = label['htmlOptions'];
		Html.addCssClass(labelOptions, 'control-label');
		Html.addCssClass(rowOptions, 'box-row');
		prepend = '';
		append = '';
		
		if (label['required']) {
			prepend = '<span>*<span>';
		}
		if (label['element'] !== undefined) {
			append = this.renderElements([label['element']]);
		}
		cells.push(Html.tag('label', prepend + label['text'] + append, labelOptions));
		cells.push('<div class="controls"><div class="controls-inputs">' + (this.renderElements(row['elements'])) + '</div></div>');
		return Html.tag('div', '<div class="control-group">' + (cells.join('')) + '</div>', rowOptions);
	},
	
	createRowWrapper: function(row) {
		var defaults = {
			'detail': false,
			'rows': [],
			'htmlOptions': {}
			};
			
		row = extend(defaults, row);
		if (isEmptyObject(row)) {
			return '';
		}
		
		if (row['detail']) {
			Html.addCssClass(row['htmlOptions'], 'box-row-wrapper box-row-wrapper-details hide');
		} else {
			Html.addCssClass(row['htmlOptions'], 'box-row-wrapper box-row-wrapper-normal');
		}
		
		return Html.tag('div', this.renderRows(row['rows']), row['htmlOptions']);
	},
	
	createText: function(element) {
		var defaults = {
			'name': '',
			'value': '',
			'htmlOptions': {}
			},
			label = false, 
			htmlOptions, html;
			
		element = extend(defaults, element);
		htmlOptions = element['htmlOptions'];
		if (htmlOptions['label'] !== undefined) {
			label = htmlOptions['label'];
			delete htmlOptions['label'];
		}
		html = Html.input('text', element['name'], element['value'], htmlOptions);
		if (label !== false) {
			html = Html.tag('label', html + label, {'class': 'text'});
		}
		
		return html;
	},
	
	createSpinner: function(element) {
		var defaults = {
			'name': '',
			'value': '',
			'htmlOptions': {},
			'data-config': {}
			},
			jsOptions = {},
			label = false, 
			validators, htmlOptions, html;
			
		element = extend(defaults, element);
		htmlOptions = element['htmlOptions'];
		if (htmlOptions['label'] !== undefined) {
			label = htmlOptions['label'];
			delete htmlOptions['label'];
		}
		
		if (htmlOptions['data-validate']) {
			validators = htmlOptions['data-validate'];
			if (validators['range']) {
				jsOptions['min'] = validators['range'][0];
				jsOptions['max'] = validators['range'][1];
				jsOptions['step'] = Math.ceil((validators['range'][1] - validators['range'][0]) / 100);
			}
		}
		if (jsOptions['min'] === undefined) {
			jsOptions['min'] = 0;
		}
		
		htmlOptions['data-config']['axSpinner'] = jsOptions;
		Html.addCssClass(htmlOptions, 'spinner');
		html = Html.input('text', element['name'], element['value'], htmlOptions);
		if (label !== false) {
			html = Html.tag('label', html, {'class': 'text'});
		}
		
		return html;
	},
	
	createPassword: function(element) {
		var defaults = {
			'name': '',
			'value': '',
			'htmlOptions': {}
			};
			
		element = extend(defaults, element);
		return Html.input('password', element['name'], element['value'], element['htmlOptions']);
	},
	
	createTextArea: function(element) {
		var defaults = {
			'name': '',
			'value': '',
			'htmlOptions': {}
			};
			
		element = extend(defaults, element);
		return Html.textarea(element['name'], element['value'], element['htmlOptions']);
	},
	
	createCheckbox: function(element) {
		var defaults = {
			'name': '',
			'value': false,
			'htmlOptions': {}
			};
			
		element = extend(defaults, element);
		return Html.checkbox(element['name'], element['value'], element['htmlOptions']);
	},
	
	createCheckboxList: function(element) {
		var defaults = {
			'name': '',
			'value': null,
			'data': {},
			'htmlOptions': {}
			};
			
		element = extend(defaults, element);
		return Html.checkboxList(element['name'], element['value'], element['data'], element['htmlOptions']);
	},
	
	createRadio: function(element) {
		var defaults = {
			'name': '',
			'value': false,
			'htmlOptions': {}
			};
			
		element = extend(defaults, element);
		return Html.radio(element['name'], element['value'], element['htmlOptions']);
	},
	
	createRadioList: function(element) {
		var defaults = {
			'name': '',
			'value': null,
			'data': {},
			'htmlOptions': {}
			};
			
		element = extend(defaults, element);
		return Html.radioList(element['name'], element['value'], element['data'], element['htmlOptions']);
	},
	
	createRadioSwitch: function(element) {
		var defaults = {
			'name': '',
			'value': null,
			'data': [['1', 'Enabled'], ['0', 'Disabled']],
			'htmlOptions': {}
			};
			
		element = extend(defaults, element);
		return Html.radioList(element['name'], element['value'], element['data'], element['htmlOptions']);
	},
	
	createRevRadioSwitch: function(element) {
		var defaults = {
			'name': '',
			'value': null,
			'data': {'0': 'Enabled', '1': 'Disabled'},
			'htmlOptions': {}
			};
		
		element = extend(defaults, element);
		return Html.radioList(element['name'], element['value'], element['data'], element['htmlOptions']);
	},
	
	createSelect: function(element) {
		var defaults = {
			'name': '',
			'value': null,
			'data': {},
			'htmlOptions': {}
			};
			
		element = extend(defaults, element);
		return Html.dropDownList(element['name'], element['value'], element['data'], element['htmlOptions']);
	},
	
	createCombobox: function(element) {
		console.error('Not implement');
		return '';
	},
	
	createHidden: function(element) {
		var defaults = {
			'name': '',
			'value': '',
			'htmlOptions': {}
			};
			
		element = extend(defaults, element);
		return Html.input('hidden', element['name'], element['value'], element['htmlOptions']);
	},
	
	createFile: function(element) {
		var defaults = {
			'name': '',
			'value': '',
			'htmlOptions': {}
			};
			
		element = extend(defaults, element);
		return Html.input('file', element['name'], element['value'], element['htmlOptions']);
	},
	
	createWrapper: function(element) {
		var defaults = {
				'tag': 'div',
				'elements': [],
				'htmlOptions': {}
			},
			html;
			
		element = extend(defaults, element);
		if (isEmptyObject(element['elements'])) {
			return '';
		}
		
		html = this.renderElements(element['elements']);
		Html.addCssClass(element['htmlOptions'], 'wrapper-elements');
		return Html.tag(element['tag'], html, element['htmlOptions']);
	},
	
	createTag: function(element) {
		var defaults = {
			'tag': 'span',
			'label': null,
			'htmlOptions': {}
			};
			
		element = extend(defaults, element);
		return Html.tag(element['tag'], element['label'], element['htmlOptions']);
	},
	
	createCustom: function(element) {
		var defaults = {
				'content': ''
			};
			
		element = extend(defaults, element);
		return element['content'];
	},
	
	createTableList: function(element) {
		var defaults = {
			'name': '',
			'schema': {},
			'scenario': 'insert',
			'data': []
			},
			schemeDefaults = {
				'type': 'simple',
				'jsOptions': {
					'structure': {}, 
					'primary': [],
					'skinData': {
						'columns': []
					}
				},
				'columns': [],
				'htmlOptions': {
					'data-config': {}
				}
			},
			self = this,
			headers = [],
			fields = [],
			schema, htmlOptions, jsOptions,
			table, tbody, thead, html,
			column, field, widgetName,
			k, i, _element;
		
		element = extend(defaults, element);
		schema = extend(true, schemeDefaults, element['schema']);
		htmlOptions = schema['htmlOptions'];
		jsOptions = schema['jsOptions'];
		
		headers.push({
			'htmlOptions': {'class': 'selectall-cell', 'width': '30'},
			'content': '<input type="checkbox" class="selectall" />'
		});
		fields.push({
			'htmlOptions': {'class': 'pk-cell'},
			'content': '<input type="checkbox" class="pk" />'
		});

		for (k in schema['columns']) {
			column = schema['columns'][k];
			if (isEmptyObject(column)) {
				continue;
			}
			
			field = {'htmlOptions': {'nofield': 'true'}, 'elements': []};
			field = extend(true, field, column['field']);
			
			for (i in field['elements']) {
				_element = {'htmlOptions': {}};
				_element = extend(true, _element, field['elements'][i]);
				
				if (_element['htmlOptions']['primary']) {
					jsOptions['primary'].push(_element['name']);
					_element['htmlOptions']['data-validate'] = _element['htmlOptions']['data-validate'] || {};
					_element['htmlOptions']['data-validate']['tableListUnique'] = true;
				}
				
				jsOptions['structure'][_element['name']] = _element['type'];
				field['elements'][i] = _element;
			}
			
			//jsOptions['skinData']['columns'].push(Boolean(column['display']));
			headers.push(column['header']);
			fields.push({
				'htmlOptions': field['htmlOptions'],
				'content': self.renderElements(field['elements'])
			});
		}
		
		if (schema['type'] === 'simple') {
			widgetName = 'axFormSpTableList';
			Html.addCssClass(htmlOptions, 'js-form-pre-serialize editable');
		} else {
			widgetName = 'axFormAdTableList';
			Html.addCssClass(htmlOptions, 'js-form-pre-serialize editable advance');
		}
		
		headers.push({
			'htmlOptions': {'class': 'actions'},
			'content': 'Action'
		});
		fields.push({
			'htmlOptions': {},
			'content': ''
		});
		
		thead = Html.tag('thead', Html.tr(headers, 'th'), {});
		tbody = Html.tag('tbody');
		table = Html.tag('table', thead + tbody, {});
		
		jsOptions['editTpl'] = Html.tr(fields, 'td');
		jsOptions['data'] = element['data'];
		jsOptions['name'] = element['name'];
		jsOptions['scenario'] = element['scenario'];
		htmlOptions['data-config'][widgetName] = jsOptions;
		html = '<div class="editable-list"><div class="search-box"></div><div class="functional-buttons"></div></div><div class="editable-table">'
			+ table + '</div>';
		return Html.tag('div', html, htmlOptions);
	},
	
	createIpMask: function(element) {
		console.error('Not implement');
		return '';
	}
};

window.Html = Html;
window.FormRender = FormRender;
window.render = new FormRender();
})(jQuery);