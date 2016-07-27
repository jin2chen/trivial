/**
 * 计算球面间两点间距
 *
 * @author mole <mole.chen@foxmail.com>
 * 
 * functions
 * _distance(ox[原点经度], oy[原点纬度], dx[目标经度], dy[目标纬度])
 * distance(oid[起点ID], did[目标ID])
 */
DROP FUNCTION IF EXISTS _distance;
DROP FUNCTION IF EXISTS distance;
DELIMITER $$
CREATE DEFINER=`root`@`localhost` FUNCTION _distance (ox DOUBLE, oy DOUBLE, dx DOUBLE, dy DOUBLE)
RETURNS DOUBLE 
BEGIN
	DECLARE rs DOUBLE DEFAULT 0;
	SELECT 6378 * 2 * ASIN(SQRT(POWER(SIN((oy - ABS(dy)) * PI() / 180 / 2), 2) + COS(oy * PI() / 180) * COS(ABS(dy) * PI() / 180) * POWER(SIN((ox - dx) * PI() / 180 / 2), 2))) INTO rs;
	RETURN rs;
END
$$ 
CREATE DEFINER=`root`@`localhost` FUNCTION distance (oid INT, did INT) 
RETURNS DOUBLE 
BEGIN
	DECLARE rs, ox, oy, dx, dy DOUBLE;
	SELECT `x`, `y` INTO ox, oy FROM t_dict_house WHERE house_id = oid;
	SELECT `x`, `y` INTO dx, dy FROM t_dict_house WHERE house_id = did;
	SELECT _distance (ox, oy, dx, dy) INTO rs;
	RETURN rs;
END $$

DELIMITER ;
