<?php

/**
 * Progress bar for a lengthy PHP process
 * http://spidgorny.blogspot.com/2012/02/progress-bar-for-lengthy-php-process.html
 */

class ProgressBar {
	var $percentDone = 0;
	var $indeterminate = true;
	var $pbid;
	var $pbarid;
	var $tbarid;
	var $textid;
	var $decimals = 0;

	function __construct($percentDone = 0) {
		$this->pbid = 'pb';
		$this->pbarid = 'progress-bar';
		$this->tbarid = 'transparent-bar';
		$this->textid = 'pb_text';
		$this->percentDone = $percentDone;
		$this->indeterminate = true;
	}

	function render() {
		//print ($GLOBALS['CONTENT']);
		//$GLOBALS['CONTENT'] = '';
		print($this->getContent());
		$this->flush();
		//$this->setProgressBarProgress(0);
	}

	function getContent() {
		//$this->percentDone = floatval($this->percentDone);
		//$percentDone = number_format($this->percentDone, $this->decimals, '.', '') .'%';
		return '	<div id="'.$this->pbid.'" class="pb_container">
		<div id="'.$this->textid.'" class="'.$this->textid.'" style="display: none"></div>
		<div class="pb_bar">
			<div id="'.$this->pbarid.'" class="pb_indeterminate" style="width: 100%;"></div>
			<div id="'.$this->tbarid.'"></div>
		</div>
		<br style="height: 1px; font-size: 1px;"/>
	</div>';
	}

	function setProgressBarProgress($percentDone, $text = '') {
		if ($this->indeterminate) {
			print('<script>'."\n\r".'	document.getElementById("'.$this->pbarid.'").setAttribute("class", "pb_before");'."\n\r".'	document.getElementById("'.$this->textid.'").style.display = "inline";'."\n\r".'</script>'."\n\r");
			$this->indeterminate = false;
		}
		$this->percentDone = $percentDone;
		$text = $text ? $text : number_format($this->percentDone, $this->decimals, '.', '').'%';
		print('<script>'."\n\r".'if (document.getElementById("'.$this->pbarid.'")) {
	document.getElementById("'.$this->pbarid.'").style.width = "'.$percentDone.'%";'."\n\r");
		if ($percentDone == 100) {
			//print('document.getElementById("'.$this->pbid.'").style.display = "none";');
			print('	document.getElementById("carregando").innerHTML = "Redirecionando&hellip;";
	document.getElementById("'.$this->pbarid.'").setAttribute("class", "pb_indeterminate");
	document.getElementById("'.$this->textid.'").style.display = "none";'."\r\n");
		} else {
			print('	document.getElementById("'.$this->tbarid.'").style.width = "'.(100-$percentDone).'%";'."\r\n");
		}
		if ($text) {
			print('	document.getElementById("'.$this->textid.'").innerHTML = "'.htmlspecialchars($text).'";'."\r\n");
		}
		print('}'."\n\r".'</script>');
		$this->flush();
	}
	
	function hide() {
		print('<script>'."\n\r".'document.getElementById("'.$this->pbid.'").style.display = "none";'."\n\r".'document.getElementById("carregando").style.display = "none";'."\n\r".'document.title = "Erro";'."\n\r".'</script>'."\n\r");
		$this->flush();
	}

	function flush() {
		print str_pad('', intval(ini_get('output_buffering')))."\n\r";
		//ob_end_flush();
		flush();
	}

}