<?php
 /**
 * Zend Framework
 *
 * LICENSE
 *
 * Arquivo de livre reprodução
 * 
 * Utilização:
 * 
 * echo '<pre>';
 * print_r(CsvToArray('teste.csv'));
 * echo '</pre>';
 *
 * 
 * 
 * @category   CSV
 * @package    CsvToArray
 * @copyright  Copyleft (c) 2009-2010 . (http://www.pontophp.com.br)
 * @version    1.1
 */
 final class CsvToArray {

 	/**
 	 * Função estática principal. O parâmetro $delimiter não é obrigatório, apenas se for utilizado outro tipo de caractere, por exemplo a vírgula (,).
 	 *
 	 * @param string $file
 	 * @param char $delimiter
 	 * @return array
 	 */
 	public static function open($file, $delimiter = ';') {
 		return self::csvArray($file, $delimiter);
 	}
 	
 	/**
 	 * Função para contagem do total de linhas.
 	 *
 	 * @param string $file
 	 * @param integer $capture_limit_in_kb
 	 * @return integer
 	 */
 	private static function count_lines($file, $capture_limit_in_kb = 500) {
		// read in file
		$fh = fopen($file, 'r');
			$contents = fread($fh, ($capture_limit_in_kb * 1024)); // in kB
			
			// remove blank lines from the beginning and end
			$contents = preg_replace("`\A[ \t]*\r?\n|\r?\n[ \t]*\Z`", "", $contents);
		fclose($fh);

		// specify allowed line endings
		$line_endings = array(
			'rn' => "\r\n",
			'n' => "\n",
			'r' => "\r",
			'nr' => "\n\r"
		);

		// loop and count each line ending instance
		foreach ($line_endings as $key => $value) {
			$line_result[$key] = substr_count($contents, $value);
		}

		// sort by largest array value
		asort($line_result);

		return end($line_result);
	}
 	
 	private static function csvArray($file, $delimiter) {
 		$result = Array();
 		$size = filesize($file) + 1;
 		$count = self::count_lines($file, 500);
 		$file = fopen($file, 'r');
 		$keys = fgetcsv($file, $size, $delimiter);
 		
 		require_once 'ProgressBar.class.php';

		echo CARREGANDO;
		$p = new ProgressBar();
		echo '<div style="width: 400px;">' . "\r\n";
		$p->render();
		echo '</div>' . "\r\n";
 		
 		$j = 0;
 		while ($row = fgetcsv($file, $size, $delimiter)) {
 			for($i = 0; $i < count($row); $i++) {
 				if(array_key_exists($i, $keys)) {
 					$row[$keys[$i]] = $row[$i];
 				}
 			}
 			$j++;
 			$p->setProgressBarProgress($j*100/$count);
			usleep(100000*0.1);
 			$result[] = $row;
 		}
 		fclose($file);
 		return $result;
 	}
}
?>
