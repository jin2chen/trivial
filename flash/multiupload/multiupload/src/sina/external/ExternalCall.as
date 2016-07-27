package sina.external 
{
	import flash.external.ExternalInterface;
	
	/**
	 * @author     mole<mole1230@gmail.com>
	 * @version    $Id: ExternalCall.as 7 2010-10-19 10:38:30Z mole1230 $
	 */
	public class ExternalCall
	{
		
		public static function simple(callback:String):void
		{
			ExternalInterface.call(callback);
		}
		
		public static function setHeight(callback:String, height:Number):void
		{
			ExternalInterface.call(callback, height);
		}
		
		public static function endUpload(callback:String, files:Array):void
		{
			ExternalInterface.call(callback, files);
		}
		
		private static function _escapeMessage(message:*):* 
		{
			if (message is String) {
				message = _escapeString(message);
			}
			else if (message is Array) {
				message = _escapeArray(message);
			}
			else if (message is Object) {
				message = _escapeObject(message);
			}
			
			return message;
		}
		
		private static function _escapeString(message:String):String 
		{
			var replacePattern:RegExp = /\\/g; //new RegExp("/\\/", "g");
			return message.replace(replacePattern, "\\\\");
		}
		
		private static function _escapeArray(message_array:Array):Array 
		{
			var length:uint = message_array.length;
			var i:uint = 0;
			for (i; i < length; i++) {
				message_array[i] = _escapeMessage(message_array[i]);
			}
			return message_array;
		}
		private static function _escapeObject(message_obj:Object):Object 
		{
			for (var name:String in message_obj) {
				message_obj[name] = _escapeMessage(message_obj[name]);
			}
			return message_obj;
		}
	}
}