<?php
	require_once "functions.php"; 
	require_once "sessions.php";
	require_once "sanity_check.php";
	require_once "version.php"; 
	require_once "config.php";
	require_once "db-prefs.php";

	$link = db_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);	

	init_connection($link);

	login_sequence($link);

	$dt_add = time();

	no_cache_incantation();

	header('Content-Type: text/html; charset=utf-8');
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Tiny Tiny RSS</title>
	<link rel="stylesheet" type="text/css" href="lib/dijit/themes/claro/claro.css"/>
	<link rel="stylesheet" type="text/css" href="tt-rss.css?<?php echo $dt_add ?>"/>
	<link rel="stylesheet" type="text/css" href="cdm.css?<?php echo $dt_add ?>"/>

	<?php print_theme_includes($link) ?>
	<?php print_user_stylesheet($link) ?>

	<link rel="shortcut icon" type="image/png" href="images/favicon.png"/>

	<script type="text/javascript" src="lib/prototype.js"></script>
	<script type="text/javascript" src="lib/scriptaculous/scriptaculous.js?load=effects,dragdrop,controls"></script>
	<script type="text/javascript" src="lib/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
	<script type="text/javascript" charset="utf-8" src="localized_js.php?<?php echo $dt_add ?>"></script>
	<script type="text/javascript" charset="utf-8" src="tt-rss.js?<?php echo $dt_add ?>"></script>
	<script type="text/javascript" charset="utf-8" src="functions.js?<?php echo $dt_add ?>"></script>
	<script type="text/javascript" charset="utf-8" src="feedlist.js?<?php echo $dt_add ?>"></script>
	<script type="text/javascript" charset="utf-8" src="viewfeed.js?<?php echo $dt_add ?>"></script>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	<script type="text/javascript">
		Event.observe(window, 'load', function() {
			init();
		});
	</script>
</head>

<body id="ttrssMain" class="claro">

<div id="overlay" style="display : block">
	<div id="overlay_inner">
		<div class="insensitive"><?php echo __("Loading, please wait...") ?></div>
		<div dojoType="dijit.ProgressBar" places="0" style="width : 300px" id="loading_bar"
	     progress="0" maximum="100">
		</div>
	<noscript>
		<p>
		<?php print_error(__("Your browser doesn't support Javascript, which is required
		for this application to function properly. Please check your
		browser settings.")) ?></p>
	</noscript>
	</div>
</div> 

<div id="hotkey_help_overlay" style="display : none" onclick="Element.hide(this)">
	<?php rounded_table_start("hho"); ?>
	<?php include "help/3.php" ?>
	<?php rounded_table_end(); ?>
</div>

<div id="notify" class="notify"><span id="notify_body">&nbsp;</span></div>
<div id="cmdline" style="display : none"></div>
<div id="auxDlg" style="display : none"></div>

<div id="main" dojoType="dijit.layout.BorderContainer">

<div id="header" dojoType="dijit.layout.ContentPane" region="top">
	<div class="topLinks" id="topLinks">

	<?php if (!SINGLE_USER_MODE) { ?>
			<?php echo __('Hello,') ?> <b><?php echo $_SESSION["name"] ?></b> |
	<?php } ?>
	<a href="prefs.php"><?php echo __('Preferences') ?></a>

	<?php if (defined('FEEDBACK_URL') && FEEDBACK_URL) { ?>
		| <a target="_blank" class="feedback" href="<?php echo FEEDBACK_URL ?>">
				<?php echo __('Comments?') ?></a>
	<?php } ?>

	<?php if (!SINGLE_USER_MODE) { ?>
			| <a href="logout.php"><?php echo __('Logout') ?></a>
	<?php } ?>

	<img id="newVersionIcon" style="display:none;" onclick="newVersionDlg()" 
		width="13" height="13" 
		src="<?php echo theme_image($link, 'images/new_version.png') ?>"
		title="<?php echo __('New version of Tiny Tiny RSS is available!') ?>" 
		alt="new_version_icon"/>

	</div>

	<img src="<?php echo theme_image($link, 'images/ttrss_logo.png') ?>" alt="Tiny Tiny RSS"/>	
</div>

<div id="feeds-holder" dojoType="dijit.layout.ContentPane" region="leading" style="width : 20%" splitter="true">
	<div id="feedlistLoading">
		<img src='images/indicator_tiny.gif'>
		<?php echo  __("Loading, please wait..."); ?></div>
	<div id="feedTree"></div>
</div>

<div dojoType="dijit.layout.TabContainer" region="center" id="content-tabs">
<div dojoType="dijit.layout.BorderContainer" region="center" id="content-wrap"
	title="News">

<div id="toolbar" dojoType="dijit.layout.ContentPane" region="top">

	<div id="main-toolbar" dojoType="dijit.Toolbar">		

		<form id="main_toolbar_form" action="" onsubmit='return false'>

		<button dojoType="dijit.form.Button" id="collapse_feeds_btn" 
			onclick="collapse_feedlist()"
			title="<?php echo __('Collapse feedlist') ?>" style="display : inline">
			&lt;&lt;</button>

		<select name="view_mode" title="<?php echo __('Show articles') ?>" 
			onchange="viewModeChanged()"
			dojoType="dijit.form.Select" style="width : 100px">
			<option selected="selected" value="adaptive"><?php echo __('Adaptive') ?></option>
			<option value="all_articles"><?php echo __('All Articles') ?></option>
			<option value="marked"><?php echo __('Starred') ?></option>
			<option value="published"><?php echo __('Published') ?></option>
			<option value="unread"><?php echo __('Unread') ?></option>
			<!-- <option value="noscores"><?php echo __('Ignore Scoring') ?></option> -->
			<option value="updated"><?php echo __('Updated') ?></option>
		</select>

		<select title="<?php echo __('Sort articles') ?>"
			onchange="viewModeChanged()" 
			dojoType="dijit.form.Select" name="order_by" style="width : 100px">
			<option selected="selected" value="default"><?php echo __('Default') ?></option>
			<option value="date"><?php echo __('Date') ?></option>
			<option value="title"><?php echo __('Title') ?></option>
			<option value="score"><?php echo __('Score') ?></option>
		</select>

		<button dojoType="dijit.form.Button" name="update" 
			onclick="scheduleFeedUpdate()">
			<?php echo __('Update') ?></button>

		<button dojoType="dijit.form.Button" 
			onclick="catchupCurrentFeed()">
			<?php echo __('Mark as read') ?></button>

		</form>

		<div class="actionChooser">
			<select onchange="quickMenuChange(this)" dojoType="dijit.form.Select"
				style="width : 150px">
					<option value="qmcDefault" selected="selected"><?php echo __('Actions...') ?></option>
					<option value="qmcSearch"><?php echo __('Search...') ?></option>
					<option disabled="1" value="x"><?php echo __('Feed actions:') ?></option>
					<option value="qmcAddFeed"><?php echo __('Subscribe to feed...') ?></option>
					<option value="qmcEditFeed"><?php echo __('Edit this feed...') ?></option>
					<option value="qmcRescoreFeed"><?php echo __('Rescore feed') ?></option>
					<option value="qmcRemoveFeed"><?php echo __('Unsubscribe') ?></option>
					<option disabled="1" value="x"><?php echo __('All feeds:') ?></option>
					<option value="qmcCatchupAll"><?php echo __('Mark as read') ?></option>
					<option value="qmcShowOnlyUnread"><?php echo __('(Un)hide read feeds') ?></option>
					<option disabled="1" value="x"><?php echo __('Other actions:') ?></option>
					<option value="qmcDigest"><?php echo __('Switch to digest...') ?></option>
					<option value="qmcTagCloud"><?php echo __('Show tag cloud...') ?></option>
					<option value="qmcAddLabel"><?php echo __('Create label...') ?></option>
					<option value="qmcAddFilter"><?php echo __('Create filter...') ?></option>
					<option value="qmcHKhelp"><?php echo __('Keyboard shortcuts help') ?></option>
			</select>
		</div>

		</div>

	</div>

	<div id="headlines-wrap-inner" dojoType="dijit.layout.BorderContainer" region="center">

		<div id="headlines-toolbar" dojoType="dijit.layout.ContentPane" region="top">
		</div>

		<div id="headlines-frame" dojoType="dijit.layout.ContentPane" 
				onscroll="headlines_scroll_handler(this)" region="center">
			<div id="headlinesInnerContainer">
				<div class="whiteBox"><?php echo __('Loading, please wait...') ?></div>
			</div>
		</div>

		<?php if (!get_pref($link, 'COMBINED_DISPLAY_MODE')) { ?>
		<div id="content-insert" dojoType="dijit.layout.ContentPane" region="bottom"
			style="height : 50%" splitter="true"></div>
		<?php } ?>

	</div>
</div>
</div>

<!-- <div id="footer" dojoType="dijit.layout.ContentPane" region="bottom">
	<a href="http://tt-rss.org/">Tiny Tiny RSS</a>
	<?php if (!defined('HIDE_VERSION')) { ?>
		 v<?php echo VERSION ?> 
	<?php } ?>
	&copy; 2005&ndash;<?php echo date('Y') ?> <a href="http://fakecake.org/">Andrew Dolgov</a>
</div> -->

</div>

<?php db_close($link); ?>

</body>
</html>
