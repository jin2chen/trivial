(function($) {

var MAX_ZINDEX = 10;

var position = function(width, height) {
	var _left = parseInt(Math.random() * width),
		_top = parseInt(Math.random() * height);
	return {left: _left + "px", top: _top + "px"};
}

$.extend($.fn, {
	wishList: function(data, settings) {
		var s = $.extend({
				tpl: "<li></li>",
				width: 100,
				height: 90
			}, settings);
		var $this = $(this);
		var cWidth = $this.innerWidth() - s.width,
			cHeight= $this.innerHeight() - s.height;
		
		$.each($.makeArray(data).reverse(), function(i, json) {
			var tpl = s.tpl;
			$.each(json, function(k, v) {
				tpl = tpl.replace(new RegExp("\\{" + k + "\\}", "g"), v);
			});
			var $wish = $(tpl).css(position(cWidth, cHeight))
						.css({"width": s.width + "px", "height": s.height + "px", "z-index": ++MAX_ZINDEX})
						.appendTo($this)
						.bind("mousedown", function(e) {$(this).css('z-index', ++MAX_ZINDEX)});
		});

		$this.children().draggable({containment: $this, scroll: false});
	}
});

})(jQuery);