<?php 
function short($hodnota, $dlzka)
	{

		$string = strip_tags($hodnota);
		if (strlen($string) > (int)$dlzka) {

		    $stringCut = substr($string, 0, (int)$dlzka);
		    $endPoint = strrpos($stringCut, ' ');
		    $string = $endPoint? substr($stringCut, 0, $endPoint) : substr($stringCut, 0);
		    $string .= '...';
		}
		return $string;
				
	}	
 ?>