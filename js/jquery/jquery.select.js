/**
 * @preserve NO COPYRIGHTS OR LICENSES. DO WHAT YOU LIKE.
 * @author mole <mole1230@gmail.com>
 */
(function($){

$.RelateSelect = function(element, options) {
	var self = this, s;
	this.settings = $.extend(true, {}, $.RelateSelect.defaults, options);
	this.selectors = {};
	this.parents = {};
	this.container = $(element);
	this.createSelect(0);
	
};
$.extend($.RelateSelect, {
	prototype: {
		createSelect: function(deepth) {
			var s = this.settings, isNew = false, self = this;
		
			// collect data
			var data = s.data;
			if (deepth > 0) {
				for (var i = 0; i < deepth; i++) {
					var $option = this.selectors[i].children(":selected"),
						key = $option.text() + ',' + $option.val();
					data = data[key];
				}
			} 
			
			// create select
			var $select;
			if (!this.selectors[deepth]) {
				$select = $("<select/>", {
					"name": s.names[deepth],
					"id": s.ids[deepth],
					"class": s.classes[deepth]
				});
				
				if (!$.isArray(data)) {
					$select.bind("change", {deepth: deepth + 1}, function(e) {
						self.createSelect(e.data.deepth);
					});
				}
				
				this.selectors[deepth] = $select;
				isNew = true;
			} else {
				$select = this.selectors[deepth];
			}
			
			if (isNew) {
				$select.append($.RelateSelect.createOptionsHtml(data))
					.val(s.selecteds[deepth] || -1000)
					.appendTo(this.container)
					.trigger("change");
			} else {
				$select.empty()
					.append($.RelateSelect.createOptionsHtml(data)).trigger("change");
			}
		}
	},
	
	defaults: {
		data: {},
		names: [],
		selecteds: {},
		ids: {},
		classes: {}
	},

	createOptionsHtml: function(data) {
		data = $.RelateSelect.getData(data);
	
		var options = [];
		for (var i = 0; data[i]; i++) {
			var tmp = data[i].split(",");
			options.push(["<option value=\"", tmp[1], "\">", tmp[0], "</option>"].join(""));
		}
		
		return options.join("");
	},
	
	getData: function(data) {
		var tmp = [];
		if (typeof data === "object" && !$.isArray(data)) {
			for (var k in data) {
				tmp.push(k);
			}
		} else if ($.isArray(data)) {
			tmp = data;
		} else {
			tmp = [];
		}
		
		return tmp;
	}
});

$.fn.relateSelect = function(options) {
	if (this.data("RelateSelect")) { 
		return this; 
	}
	if (this.length > 0) {
		$.data(this[0], 'RelateSelect', new $.RelateSelect(this[0], options));
	}
};

})(jQuery);