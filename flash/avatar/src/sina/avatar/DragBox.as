package sina.avatar 
{
	import flash.display.Graphics;
	import flash.display.Sprite;
	import flash.events.*;
	import flash.geom.Rectangle;
	import nl.demonsters.debugger.MonsterDebugger;
	import sina.ui.skin.DragBoxGripper;
	
	/**
	 * @author     mole<mole1230@gmail.com>
	 * @version    $Id: DragBox.as 7 2010-10-19 10:38:30Z mole1230 $
	 */
	public class DragBox extends Sprite
	{
		public static const MOVE:String = "dragbox_moving";
		public static const STOP_MOVE:String = "dragbox_stop_movie";
		public static const START_MOVE:String = "dragbox_start_movie";
		public static const RESIZE:String = "dragbox_resizing";
		public static const START_RESIZE:String = "dragbox_start_resize";
		
		public var isDragging:Boolean = false;
		public var isResizing:Boolean = false;
		
		private var dragBoundary:Rectangle;
		private var bg:Sprite;
		private var gripper:Sprite;
		
		private var selfWidth:Number;
		private var selfHeight:Number;
		private var initWidth:Number;
		private var initHeight:Number;
		private var offWidth:Number;
		private var offHeight:Number;
		private var originalRatio:Number;
		
		public function DragBox(dragBoundary:Rectangle, width:Number = 100, height:Number = 100) 
		{
			this.bg = new Sprite();
			this.selfWidth = width;
			this.selfHeight = height;
			this.initWidth = width;
			this.initHeight = height;
			this.originalRatio = width / height;
			this.addChild(bg);
			this.dragBoundary = dragBoundary;
			this.addEventListener(Event.ADDED_TO_STAGE, onStage);
			
		}
		
		public function get maxLength():Number
		{
			return Math.max(this.initWidth, this.initHeight);
		}
		
		public function resetBoundary(dragBoundary:Rectangle):void
		{
			this.dragBoundary = dragBoundary;
		}
		
		public function resize(num:Number):void
		{
			if (this.initWidth > this.initHeight) {
				this.resizeBox(num, num / this.originalRatio);
			} else {
				this.resizeBox(num * this.originalRatio, num);
			}
		}
		
		private function onStage(event:Event):void
		{
			this.removeEventListener(Event.ADDED_TO_STAGE, onStage);
			this.drawBox();
			this.gripper = new DragBoxGripper();
			this.gripper.x = this.width;
			this.gripper.y = this.height;
			this.addChild(this.gripper);
			this.initListeners();
			stage.addChild(CustomCursor.getInstance());
		}
		
		private function initListeners():void
		{
			this.bg.addEventListener(MouseEvent.MOUSE_OVER, bgHandler);
			this.bg.addEventListener(MouseEvent.MOUSE_OUT, bgHandler);
			this.bg.addEventListener(MouseEvent.MOUSE_DOWN, bgHandler);
			this.bg.addEventListener(MouseEvent.MOUSE_UP, bgHandler);
			this.gripper.addEventListener(MouseEvent.MOUSE_OVER, gripperHandler);
            this.gripper.addEventListener(MouseEvent.MOUSE_OUT, gripperHandler);
            this.gripper.addEventListener(MouseEvent.MOUSE_DOWN, gripperHandler);
            this.gripper.addEventListener(MouseEvent.MOUSE_UP, gripperHandler);
		}
		
		private function bgHandler(event:MouseEvent):void
		{
			switch (event.type) {
				case MouseEvent.MOUSE_OVER:
					if (this.isResizing || this.isDragging) {
						return;
					}
					CustomCursor.getInstance().showMove();
					break;
				case MouseEvent.MOUSE_OUT:
					if (this.isResizing || this.isDragging) {
						return;
					}
					CustomCursor.getInstance().showNormal();
					break;
				case MouseEvent.MOUSE_DOWN:
					this.isDragging = true;
					this.dispatchEvent(new Event(START_MOVE));
					CustomCursor.getInstance().showMove();
					var boundary:Rectangle = new Rectangle(this.dragBoundary.x, this.dragBoundary.y, this.dragBoundary.width - this.width, this.dragBoundary.height - this.height);
					this.startDrag(false, boundary);
					stage.addEventListener(MouseEvent.MOUSE_MOVE, dragging);
					stage.addEventListener(MouseEvent.MOUSE_UP, bgHandler);
					break;
				case MouseEvent.MOUSE_UP:
					if (!this.bg.hitTestPoint(event.stageX, event.stageY)) {
						CustomCursor.getInstance().showNormal();
					}
					this.stopDrag();
					stage.removeEventListener(MouseEvent.MOUSE_MOVE, dragging);
					stage.removeEventListener(MouseEvent.MOUSE_UP, bgHandler);
					this.dispatchEvent(new Event(STOP_MOVE));
					this.isDragging = false;
					break;
				default:
					break;
			}
		}
		
		private function dragging(event:MouseEvent):void
		{
			
		}
		
		private function gripperHandler(event:MouseEvent):void
		{
			switch (event.type) {
				case MouseEvent.MOUSE_OVER:
					if (this.isResizing || this.isDragging) {
						return;
					}
					CustomCursor.getInstance().showResize(event.target as Sprite);
					break;
				case MouseEvent.MOUSE_OUT:
					if (this.isResizing || this.isDragging) {
						return;
					}
					CustomCursor.getInstance().showNormal();
					break;
				case MouseEvent.MOUSE_DOWN:
					this.isResizing = true;
					CustomCursor.getInstance().showResize(event.target as Sprite);
					this.dispatchEvent(new Event(START_RESIZE));
					this.offWidth = Math.abs(event.target.mouseX);
					this.offHeight = Math.abs(event.target.mouseY);
					
					event.stopPropagation();
					stage.addEventListener(MouseEvent.MOUSE_MOVE, resizing);
					stage.addEventListener(MouseEvent.MOUSE_UP, gripperHandler);
					break;
				case MouseEvent.MOUSE_UP:
					if (!this.gripper.hitTestPoint(event.stageX, event.stageY)) {
						CustomCursor.getInstance().showNormal();
					}
					stage.removeEventListener(MouseEvent.MOUSE_MOVE, resizing);
					stage.removeEventListener(MouseEvent.MOUSE_UP, gripperHandler);
					this.dispatchEvent(new Event(STOP_MOVE));
					this.isResizing = false;
					break;
				default:
					break;
			}
		}
		
		private function resizing(event:MouseEvent):void
		{
			this.resizeBox(this.mouseX, this.mouseY, this.offWidth, this.offHeight);
		}
		
		private function resizeBox(mouseX:Number, mouseY:Number, offWidth:Number = 0, offHeight:Number = 0):void
		{
			this.selfWidth = mouseX  + offWidth;
			this.selfHeight = mouseY + offHeight;
			var boundW:Number = this.dragBoundary.x + this.dragBoundary.width;
			var boundH:Number = this.dragBoundary.y + this.dragBoundary.height;

			if (this.selfWidth < 50) {
				this.selfWidth = 50;
			}
			if (this.selfHeight < 50) {
				this.selfHeight = 50;
			}
			if (this.selfWidth > boundW - this.x) {
				this.selfWidth = boundW - this.x;
			}
			if (this.selfHeight > boundH - this.y) {
				this.selfHeight = boundH - this.y;
			}
			if (this.selfWidth < this.selfHeight * this.originalRatio) {
				this.selfHeight = this.selfWidth / this.originalRatio;
			} else {
				this.selfWidth = this.selfHeight * this.originalRatio;
			}
			
			this.drawBox();
			this.gripper.x = this.selfWidth;
			this.gripper.y = this.selfHeight
		}
		
		private function drawBox():void
		{
			var gp:Graphics = this.bg.graphics;
			gp.clear();
			gp.beginFill(0x000000, 0);
			gp.lineStyle(1, 0xFFFFFF);
			gp.drawRect(0, 0, this.selfWidth - 1, this.selfHeight - 1);
			gp.endFill();
		}
	}

}