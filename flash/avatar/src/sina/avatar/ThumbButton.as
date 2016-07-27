package sina.avatar 
{
	import flash.display.Bitmap;
	import flash.display.BitmapData;
	import flash.display.Graphics;
	import flash.display.Sprite;
	import flash.filters.GlowFilter;
	import flash.text.TextField;
	import flash.text.TextFieldAutoSize;
	import flash.text.TextFormat;
	import nl.demonsters.debugger.MonsterDebugger;
	
	/**
	 * @author     mole<mole1230@gmail.com>
	 * @version    $Id: ThumbButton.as 7 2010-10-19 10:38:30Z mole1230 $
	 */
	public class ThumbButton extends Sprite
	{
		private var buttonWidth:Number;
		private var buttonHeight:Number;
		private var titleText:String;
		
		private var bitmap:Bitmap;
		private var buttonBg:Sprite;
		private var bitmapBg:Sprite;
		private var bgFilters:Array;
		
		private var _active:Boolean = true;
		
		public function ThumbButton(width:Number, height:Number, titleText:String) 
		{	
			var bg:Sprite = new Sprite();
			var gp:Graphics = bg.graphics;
			gp.beginFill(0xFFFFFF, 0);
			gp.lineStyle(1, 0x999999);
			gp.drawRect(0, 0, width + 1, height + 1);
			this.addChild(bg);
			
			this.bitmap = new Bitmap(null, "never", true);
			this.bitmap.x = 0.5;
			this.bitmap.y = 0.5;
			bg.addChild(this.bitmap);
			
			var guideText:TextField = new TextField();
			var tf:TextFormat = new TextFormat("宋体", 13, 0x999999);
			guideText.defaultTextFormat = tf;
			guideText.selectable = false;
			guideText.autoSize = TextFieldAutoSize.LEFT;
			guideText.text = titleText;
			guideText.y = this.height + 5;
			this.addChild(guideText);
			
			if (bg.width > guideText.width) {
				guideText.x = (bg.width - guideText.width) / 2;
			} else {
				bg.x = (guideText.width - bg.width) / 2;
			}
			
			/*this.buttonWidth = width + 8;
			this.buttonHeight = height + 8;
			this.titleText = titleText;
		
			var wrapper:Sprite = new Sprite();
			this.addChild(wrapper);
			
			var gp:Graphics;
			this.buttonBg = new Sprite();
			gp = this.buttonBg.graphics;
			gp.beginFill(0xEEEEEE);
			gp.lineStyle(1, 0xFFFFFF);
			gp.drawRect(0, 0, this.buttonWidth, this.buttonHeight);
			gp.endFill();
			wrapper.addChild(this.buttonBg);
			
			var gripper:Sprite = new Sprite();
			gp = gripper.graphics;
			gp.beginFill(0xEEEEEE);
			gp.drawRect(0, 0, 11, 11);
			gp.endFill();
			gripper.rotation = -45;
			gripper.x = -8;
			gripper.y = (this.buttonBg.height - gripper.height / 2.8) / 2;
			this.buttonBg.addChild(gripper);
			
			this.bitmapBg = new Sprite();
			gp = this.bitmapBg.graphics;
			gp.beginFill(0xBDCFDB);
			gp.drawRect(0, 0, this.buttonWidth - 4, this.buttonHeight - 4);
			gp.endFill();
			this.bitmapBg.x = 2;
			this.bitmapBg.y = 2;
			wrapper.addChild(this.bitmapBg);
			
			this.bitmap = new Bitmap(null, "never", true);
			this.bitmap.x = 4;
			this.bitmap.y = 4;
			wrapper.addChild(this.bitmap);
			
			var guideText:TextField = new TextField();
			var tf:TextFormat = new TextFormat("宋体", 13, 0x999999);
			guideText.defaultTextFormat = tf;
			guideText.selectable = false;
			guideText.autoSize = TextFieldAutoSize.CENTER;
			guideText.text = titleText;
			guideText.y = this.buttonBg.height + 5;
			this.addChild(guideText);

			if (this.buttonWidth > guideText.width) {
				guideText.x = (this.buttonWidth - guideText.width) / 2;
			} else {
				guideText.x = 0;
				wrapper.x = (guideText.width - this.buttonWidth) / 2;
			}
			
			this.bgFilters = new Array(new GlowFilter(0xEC936C, 0.6, 6, 6, 2, 6));*/
		}
		/*
		public function set active(bool:Boolean):void
		{
			if (this._active == bool) {
				return;
			}
			
			this._active = bool;
			this.buttonMode = !this._active;
			this.mouseChildren = !this._active;
			this.mouseEnabled = !this._active;
			this.bitmapBg.visible = !this._active;
			this.buttonBg.visible = this._active;
			this.buttonBg.filters = this._active ? this.bgFilters : null;
		}*/
		
		public function get active():Boolean
		{
			return this._active;
		}
		
		public function draw(thumbData:BitmapData):void
		{
			this.bitmap.bitmapData = thumbData;
		}
		
	}

}