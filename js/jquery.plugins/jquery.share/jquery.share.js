/**
 * Share
 *
 * @author mole <mole.chen@foxmail.com>
 * @version $Id: jquery.share.js 171 2011-09-22 07:40:26Z mole1230 $
 */
(function($) {

var cc = function(a, b, c, d) {
	return {
		name: a,
		alias: b,
		url: c,
		pos: d
	};
};

var parseUrl = function(c, s) {
	var d = s.title,
		e = s.url,
		f = s.content,
		g = s.picture,
		a = c.name,
		z = s.appKey[c.name] || "",
		h, i, k, j;
	i = " - " + f;
	if (f == null || d.indexOf(f) != -1 || f.length <= 1) {
		i = "";
	}
	if ("t163" == a) {
		h = encodeURIComponent(d + i + " " + e);
	} else if ("tsina" == a || "tsohu" == a || "tqq" == a) {
		h = encodeURIComponent(d + i);
	} else {
		h = encodeURIComponent(d);
	}
	j = encodeURIComponent(f);
	k = c.url.replace("{0}", encodeURIComponent(e))
		.replace("{1}", h).replace("{2}", j)
		.replace("{3}", encodeURIComponent(g))
		.replace("{-1}", z);

	return k;
};

$.ShareTo = function() {};
$.ShareTo.shares = {
	1: cc("tsohu", "搜狐微博", "http://t.sohu.com/third/post.jsp?&url={0}&title={1}&content=utf-8&pic={3}", 3),
	2: cc("tsina", "新浪微博", "http://service.weibo.com/share/share.php?url={0}&source=bookmark&title={1}&appkey={-1}&pic={3}", 1),
	3: cc("tqq", "腾讯微博", "http://v.t.qq.com/share/share.php?title={1}&url={0}&pic={3}", 2),
	4: cc("t163", "网易微博", "http://t.163.com/article/user/checkLogin.do?source=Passit&info={1}&pic={3}", 4),
	5: cc("qqkj", "QQ空间", "http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url={0}&title={1}", 9),
	6: cc("rrw", "人人网", "http://share.renren.com/share/buttonshare.do?link={0}&title={1}", 7),
	7: cc("kxw", "开心网", "http://www.kaixin001.com/repaste/bshare.php?rtitle={1}&rurl={0}&rcontent={2}", 8)
};
$.ShareTo.defaults = {
	showIndex: [1, 2, 3, 4, 5, 6, 7],
	imgUrl: "share.png",
	imgSize: 16,
	liMargin: 4,
	url: document.location.href,
	title: document.title,
	content: (function(){
		var s = []; 
		$("meta").each(function(){
			var $this = $(this);
			if ($this.attr("name").toLowerCase() == "description") {
				s.push($this.attr("content"));
			}
		});

		return s.join("-");
	})(),
	picture: "",
	appKey: {
		tsina: "",
		tqq: ""
	}
};
$.fn.shareTo = function(options) {
	var s = $.extend(true, {}, $.ShareTo.defaults, options || {}),
		html, o,
		i, k, l = s.showIndex.length;

	html = '<div><span style="float:left;display:inline-block;">分享：</span>';
	for(i = 0; i < l; i++) {
		k = s.showIndex[i];
		o = $.ShareTo.shares[k];
		if (!o) {
			continue;
		}

		html+= '<a target="_blank" href="' + parseUrl(o, s) + '" style="width:' 
			+ s.imgSize + 'px;overflow:hidden;height:' + s.imgSize + 'px;display:inline-block;margin:0 ' 
			+ s.liMargin +'px 0 0;padding:0;background: transparent url(' + s.imgUrl 
			+ ') no-repeat -' + s.imgSize + 'px -' + (s.imgSize) * o.pos + 'px" title="分享到' + o.alias+'"></a>';
	}
	html += '</div>';
	$(this).html(html).find("a").hover(function(){
		var $this = $(this), y, a;
		if (!!$this.css("background-position")) {
			a = $this.css("background-position").split(" ");
			y = parseFloat(a[1]);
			$this.css({"background-position": "0px " + y + "px"});
		} else {
			$this.css({"background-position-x": "0px"});
		}
	}, function(){
		var $this = $(this), y, a;
		if (!!$this.css("background-position")) {
			a = $this.css("background-position").split(" ");
			y = parseFloat(a[1]);
			$this.css({"background-position": "-" + s.imgSize + "px " + y + "px"});
		} else {
			$this.css({"background-position-x": "-" + s.imgSize + "px"});
		}
	});
};
}(jQuery));