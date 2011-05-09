<script type="text/javascript" charset="UTF-8">
	var ajaxWait = '<img src="<?php echo($config['fantasy_web_root']); ?>images/icons/ajax-loader.gif" width="28" height="28" border="0" align="absmiddle" />&nbsp;Operation in progress. Please wait...';
	var responseError = '<img src="<?php echo($config['fantasy_web_root']); ?>images/icons/icon_fail.png" width="24" height="24" border="0" align="absmiddle" />&nbsp;';
	var fader = null;
	var refreshAfterUpdate = false;
	
	$(document).ready(function(){
		$('a[rel=avail]').click(function () {
			refreshAfterUpdate = true;
			runAjax("<?php echo($config['fantasy_web_root']); ?>admin/availablePlayers/"); return false;
		});
		$('a[rel=upavail]').click(function () {
			runAjax("<?php echo($config['fantasy_web_root']); ?>admin/updatePlayers/"); return false;
		});
		$('a[rel=elidg]').click(function () {
			runAjax("<?php echo($config['fantasy_web_root']); ?>admin/elidgibility"); return false;
		});
		$('a[rel=sched]').click(function () {
			runAjax("<?php echo($config['fantasy_web_root']); ?>admin/scoringSchedule"); return false;
		});
		$('a[rel=games]').click(function () {
			runAjax("<?php echo($config['fantasy_web_root']); ?>admin/generateSchedules"); return false;
		});
		$('a[rel=sql]').click(function () {
			refreshAfterUpdate = true;
			runAjax("<?php echo($config['fantasy_web_root']); ?>admin/loadSQLFiles"); return false;
		});
		$('a[rel=dataUpdate]').click(function () {
			refreshAfterUpdate = true;
			runAjax("<?php echo($config['fantasy_web_root']); ?>admin/dataUpdate"); return false;
		});
		$('a[rel=configUpdate]').click(function () {
			refreshAfterUpdate = true;
			runAjax("<?php echo($config['fantasy_web_root']); ?>admin/configUpdate"); return false;
		});
		$('a[rel=reset]').click(function () {
			refreshAfterUpdate = true;
			if (confirm("Are you sure you want to perform this operation? This will reset the entire season to it's starting point and wipe out ALL season stats and data.")) {
				if (confirm("Are you sure you want to do this? This operation CANNOT be undone."))
					runAjax("<?php echo($config['fantasy_web_root']); ?>admin/resetSeason"); 
			}
			return false;
		});
		$('a[rel=sim]').click(function () {
			refreshAfterUpdate = true;
			runAjax("<?php echo($config['fantasy_web_root']); ?>admin/processSim"); return false;
		});
	});
	function runAjax (url) {
		//clearTimeout(fader);
		$('div#activeStatus').removeClass('error');
		$('div#activeStatus').removeClass('success');
		$('div#activeStatus').html(ajaxWait);
		$('div#activeStatusBox').fadeIn("slow");
		$.getJSON(url, function(data){
			error = false;
			if (data.status.indexOf(":") != -1) {
				var status = data.status.split(":");
				$('div#activeStatus').addClass(status[0].toLowerCase());
				var response = status[1];
				if (status[0].toLowerCase() == "error") {
					response = responseError + response;
					error = true;
				}
				$('div#activeStatus').html(response);
			} else {
				$('div#activeStatus').addClass('success');
				$('div#activeStatus').html('Operation Completed Successfully');
			}
			if (!error && refreshAfterUpdate) {
				setTimeout('refreshPage()',3000);
			}
			//setTimeout('fadeStatus("active")',15000);
		});
	}
	function fadeStatus(type) {
		$('div#'+type+'StatusBox').fadeOut("normal",function() { clearTimeout(fader); $('div#'+type+'StatusBox').hide(); });
	}
	function refreshPage() { 
		document.location.href = '<?php echo($_SERVER['PHP_SELF']); ?>';
	}
</script>
<div id="single-column" class="dashboard">
<h1>Welcome to the Dashboard, <?php echo($name); ?></h1>
    <?php if (isset($email) && !empty($email)) { ?>
    Your are currently logged in as: <b><?php echo($email); ?></b>
    <?php } ?>
    <br />
	<?php
    if (isset($message) && !empty($message)) { ?>
    <?php echo($message); ?>
    <br />
    <?php } ?>
</div>



<div id="center-column" class="dashboard">
<?php if (isset($installWarning)) { ?>
<span class="error"><?php echo($install_message); ?></span>
<br />
<?php } else { ?>
    
    <div class='textbox'>
    <table cellpadding="0" cellspacing="0" border="0" style="width:625px;">
    <tr class='title'>
    	<td style='padding:3px'>Admin Functions</td>
    </tr>
    <tr>
    	<td class="hsc2_l" style='padding:3px'>
		<div id="activeStatusBox"><div id="activeStatus"></div></div>	
        <h3>Database Functions</h3>
        <?php
		if (!file_exists($this->params['config']['sql_file_path']) || !is_readable($this->params['config']['sql_file_path'])) {
			echo('<span class="error"><strong>Warning:</strong> The SQL file path on record either does not exist or cannot be read. The SQL file folder must be setup correctly before you can proceed with uploading OOTP data to the site.</span>');
		} else { ?>
        Using <strong>Load All SQL Data Files</strong> below may take serveral minutes to load all your OOTP data files. Please be patient when running this function. For more precise control over what files are loaded, use the <strong>Load Individual SQL Files</strong> option instead.
        <ul class="iconmenu">
            <li><?php echo anchor('#','<img src="'.$config['fantasy_web_root'].'images/icons/database_up.png" width="48" height="48" border="0" />',array('rel'=>'sql')); ?><br />
            Load All SQL Data Files</li>
			<li><?php echo anchor('admin/listSQLFiles','<img src="'.$config['fantasy_web_root'].'images/icons/database_search.png" width="48" height="48" border="0" />'); ?><br />
            Load Individual SQL Files</li>
            
        </ul>
        <?php } ?>
        <br clear="all" /><br />
        <h3>Settings</h3>
        <?php if (isset($settingsError) && !empty($settingsError)) { ?>
        <span class="error"><?php echo($settingsError); ?></span><br />
        <?php } ?>
		<ul class="iconmenu">
			<?php 
			$hidden_funcs = false; 
			if ((isset($league_info) && $league_info->current_date <= $league_info->start_date) || !isset($league_info)) { ?>
        	<li><?php echo anchor('admin/configGame','<img src="'.$config['fantasy_web_root'].'images/icons/window_edit.png" width="48" height="48" border="0" />'); ?><br />
            Global Settings</li>
            <li><?php echo anchor('admin/configFantasy','<img src="'.$config['fantasy_web_root'].'images/icons/window_edit.png" width="48" height="48" border="0" />'); ?><br />
            Fantasy Settings</li>
            <li><?php echo anchor('admin/configRosters','<img src="'.$config['fantasy_web_root'].'images/icons/users.png" width="48" height="48" border="0" />'); ?><br />
            Rosters Rules Settings</li>
			<li><?php echo anchor('admin/configScoringRules','<img src="'.$config['fantasy_web_root'].'images/icons/application_edit.png" width="48" height="48" border="0" />'); ?><br />
            Scoring Rules Settings</li>
            <?php } else { ?>
            <li><?php echo anchor('admin/configInfo','<img src="'.$config['fantasy_web_root'].'images/icons/window_lock.png" width="48" height="48" border="0" />'); ?><br />
            Review Settings</li>
            <?php $hidden_funcs = true;
			}
			?>
			<li><?php echo anchor('admin/configSocial','<img src="'.$config['fantasy_web_root'].'images/icons/facebook-64x64.png" width="48" height="48" border="0" />'); ?><br />
            Social Media Settings</li>
            <?php 
			if (defined('ENV') && ENV != "live") { ?>
			<li><?php echo anchor('admin/configOOTP','<img src="'.$config['fantasy_web_root'].'images/icons/window_edit.png" width="48" height="48" border="0" />'); ?><br />
            Date/Scoring Period Settings</li>
			<?php 
			}
			if ($hidden_funcs) { ?>
            <li><img src="<?php echo(PATH_IMAGES); ?>icons/stock_new-appointment.png" width="24" height="24" border="0" align="left" alt="" title="" /><span style="color:#c00;text-align:left;">More functions are available once your league setup is more complete.</span>
			<?php }
			?>
        </ul>
        <br clear="all" /><br />
        <?php if (isset($league_info) && $league_info->current_date <= $league_info->start_date) { ?>
        <h3>Pre-Season Functions</h3>
        <?php if ($playerCount == 0) { 
		echo('<br /><span class="error" style="margin:0px; width:90%;"><strong>Warning:</strong> No players have been loaded into the database. Many site functions will not work correctly until this is done.</span><br />'); } ?>
        <ul class="iconmenu">
           	<?php 
			$hidden_funcs = false; 
			//if (!$in_season) { ?>
            <li><?php echo anchor('#','<img src="'.$config['fantasy_web_root'].'images/icons/database_remove.png" width="48" height="48" border="0" />',array('rel'=>'reset')); ?><br />
            Reset game to Pre-season</li>
            <?php //} ?>
            <?php if (isset($league_info)) { ?>
            <li><?php echo anchor('#','<img src="'.$config['fantasy_web_root'].'images/icons/users.png" width="48" height="48" border="0" />',array('rel'=>'avail')); ?><br />
            Import Available Players</li>
            <li><?php echo anchor('#','<img src="'.$config['fantasy_web_root'].'images/icons/calendar_empty.png" width="48" height="48" border="0" />',array('rel'=>'sched')); ?><br />
            Generate Scoring Schedule</li>
            <?php } else { 
				$hidden_funcs = true;
			} ?>
            <?php if (isset($leagues) && sizeof($leagues) > 0) { ?>
            <li><?php echo anchor('#','<img src="'.$config['fantasy_web_root'].'images/icons/calendar.png" width="48" height="48" border="0" />',array('rel'=>'games')); ?><br />
            Generate Schedules for Leagues</li>
            <?php } else { 
				$hidden_funcs = true;
			} 
			if ($hidden_funcs) { ?>
            <li><img src="<?php echo(PATH_IMAGES); ?>icons/stock_new-appointment.png" width="24" height="24" border="0" align="left" alt="" title="" /><span style="color:#c00;text-align:left;">More functions are available once your league setup is more complete.</span>
			<?php }
			?>
        </ul>
        <br clear="all" /><br />
        <?php } ?>
        <?php if ($in_season && isset($league_info)) { ?>
        <h3>Regular Season Functions</h3>
		Some of the folowing functions will likely take some time as all players in the league will be processed. Please be 
		patient when running these opertions.
        <br clear="all" /><br />
        <ul class="iconmenu">
            <?php if ($config['last_process_time'] < $config['last_sql_load_time'] && $league_info->current_date > $league_info->start_date) { ?>
            <li><?php echo anchor('#','<img src="'.$config['fantasy_web_root'].'images/icons/process.png" width="48" height="48" border="0" />',array('rel'=>'sim')); ?><br />
            Process Current Sim Results</li><?php } ?>
			<li><?php echo anchor('#','<img src="'.$config['fantasy_web_root'].'images/icons/repeat.png" width="48" height="48" border="0" />',array('rel'=>'upavail')); ?><br />
            Update Players</li>
            <li><?php echo anchor('#','<img src="'.$config['fantasy_web_root'].'images/icons/baseball-icon.png" width="48" height="48" border="0" />',array('rel'=>'elidg')); ?><br />
           Update Player Elidgibility</li>
        </ul>
        <?php } ?>
        <?php if (isset($config['bug_db'])) { ?>
        <br clear="all" /><br />
        <h3>Project/Bug Tracking Database</h3>
        <ul class="iconmenu">
            <li><?php echo anchor('/search/projects','<img src="'.$config['fantasy_web_root'].'images/icons/database_search.png" width="48" height="48" border="0" />'); ?><br />
            View Project List</li>
            <li><?php echo anchor('/search/bugs','<img src="'.$config['fantasy_web_root'].'images/icons/database_search.png" width="48" height="48" border="0" />'); ?><br />
            View Bug Database</li>
            <li><?php echo anchor('/bug_resolve','<img src="'.$config['fantasy_web_root'].'images/icons/process_accept.png" width="48" height="48" border="0" />'); ?><br />
            Resolve a bug</li>
        </ul>
        <?php } ?>
        </td>
    </tr>
    </table>
    </div>
    <?php } ?>
</div>

<div id="right-column">

	<?php if (isset($dataUpdate) || isset($configUpdate)) { ?>
	<div class='textbox'>
    <table cellpadding="0" cellspacing="0" border="0" style="width:235px;">
    <tr class='title'>
    	<td style='padding:3px; color:#FF0'>UPDATE NOTICE</td>
    </tr>
    <tr>	
		<td class="hsc2_l" style='padding:6px'>
        <?php if (isset($dataUpdate) || isset($configUpdate)) { ?>
        <span class="error" style="margin:0px; width:90%;"><b>Update Required</b>
        <br /><br />
        An update is required to your Fantasy League Web Site. The following updates are currently required:
        <ul>
        <?php if (isset($dataUpdate)) { ?>
        	<li><?php echo anchor('#','MySQL database',array('rel'=>'dataUpdate')); ?>.</li>
        <?php } ?>
        <?php if (isset($configUpdate)) { ?>
        	<li><?php echo anchor('#','Site config files',array('rel'=>'configUpdate')); ?>.</li>
        <?php } ?>
        </ul>
        </span><br /><br />
		<?php
		}
		?>
       </td>
    </tr>
    </table>
    </div>
	<?php
	}
	?>

	<?php 
	if (isset($league_info) && $config['last_sql_load_time'] != EMPTY_DATE_TIME_STR && isset($missingTables) && sizeof($missingTables) > 0) { ?>
    <div class='textbox'>
    <table cellpadding="0" cellspacing="0" border="0" style="width:235px;">
    <tr class='title'>
    	<td style='padding:3px; color:#FF0'>MySQL DATA WARNING</td>
    </tr>
    <tr>
    	<td class="hsc2_l" style='padding:6px'>
        <span class="error" style="margin:0px; width:90%;"><b>Required tables missing!</b>
        <br /><br />
        The following <b>required</b> OOTP MySQL data tables were not found in your database. 
        Please assure all required tables list on the individual 
        <?php echo anchor('admin/listSQLFiles','SQL file list'); ?> page are loaded before proceeding 
        with your game.
        <br /><br />
       <ul><?php foreach ($missingTables as $tableName) {
        	echo("<li><b>".$tableName."</b></li><br />");
	   } ?>
       </ul>
       </span>
        </td>
    </tr>
    </table>
    </div>
    <?php 
	} 
	?>
	<div class='textbox'>
    <table cellpadding="0" cellspacing="0" border="0" style="width:235px;">
    <tr class='title'>
    	<td style='padding:3px'>Game Stats</td>
    </tr>
    <tr>
    	<td class="hsc2_l" style='padding:6px'>
        <?php if (isset($league_info)) { ?>
        <b>Current League Date:</b><br /> <?php echo(date('m/d/Y',strtotime($league_info->current_date))); ?><br />
        
        <br /><b>Current Scoring period:</b><br /> 
		<?php if (isset($currPeriod['id']) && $currPeriod['id'] != -1) { 
		echo($currPeriod['id']." ".date('m/d',strtotime($currPeriod['date_start']))." - ".date('m/d',strtotime($currPeriod['date_end']))); ?><br />
      	 <?php } else if (!isset($currPeriod['id']) && $league_info->current_date == $league_info->start_date) {
        echo("1 ".date('m/d',strtotime($league_info->current_date))); ?><br />
        <?php } else { ?>
        The current scoring period will appear once the OOTP season begins.<br />
        <?php } ?>
        
        <br /><b>Total Scoring periods:</b>
        <?php if (isset($periodCount) && $periodCount != 0) { 
			echo($periodCount."<br />");
			echo anchor('admin/configScoringPeriods','View/Edit Scoring Period Schedule')."<br />"; 
		} else { 
        echo('<br /><span class="error" style="margin:0px; width:90%;"><strong>Warning:</strong> No scoring periods were found.</span>'); } ?>
         
        <br /><b>Last SQL Data Upload:</b><br /> <?php echo(date('m/d/Y h:m:s A',strtotime($config['last_sql_load_time']))); ?> <br />
        <br /><b>Last Sim Processed:</b><br /> <?php if ($config['last_process_time'] != EMPTY_DATE_TIME_STR) { echo(date('m/d/Y h:m:s A',strtotime($config['last_process_time']))); }
		else { echo("No scoring periods processed yet."); } ?>
        <br /><br /><b>OOTP Players Loaded:</b> 
        <?php if ($playerCount > 0) { echo($playerCount); }
		else { echo('<br /><span class="error" style="margin:0px; width:90%;"><strong>Warning:</strong> Players not loaded</span>'); } ?>
        <?php 
		} else { ?>
        <span class="error" style="margin:0px; width:90%;"><strong>League Files not loaded</strong>
        <br /><br />
		You need to load your game's MySQL database files using the <strong>Database Functions</strong> tools on this page to view stats based on your game.</span>
        <?php } ?>
        </td>
    </tr>
    </table>
    </div>
    <br clear="all" />
	<div class='textbox'>
    <table cellpadding="0" cellspacing="0" border="0" style="width:235px;">
    <tr class='title'>
    	<td style='padding:3px'>News</td>
    </tr>
    <tr>
    	<td class="hsc2_l" style='padding:6px'>
        <img src="<?php echo($config['fantasy_web_root']); ?>images/icons/icon_add.gif" width="16" height="16" border="0" alt="Add" title="add" align="absmiddle" /> 
		<?php echo anchor('/news/submit/mode/add/type_id/'.NEWS_FANTASY_GAME, 'Add News for Site'); ?>
        <p />
        <img src="<?php echo($config['fantasy_web_root']); ?>images/icons/icon_add.gif" width="16" height="16" border="0" alt="Add" title="add" align="absmiddle" /> 
		<?php echo anchor('/news/submit/mode/add/type_id/'.NEWS_PLAYER, 'Add Player News'); ?>
       	<p />
        <img src="<?php echo($config['fantasy_web_root']); ?>images/icons/icon_search.gif" width="16" height="16" border="0" alt="Add" title="add" align="absmiddle" /> 
		<?php echo anchor('/search/news/', 'Browse All News'); ?>
       
        </td>
    </tr>
    </table>
    </div>
    <br clear="all" />
	<div class='textbox'>
    <table cellpadding="0" cellspacing="0" border="0" style="width:235px;">
    <tr class='title'>
    	<td style='padding:3px'>All Leagues</td>
    </tr>
    <tr class='headline'>
    	<td style='padding:3px'>* = private league</td>
    </tr>
    <tr>
    	<td class="hsc2_l">
        <?php if (isset($leagues) && sizeof($leagues) > 0) { ?>
        <ul id="league_list">
        	<?php foreach($leagues as $id => $details) { ?>
            <li>
            <?php if (isset($details['avatar']) && !empty($details['avatar'])) { ?>
            <img align="absmiddle" width="24" height="24" src="<?php echo(PATH_LEAGUES_AVATARS.$details['avatar']); ?>" border="0" alt="<?php echo($details['league_name']); ?>" title="<?php echo($details['league_name']); ?>" />
			<?php } ?>
			<?php echo(anchor('/league/home/'.$id, $details['league_name'])); if ($details['access_type'] != 1) { echo('*'); } ?></li>
        	<?php } ?>
        </ul>
        <?php } else { ?>
        	No Public Leagues are available at this time.
        <?php } ?>
        </td>
    </tr>
    </table>
    </div>
	<br clear="all" /><br />
    <div class='textbox'>
    <table cellpadding="0" cellspacing="0" border="0" style="width:235px;">
    <tr class='title'>
    	<td style='padding:3px'>Fantasy Mod Info</td>
    </tr>
    <tr class='headline'>
    	<td style='padding:3px'>Version Information</td>
    </tr>
    <tr>
    	<td class="hsc2_l" style='padding:6px'>
        <strong>Your Version:</strong>  <?php echo(SITE_VERSION); ?>
        <br />
        <?php 
		if (isset($version_check) && is_array($version_check) && sizeof($version_check) > 0) { ?>
        <span class="<?php echo($version_check[0]); ?>">
        <?php echo($version_check[1]); ?>
        </span>
        <?php } ?>
        </td>
    </tr>
    </table>
    </div>
	<br clear="all" /><br />

</div>

<br class="clear" />