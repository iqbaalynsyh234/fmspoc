<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
<meta name="google-site-verification" content="TvgB-1A56P-JtCDlsIhWa3oOk5TW7qsD8X5N3oqu2JY" />
		<title>Intelligent Transportation System</title>
		<meta name="description" content="<?=isset($metakeywords) ? $metakeywords : $this->config->item("APPDESCRIPTION"); ?>" />
		<meta name="keywords" content="<?=isset($metadescription) ? $metadescription : $this->config->item("APPKEYWORDS"); ?>" />
		<meta name="robots" content="index, follow" />
		<meta name="googlebot" content="index,follow" />
		<meta name="msnbot" content="index,follow" />		
		<meta name="author" content="adilahsoft.com" />
		<meta name="copyright" content="2010 adilahsoft.com" />
		<?php if ($this->config->item("favicon")) { ?>
		<link rel="shortcut icon" href="<?=base_url().$this->config->item("favicon");?>">
		<link rel="icon" href="<?=base_url().$this->config->item("favicon");?>">
		<?php } else  { ?>
		<link rel="shortcut icon" href="<?=base_url();?>assets/images/favicon_lacakmobil.ico">
		<link rel="icon" href="<?=base_url();?>assets/images/favicon_lacakmobil.ico">
		<?php } ?>
		<link media="screen" rel="stylesheet" type="text/css" href="<?php echo base_url();?>assets/kpa/css/login.css"  />
		<script src="<?php echo base_url();?>assets/kpa/js/jquery-1.7.1.min.js" type="text/javascript"></script>
		

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-20131355-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
	</head>
	<body>
		<?=$content?>
	</body>
</html>
