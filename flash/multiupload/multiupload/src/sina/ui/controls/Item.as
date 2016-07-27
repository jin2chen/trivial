package sina.ui.controls 
{
	import com.adobe.serialization.json.JSON;
	import com.adobe.serialization.json.JSONParseError;
	import flash.display.Bitmap;
	import flash.display.BitmapData;
	import flash.display.GradientType;
	import flash.display.Graphics;
	import flash.display.Loader;
	import flash.display.Sprite;
	import flash.events.DataEvent;
	import flash.events.Event;
	import flash.events.HTTPStatusEvent;
	import flash.events.IOErrorEvent;
	import flash.events.MouseEvent;
	import flash.events.ProgressEvent;
	import flash.events.SecurityErrorEvent;
	import flash.geom.Matrix;
	import flash.net.FileReference;
	import flash.net.URLLoader;
	import flash.net.URLRequest;
	import flash.net.URLRequestMethod;
	import flash.net.URLVariables;
	import flash.text.TextField;
	import flash.text.TextFieldAutoSize;
	import flash.text.TextFormat;
	import flash.utils.*;
	import nl.demonsters.debugger.MonsterDebugger;
	import sina.ui.events.ItemEvent;
	import sina.ui.skin.AllSkins;
	import sina.ui.skin.DeleteIcon;
	import sina.ui.skin.UploadedIconComplete;
	import sina.ui.skin.UploadedIconError;
	
	/**
	 * @author     mole<mole1230@gmail.com>
	 * @version    $Id: Item.as 7 2010-10-19 10:38:30Z mole1230 $
	 */
	public class Item extends Sprite
	{
		public static const WIDTH:Number = 96;
		public static const HEIGHT:Number = 96;
		public static const ROTATE_CW:String = "rotate_cw";
		public static const ROTATE_ACW:String = "rotate_acw"; 
		public static const UPLOAD_STATUS_NORMAL:String = "upload_status_normal";
		public static const UPLOAD_STATUS_SIZE_ERROR:String = "upload_status_size_error";
		public static const UPLOAD_STATUS_PRELOAD_ERROR:String = "upload_status_preload_error";
		public static const UPLOAD_STATUS_FAILURE:String = "upload_status_failure";
		public static const UPLOAD_STATUS_SUCCESS:String = "upload_status_success";
		public static const UPLOAD_STATUS_UPLOADING:String = "upload_status_uploading";
		public static const UPLOAD_STATUS_WAITING:String = "upload_status_waiting";
		private const AVIABLE_STATUS_NORMAL:String = "aviable_status_normal";
		private const AVIABLE_STATUS_SIZE_ERROR:String = "aviable_status_size_error";
		private const AVIABLE_STATUS_PRELOAD_ERROR:String = "aviable_status_preload_failure"
		
		private const COLOR_BORDER:uint = 0xB4B4B4;
		private const COLOR_FILL:uint = 0xFFFFFF;
		private const COLOR_FILL_GRAY:uint = 0xE7E7E7;
		private const SELECT_MASK_Y:Number = 2;

		private var _fp:int = 10;
		private var _maxSize:Number = 3;
		private var _uploadUrl:String = "";
		private var _index:int
		private var _delay:Number;
		private var _fileReference:FileReference;	
		private var _fileUrlLoader:URLLoader;
		private var _percent:Number = 0;
		private var _requestVariables:Object = { };
		private var _logFailTime:String = "0";
		private var _json:Object = { };
		
		private var _preloadProgress:Sprite;
		private var _previewLoader:Loader;
		private var _imgWidth:Number = 0;
		private var _imgHeight:Number = 0;
		private var _previewerBitMap:Bitmap;
		private var _preloaded:Boolean = false;
		private var _rotationAngle:Number = 0;
		
		private var _waitMask:Sprite;
		private var _errorMask:Sprite;
		private var _errorMaskText:TextField;
		private var _available:String = AVIABLE_STATUS_NORMAL;
		private var _selected:Boolean = false;
		private var _selectMask:Sprite;
		private var _deleteIcon:Sprite;
		private var _closewiseBtn:Button;
		private var _anticlosewiseBtn:Button;
		private var _progressBar:Sprite;
		private var _skins:AllSkins = new AllSkins();
		
		private var _uploadStatus:String = UPLOAD_STATUS_NORMAL;
		
		public static function isValidPreviewFileType(type:String):Boolean
		{
			var types:Array = [".jpg", ".jpeg", ".gif", ".png"];
			return (types.indexOf(type.toLowerCase()) > -1);
		}

		public function Item() 
		{
			_addBg();
		}
		
		public function starPreload():void
		{
			if (_preloaded) {
				dispatchEvent(new Event(ItemEvent.PRELOAD_COMPLETE, true));
				return;
			}
			
			if (_fileReference.size > _maxSize * 1024 * 1024) {
				_addErrorMask("图片超过" + _maxSize + "M");
				_preloaded = true;
				_available = AVIABLE_STATUS_SIZE_ERROR;
				dispatchEvent(new Event(ItemEvent.PRELOAD_COMPLETE, true));
			} else {
				if (_fp >= 10 && isValidPreviewFileType(_fileReference.type)) {
					try {
						_fileReference.load();
					} catch (e:Error) {
						_preloaded = true;
						_available = AVIABLE_STATUS_PRELOAD_ERROR;
						_noPreview();
						dispatchEvent(new Event(ItemEvent.PRELOAD_COMPLETE, true));
					}
				} else {
					_preloaded = true;
					_available = AVIABLE_STATUS_PRELOAD_ERROR;
					_noPreview();
					dispatchEvent(new Event(ItemEvent.PRELOAD_COMPLETE, true));
				}
			}
		}
		
		public function startUpload():void
		{
			dispatchEvent(new Event(ItemEvent.UPLOAD_START, true));
			if (_available == AVIABLE_STATUS_SIZE_ERROR) {
				uploadStatus = UPLOAD_STATUS_SIZE_ERROR;
				dispatchEvent(new Event(ItemEvent.UPLOAD_SIZE_ERROR, true));
				dispatchEvent(new Event(ItemEvent.UPLOAD_COMPLETE, true));
			} else {
				_uploadStatus = UPLOAD_STATUS_UPLOADING;
				if (_fp >= 10) {
					contains(_waitMask) && removeChild(_waitMask);
				} else {
					_addWaitMask("正在上传...");
				}
				
				var request:URLRequest = new URLRequest(_uploadUrl);
				var data:URLVariables = new URLVariables();
				for (var key:String in _requestVariables) {
					data[key] = _requestVariables[key];
				}
				data["rotate"] = _rotationAngle;
				request.method = URLRequestMethod.POST;
				request.data = data;
				
				_fileReference.upload(request);
			}
		}
		
		public function pauseUpload():void
		{
			_fileReference.cancel();
			_progressBar.scaleX = 0;
			uploadStatus = UPLOAD_STATUS_WAITING;
			
			_addWaitMask("等待上传");
		}
		
		public function rotationImg(type:String = ROTATE_CW):void
		{
			// gif 图片暂不可以旋转
			if (_fp >= 10 && isValidPreviewFileType(_fileReference.type) 
			  && _fileReference.type != ".gif"
			  && _available == AVIABLE_STATUS_NORMAL) {
				if (type == ROTATE_CW) {
					_previewerBitMap.rotation += 90;
				} else {
					_previewerBitMap.rotation -= 90;
				}
				
				var x:Number = 0;
				var y:Number = 0;
				switch (_previewerBitMap.rotation) {
					case 90:
						x = (WIDTH + _previewerBitMap.width) / 2;
						y = (HEIGHT - _previewerBitMap.height) / 2;
						_rotationAngle = 90;
						break;
					case -90:
						x = (WIDTH - _previewerBitMap.width) / 2;
						y = (HEIGHT + _previewerBitMap.height) / 2;
						_rotationAngle = -90;
						break;
					case 180:
					case -180:
						x = (WIDTH + _previewerBitMap.width) / 2;
						y = (HEIGHT + _previewerBitMap.height) / 2;
						_rotationAngle = 180;
						break;
					default:
						x = (WIDTH - _previewerBitMap.width) / 2;
						y = (HEIGHT - _previewerBitMap.height) / 2;
						_rotationAngle = 0;
						break;
				}
				_previewerBitMap.x = x;
				_previewerBitMap.y = y;
			}
		}
		
		private function _init():void
		{
			_addSelectmaskIconsProgressbar();
			_addTooltips();
			addEventListener(MouseEvent.ROLL_OVER, _itemEvent);
			addEventListener(MouseEvent.ROLL_OUT, _itemEvent);
			addEventListener(MouseEvent.CLICK, _itemEvent);
		}
		
		private function _itemEvent(e:MouseEvent):void
		{
			if (!_preloaded) {
				return;
			}
	
			switch (e.type) {
				case MouseEvent.ROLL_OVER:
					if (!e.buttonDown && 
					  (_uploadStatus == UPLOAD_STATUS_NORMAL || _uploadStatus == UPLOAD_STATUS_WAITING)) {
						  
						if (!_selected) {
							addChild(_selectMask);
							_selectMask.visible = true;
							_selectMask.alpha = 0.5;
						}
						
						addChild(_deleteIcon);
						_deleteIcon.visible = true;
						if (_closewiseBtn) {
							addChild(_closewiseBtn);
							addChild(_anticlosewiseBtn);
							_closewiseBtn.visible = true;
							_anticlosewiseBtn.visible = true;
						}
					}
					break;
				case MouseEvent.ROLL_OUT:
					if (!_selected) {
						_selectMask.visible = false;
					}
					_deleteIcon.visible = false;
					if (_closewiseBtn) {
						_closewiseBtn.visible = false;
						_anticlosewiseBtn.visible = false;
					}
					break;
				case MouseEvent.CLICK:
					if ((_uploadStatus == UPLOAD_STATUS_NORMAL || _uploadStatus == UPLOAD_STATUS_WAITING)
					  && !(e.target is Button) && e.target != _deleteIcon) {
							if (e.ctrlKey) {
								selected = !selected;
								dispatchEvent(new Event(ItemEvent.SELECT_CHANGED, true));
							} else {
								selected = true;
								dispatchEvent(new Event(ItemEvent.UNSELECT_OTHERS, true));
							}
					}
					break;
				case MouseEvent.MOUSE_DOWN:
					break;
				default:
					break;
			}
		}
		
		private function _addTooltips():void
		{
			
		}
		
		private function _addSelectmaskIconsProgressbar():void
		{
			var graphic:Graphics;
			_selectMask = new Sprite();
			graphic = _selectMask.graphics;
			graphic.lineStyle(3, 0x2876B7, 1, true, "normal", "square", "miter");
			graphic.beginFill(0xFFFFFF, 0);
			graphic.drawRect( -2, -2, WIDTH + 4, HEIGHT + 4);
			graphic.endFill();
			_selectMask.visible = false;
			addChild(_selectMask);
			
			_deleteIcon = new DeleteIcon() as Sprite;
			_deleteIcon.x = WIDTH - _deleteIcon.width - 2;
			_deleteIcon.y = SELECT_MASK_Y;
			_deleteIcon.buttonMode = true;
			_deleteIcon.visible = false;
			_deleteIcon.addEventListener(MouseEvent.CLICK, _deleteIconClick);
			addChild(_deleteIcon);
			
			if (_fp >= 10 && isValidPreviewFileType(_fileReference.type) && _fileReference.type != ".gif") {
				_closewiseBtn = new Button();
				_closewiseBtn.setStyle("skin", _skins.closewise);
				_closewiseBtn.x = 22
				_closewiseBtn.y = SELECT_MASK_Y;
				_closewiseBtn.useHandCursor = true;
				_closewiseBtn.visible = false;
				_closewiseBtn.addEventListener(MouseEvent.CLICK, _rotationBtnClick);
				addChild(_closewiseBtn);
				
				_anticlosewiseBtn = new Button();
				_anticlosewiseBtn.setStyle("skin", _skins.anticlosewise);
				_anticlosewiseBtn.x = 2
				_anticlosewiseBtn.y = SELECT_MASK_Y;
				_anticlosewiseBtn.useHandCursor = true;
				_anticlosewiseBtn.visible = false;
				_anticlosewiseBtn.addEventListener(MouseEvent.CLICK, _rotationBtnClick);
				addChild(_anticlosewiseBtn);
			}
			
			_progressBar = new Sprite();
			graphic = _progressBar.graphics;
			graphic.lineStyle();
			graphic.beginFill(0x2876B7);
			graphic.drawRect(0, 0, HEIGHT + 1, 3);
			graphic.endFill();
			_progressBar.x = -0.5;
			_progressBar.y = HEIGHT;
			_progressBar.scaleX = 0;
			addChild(_progressBar);
		}
		
		private function _deleteIconClick(e:MouseEvent):void
		{
			if (_uploadStatus == UPLOAD_STATUS_NORMAL
			  || _uploadStatus == UPLOAD_STATUS_WAITING) {
				  
				dispatchEvent(new Event(ItemEvent.DELETE, true));
			}
		}
		
		private function _rotationBtnClick(e:MouseEvent):void
		{
			if (e.target == _closewiseBtn) {
				rotationImg(ROTATE_CW);
			} else {
				rotationImg(ROTATE_ACW);
			}
		}
		
		private function _addBg():void
		{
			var bg:Sprite = new Sprite();
			var graphic:Graphics = bg.graphics;
			graphic.lineStyle(1, COLOR_BORDER);
			graphic.beginFill(COLOR_FILL);
			graphic.drawRect(0, 0, WIDTH, HEIGHT);
			graphic.endFill();
			graphic.lineStyle();
			
			var matrix:Matrix = new Matrix();
			matrix.createGradientBox(WIDTH, HEIGHT, 2 * Math.PI / 5);
			graphic.beginGradientFill(GradientType.LINEAR, [COLOR_FILL_GRAY, COLOR_FILL], [0.8, 0], [0, 250], matrix);
			graphic.drawRect(2, 2, WIDTH - 4, HEIGHT - 4);
			graphic.endFill();
			addChildAt(bg, 0);
		}
		
		private function _noPreview():void
		{
			var tf:TextFormat = new TextFormat("宋体", 12, 0x666666);
			tf.leading = 5;
			var previewerText:TextField = new TextField();
			previewerText.defaultTextFormat = tf;
			previewerText.selectable = false;
			previewerText.autoSize = TextFieldAutoSize.LEFT;
			
			var str:String = _fileReference.name;
			previewerText.text = str;
			if (previewerText.width > WIDTH - 4) {
				while (previewerText.width > WIDTH - 10) {
					str = str.substr(0, str.length - 1);
					previewerText.text = str;
				}
				str = previewerText.text + "...";
			}
			previewerText.text = "文件名：\n" + str;
			previewerText.x = 2;
			previewerText.y = 16;
			addChildAt(previewerText, 1);
		}
		
		private function _addListeners():void
		{
			if (_fp >= 10 && isValidPreviewFileType(_fileReference.type)) {
				_fileReference.addEventListener(Event.COMPLETE, _startPreview);
			}
			
			_fileReference.addEventListener(IOErrorEvent.IO_ERROR, _errorHandler);
			_fileReference.addEventListener(ProgressEvent.PROGRESS, _progressHandler);
			_fileReference.addEventListener(SecurityErrorEvent.SECURITY_ERROR, _errorHandler);
			_fileReference.addEventListener(DataEvent.UPLOAD_COMPLETE_DATA, _uploadCompleteDataHandler);
		}
		
		private function _errorHandler(e:Event = null):void
		{
			if (uploadStatus == UPLOAD_STATUS_FAILURE) {
				var icon:Sprite = new UploadedIconError() as Sprite;
				icon.x = WIDTH;
				icon.y = HEIGHT;
				addChild(icon);
				_removeProgressAndMask("上传失败");
				uploadStatus = UPLOAD_STATUS_FAILURE;
				dispatchEvent(new Event(ItemEvent.UPLOAD_FAILURE, true));
				//dispatchEvent(new Event(ItemEvent.UPLOAD_COMPLETE, true));
				
			}
			MonsterDebugger.trace('item upload error', e);
		}
		
		private function _progressHandler(e:ProgressEvent):void
		{
			if (_uploadStatus == UPLOAD_STATUS_NORMAL) {
				if (!_preloadProgress) {
					_preloadProgress = new Sprite();
					var t:TextField = new TextField();
					t.autoSize = TextFieldAutoSize.LEFT;
					t.selectable = false;
					t.text = "正在加载文件...";
					_preloadProgress.addChild(t);
					var gp:Graphics = _preloadProgress.graphics;
					gp.lineStyle(2, 0xE7E7E7);
					gp.moveTo(0, 20);
					gp.lineTo(80, 20);
					gp.lineStyle(2, 0xF56A4);
					gp.moveTo(0, 20);
				}
				
				addChild(_preloadProgress);
				_preloadProgress.graphics.lineTo(e.bytesLoaded / e.bytesTotal * 80, 20);
			}
			
			if (_uploadStatus != UPLOAD_STATUS_NORMAL 
			  && _uploadStatus != UPLOAD_STATUS_WAITING) {
				_percent = e.bytesLoaded / e.bytesTotal;
				_progressBar.scaleX = _percent;
			}
		}
		
		private function _removeProgressAndMask(str:String = ""):void
		{
			contains(_progressBar) && removeChild(_progressBar);
			_progressBar = null;
			
			if (_fp >= 10) {
				contains(_waitMask) && removeChild(_waitMask);
				_waitMask = null;
			} else {
				_addWaitMask(str);
			}
		}
		
		private function _uploadCompleteDataHandler(event:DataEvent):void
		{
			try {
				_json = JSON.decode(event.text);
				if (/^A\w{5}$/.test(_json.code)) {					
					var icon:Sprite = new UploadedIconComplete() as Sprite;
					icon.x = WIDTH;
					icon.y = HEIGHT;
					addChild(icon);
					
					_removeProgressAndMask("完成上传");
					_uploadStatus = UPLOAD_STATUS_SUCCESS;
					dispatchEvent(new Event(ItemEvent.UPLOAD_SUCCESS, true));
				} else {
					_uploadStatus = UPLOAD_STATUS_FAILURE;
					_errorHandler();
					MonsterDebugger.trace('json', event.text);
				}
			} catch (e:JSONParseError) { 
				MonsterDebugger.trace('json', event.text);
				_uploadStatus = UPLOAD_STATUS_FAILURE;
				_errorHandler();
			}
			
			dispatchEvent(new Event(ItemEvent.UPLOAD_COMPLETE, true));
		}
		
		private function _startPreview(e:Event):void
		{
			_fileReference.removeEventListener(Event.COMPLETE, _startPreview);
			contains(_preloadProgress) && removeChild(_preloadProgress);
			_preloadProgress = null;
			
			_previewLoader = new Loader();
			_previewLoader.contentLoaderInfo.addEventListener(Event.COMPLETE, _previewLoaded);
			_previewLoader.loadBytes(this._fileReference.data);
		}
		
		private function _previewLoaded(e:Event):void
		{
			var w:Number = _previewLoader.width;
			var h:Number = _previewLoader.height;
			
			var mult:Number = Math.min((WIDTH - 4) / w, (HEIGHT - 4) / h, 1);
			var box:Matrix = new Matrix();
			box.createBox(mult, mult);
			
			var bitmapData:BitmapData = new BitmapData(w * mult, h * mult, true, 0xFFFFFFFF);
			bitmapData.draw(_previewLoader, box);
			_previewerBitMap = new Bitmap(bitmapData);
			_previewerBitMap.x = (WIDTH - _previewerBitMap.width) / 2;
			_previewerBitMap.y = (HEIGHT - _previewerBitMap.height) / 2;
			addChildAt(_previewerBitMap, 1);
			
			_previewLoader.contentLoaderInfo.removeEventListener(Event.COMPLETE, _previewLoaded);
			_previewLoader = null;
			
			_preloaded = true;
			dispatchEvent(new Event(ItemEvent.PRELOAD_COMPLETE, true));
		}
		
		private function _addErrorMask(str:String):void
		{
			var paddBottom:Number = 30;
			if (!_errorMask) {
				var gp:Graphics;
				var color:uint = 0xFFFFFF;
				_errorMask = new Sprite();
				gp = _errorMask.graphics;
				gp.lineStyle();
				gp.beginFill(color, 0.5);
				gp.drawRect(0.5, 0.5, WIDTH - 1, HEIGHT - paddBottom);
				gp.endFill();
				gp.beginFill(color, 0.8);
				gp.drawRect(0.5, HEIGHT - paddBottom, WIDTH - 1, paddBottom);
				gp.endFill();
				
				
				var tf:TextFormat = new TextFormat("宋体", 12, 0xBA0000);
				tf.bold = true;
				_errorMaskText = new TextField();
				_errorMaskText.defaultTextFormat = tf;
				_errorMaskText.autoSize = TextFieldAutoSize.LEFT;
				_errorMaskText.selectable = false;
				_errorMask.mouseEnabled = false;
				_errorMask.mouseChildren = false;
				_errorMask.addChild(_errorMaskText);
			}
			
			if (_errorMaskText.text != str) {
				_errorMaskText.text = str;
				_errorMaskText.x = (WIDTH - _errorMaskText.width) / 2;
				_errorMaskText.y = HEIGHT - paddBottom + (paddBottom - _errorMaskText.height) / 2;
			}
			
			addChild(_errorMask);
		}
		
		private function _addWaitMask(tip:String = ""):void
		{
			if (!_waitMask) {
				_waitMask = new Sprite();
				if (_fp >= 10) {
					var gp:Graphics;
					gp = _waitMask.graphics;
					gp.lineStyle();
					gp.beginFill(0xFFFFFF, 0.5);
					gp.drawRect(0, 0, WIDTH, HEIGHT);
					gp.endFill();
				} else {
					var t:TextField = new TextField();
					var tf:TextFormat = new TextFormat("宋体", 12, 0x666666);
					tf.bold = true;
					t.defaultTextFormat = tf;
					t.selectable = false;
					t.autoSize = TextFieldAutoSize.LEFT;
					t.x = 2;
					t.y = HEIGHT / 2;
					_waitMask.addChild(t);
				}
			}
			
			if (!(_fp >= 10)) {
				(_waitMask.getChildAt(0) as TextField).text = tip;
			}
			
			addChild(_waitMask);
		}
		
		public function set fp(value:int):void 
		{
			if (value) {
				_fp = value;
			}
		}
		
		public function set maxSize(value:Number):void 
		{
			if (value) {
				_maxSize = value;
			}
		}
		
		public function set uploadUrl(value:String):void 
		{
			if (value) {
				_uploadUrl = value;
			}
		}
		
		public function get index():int 
		{ 
			return _index; 
		}
		
		public function set index(value:int):void 
		{
			_index = value;
		}
		
		public function get delay():Number
		{
			return _delay;
		}
		
		public function set delay(value:Number):void 
		{
			_delay = value;
		}
		
		public function get fileReference():FileReference 
		{ 
			return _fileReference; 
		}
		
		public function set fileReference(value:FileReference):void 
		{
			if (value) {
				_fileReference = value;
				_fileUrlLoader = new URLLoader();
				_addListeners();
				setTimeout(_init, _delay * 20);
			}
		}
		
		public function get uploadStatus():String 
		{ 
			return _uploadStatus; 
		}
		
		public function set uploadStatus(value:String):void 
		{
			if (_uploadStatus == value) {
				return;
			}
			
			_uploadStatus = value;
			switch (_uploadStatus) {
				case UPLOAD_STATUS_WAITING:
					_addWaitMask("等待上传...");
					selected = false;
					break;
				case UPLOAD_STATUS_SIZE_ERROR:
					_addWaitMask("不能上传...");
					break;
				case UPLOAD_STATUS_FAILURE:
					break;
				default:
					break;
			}
		}
		
		public function get selected():Boolean 
		{ 
			return _selected; 
		}
		
		public function set selected(value:Boolean):void 
		{
			if (_selected == value) {
				return;
			}
			
			_selected = value;
			_selectMask.visible = value;
			_selectMask.alpha = 1;
		}
		
		public function get requestVariables():Object
		{
			return _requestVariables;
		}
		
		public function setRequestVariables(key:String, value:*):void
		{
			_requestVariables[key] = value;
		}
		
		public function get json():Object
		{
			return _json;
		}
		
	}

}