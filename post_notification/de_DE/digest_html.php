<?php
/*
 * Please read the templates.txt for documentation. 
 */
 
//Set subject
$subject = "Überischt über neue Posts von $blogname"; 

//Sort by Post
usort($objs, $sort_post);
 
 
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>@@blogname</title>
<meta http-equiv=content-type content="text/html; charset=ISO-8859-1" />



</head>
<body>
<!-- This must be in the body, because some webmail GUIs ignore the head-->
<style type="text/css">
body {
margin: 0px;
padding: 20px;
background: #FFF;
font-family: Georgia, "Times New Roman", Times, serif;
font-size: 62.5%;
}

#container {
margin: 20px;
padding: 10px;
background: #FFF;
border:solid 1px #556B2F;
}

div.header {
font-size: 1.5em;
font-weight: bold;
text-align: center;
background: #FFF;
color: #556B2F;
}

div.subhead {
font-size: 1.5em;
font-weight: bold;
text-align: center;
background: #FFF;
color: #CCC;
}

h1 {
border-top:dotted 1px #556B2F;
padding-top: 20px;
font-size: 1.4em;
font-family: Garamond, Georgia, Verdana, serif;
color: #556B2F;
}

h2 {
font-size: 1.2em;
font-weight: bold;
}

h3 {
font-size: 1.0em;
font-weight: bold;
}

a:link {
color: #556B2F;
text-decoration: none;
}

a:hover {
color: #758575;
text-decoration: none;
}

.subscriptionDetails {
margin: 20px 0;
font-size: 1em;
font-family: "Lucida Sans Unicode", Tahoma, Arial, sans-serif;
color: #000;
}

</style>

<?php 
if($numposts){ ?> 
	<div id="container">
	<div class="header">
		<?php echo $numposts; ?> neue Posts auf <?php echo $blogname; ?>
	</div>
	<?php
	foreach($objs as $obj){
		if($obj['type'] == 0){ 
			?>		
			<h1><a href="<?php echo $obj['url']; ?>">   <?php echo "{$obj['title']} von {$obj['author']}"; ?></a></h1>
				 <?php 
				 echo "<div class=\"subhead\">{$obj['date']} {$obj['time']}</div>";
				 echo "<div> {$obj['content']}</div>"; 
				 ?>
			
			<?php
		}
	}		
	?>
	</div>
<?php 
} /*Posts*/ 

if($numcomments) { ?>
	<div id="container">
	<div class="header">
		<?php echo $numcomments; ?> neue Kommentare auf <?php echo $blogname; ?>
	</div>
	<?php
	foreach($objs as $obj){
		if($obj['type'] == 1){ 
			?>		
			<h1><a href="<?php echo $obj['url']; ?>">   <?php echo "{$obj['author']} meint zu {$obj['post_title']}"; ?></a></h1>
				 <?php 
				 echo "<div class=\"subhead\">{$obj['date']} {$obj['time']}</div>";
				 echo "<div>{$obj['content']}</div>"; 
				 ?>
			
			<?php
		}
	}		
	?>
	</div>	
<?php 
} /*comments */	
?>
<div class="subscriptionDetails">
Sie haben sich zum Empfang diese Mitteilung angemeldet. <br /><br />
Wenn Sie sich abmelden oder ihre Einstellungen ändern möchten besuchen Sie bitte:<br />
<a href="@@conf_url">@@conf_url</a><br />
</div>



</body>
</html>