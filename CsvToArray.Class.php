 <?php
 /**
 * Zend Framework
 *
 * LICENSE
 *
 * Arquivo de livre reprodu��o
 * 
 * Utiliza��o:
 * 
 * echo '<pre>';
 * print_r(CsvToArray('teste.csv'));
 * echo '</pre>';
 *
 * 
 * 
 * @category   CSV
 * @package    ScvToArray
 * @copyright  Copyleft (c) 2009-2010 . (http://www.pontophp.com.br)
 * @version    1.0
 */
 final class CsvToArray{

 	/**
 	 * Fun��o est�tica principal. O par�metro $delimiter n�o � obrigat�rio, apenas se for utilizado outro tipo de caractere, por exemplo a v�rgula (,).
 	 *
 	 * @param string $file
 	 * @param char $delimiter
 	 * @return array
 	 */
 	public static function open($file, $delimiter = ';'){
 		return self::csvArray($file, $delimiter);
 	}
 	private function csvArray($file, $delimiter)
 	{
 		$result = Array();
 		$size = filesize($file) + 1;
 		$file = fopen($file, 'r');
 		$keys = fgetcsv($file, $size, $delimiter);
 		while ($row = fgetcsv($file, $size, $delimiter))
 		{
 			for($i = 0; $i < count($row); $i++)
 			{
 				if(array_key_exists($i, $keys))
 				{
 					$row[$keys[$i]] = $row[$i];
 				}
 			}
 			$result[] = $row;
 		}

 		fclose($file);

 		return $result;
 	}
 }
?>
