/**
 * POP select
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: jquery.pop.js 171 2011-09-22 07:40:26Z mole1230 $
 */
(function($) {

$.PopSelect = function(element, options) {
	var s = $.extend({}, $.PopSelect.defaults, options || {});

	this.settings = s;
	this.element = element;
	this.selects = [];
	this.pops = [];
	this.depth = $(element).find(s.selector).length;
	this.init().create(0);
};

$.extend($.PopSelect, {
	prototype: {
		init: function() {
			var self = this, s = this.settings;
			
			$(self.element).find(s.selector).each(function(i) {
				var $this = $(this),
					$pop;
				
				// Create pop layer
				$pop = $("<div/>").hide()
					.addClass(s.popClass)
					.css("position", "absolute")
					.appendTo(document.body);

				$pop.data("POP_DATA", {
					popSelect: self, 
					container: self.element, 
					target: this, 
					depth: i, 
					defaultLabel: $this.find(s.labelTag).text()
				});
				self.selects.push(this);
				self.pops.push($pop[0]);
				
				// For document click
				$.PopSelect.pops = $.PopSelect.pops || $([]);
				$.PopSelect.pops = $.PopSelect.pops.add($pop);

				$pop.click(function(e) {
					var $this = $(this),
						$target = $(e.target),
						$selected = $this.data("POP_SELECTED") || $([]),
						id = $target.data("id"),
						label = $target.text(),
						popData = $this.data("POP_DATA"),
						i;

					if (e.target.tagName.toUpperCase() == s.itemTag.toUpperCase() 
					&& id != s.selected[popData.depth]) {
						s.selected[popData.depth] = id;
						self.setLabel(popData.depth, id ? label : popData.defaultLabel);
						$selected.removeClass(s.selectedClass);
						$target.addClass(s.selectedClass);
						$this.data("POP_SELECTED", $target);

						for (i = popData.depth + 1; i < self.depth; i++) {
							s.selected[i] = "";
						}
						$(self.selects[popData.depth]).trigger("change");
						if ($.isFunction(s.onSelect[popData.depth])) {
							s.onSelect[popData.depth].apply(self);
						}
					}
				});

				// Bind event
				$this.bind("click", {pop: $pop}, function(e) {
					var $this = $(this),
						offset = $this.offset();
					e.data.pop.css({
						top: offset.top + $this.outerHeight(),
						left: offset.left
					});
					
					$.PopSelect.hide(this);
					return false;
				});
			});

			return self;
		},
		
		create: function(depth) {
			var self = this, s = this.settings,
				data = s.data[depth],
				$select = $(self.selects[depth]),
				i;

			// Collect data
			if (depth > 0 && data) {
				for (i = 0; i < depth; i++) {
					data = data[s.selected[i]];
					if (!data) {
						break;
					}
				}
			}

			if (depth < self.depth - 1) {
				$select.bind("change", {depth: depth + 1}, function(e) {
					self.create(e.data.depth);
				}).trigger("change");
			}

			if (parseInt(s.selected[depth], 10)) {
				self.setLabel(depth, data[s.selected[depth]]);
			} else {
				self.setLabel(depth);
			}

			$(self.pops[depth]).empty().append(self.createItems(data, depth));
		},
		
		createItems: function(data, depth) {
			var self = this, s = this.settings, $item, i, items = [], selected = s.selected[depth];
			if (!data) {
				return "";
			}

			for (i in data) {
				$item = $("<" + s.itemTag + "/>").data("id", i).text(data[i]);
				if ((parseInt(selected, 10) || 0) == i) {
					$item.addClass(s.selectedClass);
					$(self.pops[depth]).data("POP_SELECTED", $item);
				}
				items.push($item[0]);
			}
		
			return $(items);
		},

		setLabel: function(depth, text) {
			var self = this, s = this.settings;
			if (typeof text === "undefined") {
				var popData = $(self.pops[depth]).data("POP_DATA");
				text = popData.defaultLabel;
			}
			$(self.selects[depth]).find(s.labelTag).text(text);
		}
	},
	defaults: {
		data: [],
		selected: [],
		onSelect: [],
		selector: "a",
		itemTag: "a",
		labelTag: "label",
		popClass: "pop-layer",
		selectedClass: "current"
	},

	hide: function(target) {
		if ($.PopSelect.pops) {
			$.PopSelect.pops.each(function() {
				var $this = $(this),
					popData = $this.data("POP_DATA") || {};
				if (target == popData.target && $this.children().size() > 0) {
					$(this).fadeIn();
				} else {
					$(this).hide();
				}
			});
		}
	}
});

$.fn.popSelect = function(options) {
	this.each(function() {
		var $this = $(this);
		if (!$this.data("popSelect")) {
			$this.data("popSelect", new $.PopSelect(this, options));
		}
	});
	return this;
};
$(document).click(function(e) {
	$.PopSelect.hide(e.target);
});
}(jQuery));