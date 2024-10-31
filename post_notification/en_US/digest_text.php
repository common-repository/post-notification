<?php
/*
 * Please read the templates.txt for documentation. 
 */
 
$subject = "Digest by $blogname"; 
 
usort($objs, $sort_post);

echo "$blogname has ";
if($numposts) echo "$numposts new posts ";
if($numposts && $numcomments) echo 'and ';
if($numcomments) echo "$numcomments comments ";
echo "geschrieben.\n\n";

if($numposts){
	echo "POSTS\n".
	     "=====\n\n\n";

	foreach($objs as $obj){
		if($obj['type'] == 0){ 
			echo "{$obj['date']} {$obj['time']}: {$obj['title']} by {$obj['author']}\n" .
				  "{$obj['url']}\n" .
				  "{$obj['contents']}\n\n\n";
			
		}
	}					

} 
			
if($numcomments){
	echo "Comments\n".
	     "========\n";
	$cur_post = - 1;
	foreach($objs as $obj){
		if($obj['type'] == 1){
			if($cur_post != $obj['post_id']){
				echo "\n!!! Comments about {$obj['post_title']} by {$obj['post_author']} ({$obj['post_url']})\n\n";
				$cur_post = $obj['post_id'];
			}
			
			echo "{$obj['date']} {$obj['time']}:  {$obj['author']} thinks \"{$obj['content']}\"\n";			
		}
	}					
}






?>  