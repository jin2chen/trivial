package sina.utils 
{
	import flash.display.Graphics;
	import flash.display.Sprite;
	
	/**
	 * @author     mole<mole1230@gmail.com>
	 * @version    $Id: BaseGemo.as 7 2010-10-19 10:38:30Z mole1230 $
	 */
	public class BaseGemo extends Sprite
	{
		
		public function BaseGemo() 
		{
			
		}
		
		public static function drawRectSprite(width:Number, height:Number, color:uint = 0xFFFFFF, alpha:Number = 1.0):Sprite
		{
			var win:Sprite = new Sprite();
			var gp:Graphics = win.graphics;
			gp.beginFill(color, alpha);
			gp.drawRect(0, 0, width, height);
			gp.endFill();
			return win;
		}
		
	}

}