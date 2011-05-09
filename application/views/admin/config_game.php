		<!-- BEGIN REGISTRATION FORM -->
    <div id="center-column">
        <?php include_once('admin_breadcrumb.php'); ?>
        <h1><?php echo($subTitle); ?></h1>
        <br />
        <div class='textbox'>
        <table cellpadding="0" cellspacing="0" border="0" style="width:825px;">
        <tr class='title'>
            <td style='padding:3px' colspan="2">Enter settings information below</td>
        </tr>
        <tr>
            <td>
			<?php 
            $errors = validation_errors();
            if ($errors) {
                echo '<span class="error">The following errors were found with your submission:<br/ ><b>'.$errors.'</b><br clear="all" /></span><br /><br />';
            }
			if ($outMess) {
                echo $outMess;
            }
            $form = new Form();
            $form->open('admin/configGame','configGame');
            $form->br();
           	$form->fieldset('General Details');
            $form->text('site_name','Site name','required|trim',($input->post('site_name') ? $input->post('site_name') : $config['site_name']),array("class"=>"longtext"));
            $form->space();
            $form->text('ootp_league_name','OOTP League Name','required|trim',($input->post('ootp_league_name') ? $input->post('ootp_league_name') : $config['ootp_league_name']),array("class"=>"longtext"));
            $form->space();
            $form->text('ootp_league_abbr','OOTP League Abbreviation','required',($input->post('ootp_league_abbr') ? $input->post('ootp_league_abbr') : $config['ootp_league_abbr']),array("class"=>"longtext"));
            $form->br();
           	$form->text('ootp_league_id','OOTP League ID','required',($input->post('ootp_league_id') ? $input->post('ootp_league_id') : $config['ootp_league_id']));
            $form->space();
            $form->select('primary_contact|primary_contact',$adminList,'Primary Contact',($this->input->post('primary_contact')) ? $this->input->post('primary_contact') : $config['primary_contact'],'required');
			$form->br();
			$form->fieldset('Tools');
			$responses[] = array('1','Enabled');
			$responses[] = array('-1','Disabled');       
			$form->fieldset('',array('class'=>'radioGroup'));
			$form->radiogroup ('stats_lab_compatible',$responses,'StatsLab Compatibility Mode',($this->input->post('stats_lab_compatible') ? $this->input->post('stats_lab_compatible') : $config['stats_lab_compatible']),'required');
			$form->fieldset();
            $form->span('Enable this option if you are running <b>StatsLab</b> using the same <em>MySQL File Load Path</em> as this fantasy league mod.',array('class'=>'field_caption'));
           	$form->space();
			$form->fieldset('',array('class'=>'radioGroup'));
			$form->radiogroup ('google_analytics_enable',$responses,'Google Analytics',($this->input->post('google_analytics_enable') ? $this->input->post('google_analytics_enable') : $config['google_analytics_enable']),'required');
			$form->fieldset();
            $form->text('google_analytics_tracking_id','Google Analytics Tracking ID','trim',($input->post('google_analytics_tracking_id') ? $input->post('google_analytics_tracking_id') : $config['google_analytics_tracking_id']));
			$form->space();
            $form->fieldset('URLs and Paths');
			$form->span('<b style="color:#c00">WARNING</b>: Do not change these paths unless you have moved your files for some reason.');
           	$form->space();
            $form->text('fantasy_web_root','Fantasy League Root URL','required|trim',($input->post('fantasy_web_root') ? $input->post('fantasy_web_root') : $config['fantasy_web_root']),array("class"=>"longtext"));
            $form->space();
            $form->text('ootp_html_report_path','HTML Reports URL','required|trim',($input->post('ootp_html_report_path') ? $input->post('ootp_html_report_path') : $config['ootp_html_report_path']),array("class"=>"longtext"));
            $form->nobr();
			$form->span("Web URL to your OOTP HTML reports folder.",array('class'=>'field_caption'));
			$form->space();
           	$form->text('sql_file_path','MySQL File Load Path','required|trim',($input->post('sql_file_path') ? $input->post('sql_file_path') : $config['sql_file_path']),array("class"=>"longtext"));
            $form->nobr();
			$form->span("Server path to MySQL Data Upload Dir. NO TRAILING SLASH.",array('class'=>'field_caption'));
			$form->space();
           	$form->text('ootp_html_report_root','HTML Report File Path','required|trim',($input->post('ootp_html_report_root') ? $input->post('ootp_html_report_root') : $config['ootp_html_report_root']),array("class"=>"longtext"));
            $form->nobr();
			$form->span("Server path to MySQL Data Upload Dir. NO TRAILING SLASH.",array('class'=>'field_caption'));
			$form->space();
           	 $form->fieldset('File Settings');
			$form->text('max_sql_file_size','Max SQL File Size','required|trim|number',($input->post('max_sql_file_size') ? $input->post('max_sql_file_size') : $config['max_sql_file_size']));
            $form->space();
           	$form->span('Specify Max File Size in Megabytes',array('class'=>'field_caption'));
           	$form->space();
           	$form->fieldset('',array('class'=>'button_bar'));
            $form->submit('Submit');
            echo($form->get());
            ?>
            </td>
        </tr>
        </table>
        </div>
    </div>
    <p /><br />