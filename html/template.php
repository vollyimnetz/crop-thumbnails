<!DOCTYPE HTML>
<html>
	<head>
		<title>test</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<?php wp_print_styles(); ?>
		<?php wp_print_scripts(); ?>
		<script type="text/javascript" charset="utf-8">
			var ajaxurl        = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
		</script>
		<?php if(isset($cptScript)) {echo $cptScript;} ?>
	</head>
	<body>
		<?php if(isset($cptContent)) {echo $cptContent;} ?>
	</body>
</html>
	