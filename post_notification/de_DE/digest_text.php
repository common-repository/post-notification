<?php
/*
 * Please read the templates.txt for documentation. 
 */
 
$subject = "Überischt über neue Posts von $blogname"; 
 
usort($objs, $sort_post);

echo "Auf $blogname wurden ";
if($numposts) echo "$numposts Beiträge ";
if($numposts && $numcomments) echo 'und ';
if($numcomments) echo "$numcomments Kommentare ";
echo "geschrieben.\n\n";

if($numposts){
	echo "BEITRÄGE\n".
	     "========\n\n\n";

	foreach($objs as $obj){
		if($obj['type'] == 0){ 
			echo "{$obj['date']} {$obj['time']}: {$obj['title']} von {$obj['author']}\n" .
				  "{$obj['url']}\n" .
				  "{$obj['contents']}\n\n\n";
			
		}
	}					

} 
			
if($numcomments){
	echo "Kommentare\n".
	     "==========\n";
	$cur_post = - 1;
	foreach($objs as $obj){
		if($obj['type'] == 1){
			if($cur_post != $obj['post_id']){
				echo "\n!!! Kommentare zu {$obj['post_title']} von {$obj['post_author']} ({$obj['post_url']})\n\n";
				$cur_post = $obj['post_id'];
			}
			
			echo "{$obj['date']} {$obj['time']}:  {$obj['author']} meint \"{$obj['content']}\"\n";			
		}
	}					
}






?>  