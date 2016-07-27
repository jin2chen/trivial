package sina.avatar 
{
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.events.MouseEvent;
	import flash.ui.Mouse;
	import nl.demonsters.debugger.MonsterDebugger;
	import sina.ui.skin.CursorMove;
	import sina.ui.skin.CursorResize;
	
	/**
	 * @author     mole<mole1230@gmail.com>
	 * @version    $Id: CustomCursor.as 7 2010-10-19 10:38:30Z mole1230 $
	 */
	public class CustomCursor extends Sprite
	{
		private static var instance:CustomCursor = null;
		
		private var cursor:Sprite;
		private var resizeCursor:Sprite;
		private var moveCursor:Sprite;
		
		private var xOffset:Number;
		private var yOffset:Number;
		private var targetObj:Sprite;
		
		public static function getInstance():CustomCursor
		{
			if (instance === null) {
				instance = new CustomCursor();
			}
			
			return instance;
		}
		
		public function CustomCursor() 
		{
			this.cursor = new Sprite();
			this.resizeCursor = new CursorResize();
			this.moveCursor = new CursorMove();
			this.mouseEnabled = false;
            this.mouseChildren = false;
		}
		
		public function showNormal():void
		{
			Mouse.show();
			if (this.contains(this.cursor)) {
				this.removeChild(this.cursor);
			}
			this.removeListener();
		}
		
		public function showMove():void
		{
			this.targetObj = null;
			this.xOffset = (-this.moveCursor.width) / 2;
			this.yOffset = ( -this.moveCursor.height) / 2;
			Mouse.hide();
			
			if (this.contains(this.resizeCursor)) {
				this.removeChild(this.resizeCursor);
			}
			this.cursor = this.moveCursor as Sprite;
			this.addChild(this.cursor);
			stage.addEventListener(MouseEvent.MOUSE_MOVE, updateCursor);
			this.updateCursor();
		}
		
		public function showResize(box:Sprite = null):void
		{
			this.targetObj = null;
			this.xOffset = (-this.resizeCursor.width) / 2;
			this.yOffset = (-this.resizeCursor.height) / 2;
			Mouse.hide();
			
			if (this.contains(this.moveCursor)) {
				this.removeChild(this.moveCursor);
			}
			this.cursor = this.resizeCursor as Sprite;
			this.addChild(this.cursor);
			
			if (box) {
				this.targetObj = box;
				this.xOffset = Math.abs(this.targetObj.mouseX) + this.xOffset;
				this.yOffset = Math.abs(this.targetObj.mouseY) + this.yOffset;
				stage.addEventListener(MouseEvent.MOUSE_MOVE, updateCursor);
			} else {
				this.targetObj = null;
				this.removeListener();
			}
			
			this.updateCursor(null);
		}
		
		private function updateCursor(event:MouseEvent = null):void
		{
			this.cursor.x = this.mouseX + this.xOffset;
			this.cursor.y = this.mouseY + this.yOffset;
			if (event) {
				event.updateAfterEvent();
			}
		}
		
		private function removeListener():void
		{
			try {
				stage.removeEventListener(MouseEvent.MOUSE_MOVE, updateCursor);
			} catch (e:Error) {
				
			}
		}
	}

}