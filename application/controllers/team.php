<?php
require_once('base_editor.php');
/**
 *	Team.
 *	The primary controller for Team manipulation and details.
 *	@author			Jeff Fox
 *	@dateCreated	04/04/10
 *	@lastModified	08/08/10
 *
 */
class team extends BaseEditor {

	/*--------------------------------
	/	VARIABLES
	/-------------------------------*/
	/**
	 *	SLUG.
	 *	@var $_NAME:Text
	 */
	var $_NAME = 'team';
	/**
	 *	SCORING PERIOD.
	 *	The current scoring period object.
	 *	@var $scoring_period:Array
	 */
	var $scoring_period = array();
	/**
	 *	RULES.
	 *	Array of rules for the league.
	 *	@var $rules:Array
	 */
	var $rules = array();
	/**
	 *	User Waivers.
	 *	TRUE if waivers enabled, FALSE if not
	 *	@var $userWaivers:Boolean
	 */
	var $userWaivers = false;
	/*--------------------------------
	/	C'TOR
	/-------------------------------*/
	/**
	 *	Creates a new instance of team.
	 */
	public function team() {
		parent::BaseEditor();
	}
	/**
	 *	INDEX.
	 *	The default handler when the controller is called.
	 *	Checks for an existing auth session, and if found,
	 *	redirects to the dashboard. Otherwise, it redirects 
	 *	to the login.
	 */
	public function index() {
		redirect('search/teams/');
	}
	/*---------------------------------------
	/	CONTROLLER SUBMISSION HANDLERS
	/--------------------------------------*/
	function init() {
		parent::init();
		$this->modelName = 'team_model';
		
		$this->views['EDIT'] = 'team/team_editor';
		$this->views['VIEW'] = 'team/team_info';
		$this->views['FAIL'] = 'team/team_message';
		$this->views['SUCCESS'] = 'team/team_message';
		$this->views['ADMIN'] = 'team/team_admin';
		$this->views['ADD_DROP'] = 'team/team_add_drop';
		$this->views['AVATAR'] = 'team/team_avatar';
		$this->views['STATS'] = 'team/team_stats';
		$this->views['TRANSACTIONS'] = 'team/team_transactions';
		$this->debug = false;
		
		$this->useWaivers = (isset($this->params['config']['useWaivers']) && $this->params['config']['useWaivers'] == 1) ? true : false;
	}
	
	public function admin() {
		$this->getURIData();
		$this->data['subTitle'] = "Team Admin";
		$this->load->model($this->modelName,'dataModel');
		$this->dataModel->load($this->uriVars['id']);
		$this->data['team_id'] = $this->uriVars['id'];
		$this->data['league_id'] = $this->dataModel->league_id;
		$this->makeNav();
		$this->params['content'] = $this->load->view($this->views['ADMIN'], $this->data, true);
	    $this->displayView();	
	}
	
	public function addDrop() {
		$this->getURIData();
		
		$this->enqueStyle('list_picker.css');
		
		$this->load->model($this->modelName,'dataModel');
		$this->dataModel->load($this->uriVars['id']);
		$this->data['team_id'] = $this->uriVars['id'];
		$this->data['league_id'] = $this->dataModel->league_id;
		
		// GET DRAFT STATUS
		$this->load->model('draft_model');
		$this->draft_model->load($this->dataModel->league_id,'league_id');
		$this->data['subTitle'] = "Add/Drop Players";
		if ($this->draft_model->completed != 1) {
			$this->data['theContent'] = "<b>ERROR</b><br /><br />Your league has not yet completed it's draft. This page will become available once the draft has been completed.";
			$this->params['content'] = $this->load->view($this->views['FAIL'], $this->data, true);
			
		} else {

			if (!function_exists('getCurrentScoringPeriod')) {
				$this->load->helper('admin');
			}
			$this->data['players'] = $this->dataModel->getBasicRoster($this->params['config']['current_period']);
			$this->data['team_name'] = $this->dataModel->teamname." ".$this->dataModel->teamnick;
			
			$this->data['scoring_period'] = getCurrentScoringPeriod($this->ootp_league_model->current_date);
			$returnVar= 'playerList';
			$this->data['list_type'] = (isset($this->uriVars['list_type'])) ? $this->uriVars['list_type'] : 2;
			if (isset($this->data['list_type']) && $this->data['list_type'] == 2) {
				$returnVar= 'formatted_stats';
			}
			if(isset($this->params['config']['useWaivers']) && $this->params['config']['useWaivers'] == 1) {
				$this->data['waiver_order'] = $this->dataModel->getWaiverOrder();
				$this->data['waiver_claims'] = $this->dataModel->getWaiverClaims();
			}
			
			$this->data[$returnVar] = $this->pullList(true,$this->dataModel->league_id, $this->data['list_type']);
			$this->data['league_id'] = $this->dataModel->league_id;
			$this->params['pageType'] = PAGE_FORM;
			$this->params['content'] = $this->load->view($this->views['ADD_DROP'], $this->data, true);
		}
		$this->makeNav();
		$this->displayView();
	}
	public function removeClaim() {
		if ($this->params['loggedIn']) {
			$this->getURIData();
			$this->loadData();
			if (isset($this->uriVars['id'])) {
				$this->db->where('id',$this->uriVars['id']);
				$this->db->delete('fantasy_teams_waiver_claims');
				$message = '<span class="success">The selected waiver claim has been successfully removed.</span>';
			} else {
				$error = true;
				$message = '<span class="error">A required claim identifier was not found. Please go back and try the operation again or contact the site adminitrator to report the problem.</span>';
			}
			$this->session->set_flashdata('message', $message);
			redirect('team/addDrop/id/'.$this->uriVars['team_id']);
		} else {
	        $this->session->set_flashdata('loginRedirect',current_url());	
			redirect('user/login');
	    }	
	}
	public function stats() {
		
		$this->getURIData();
		$this->data['subTitle'] = "Set lineup";
		$this->load->model($this->modelName,'dataModel');
		
		$team_id = -1;
		if (isset($this->uriVars['id']) && !empty($this->uriVars['id']) && $this->uriVars['id'] != -1) {
			$team_id = $this->uriVars['id'];
		} else if (isset($this->uriVars['team_id']) && !empty($this->uriVars['team_id']) && $this->uriVars['team_id'] != -1) {
			$team_id = $this->uriVars['team_id'];
		} 
		$this->dataModel->load($team_id);
		$this->data['thisItem']['team_id'] = $this->dataModel->id;
		$this->data['thisItem']['teamname'] = $this->dataModel->teamname;
		$this->data['thisItem']['teamnick'] = $this->dataModel->teamnick;
		$this->data['thisItem']['avatar'] = $this->dataModel->avatar;
		$this->data['team_id'] = $team_id;	
		$this->data['year'] = date('Y');
		$this->data['league_id']  = $this->dataModel->league_id;
		
		
		$this->prepForQuery();
		
		$this->data['batters'] = $this->dataModel->getBatters(-1, false, -999);
		$this->data['pitchers'] = $this->dataModel->getPitchers(-1, false, -999);
		
		if (sizeof($this->data['batters']) > 0 && sizeof($this->data['pitchers']) > 0) {
		
			$stats['batters'] = $this->player_model->getStatsforPeriod(1, $this->scoring_period, $this->rules,$this->data['batters']);
			$stats['pitchers'] = $this->player_model->getStatsforPeriod(2, $this->scoring_period, $this->rules,$this->data['pitchers']);
			
			$this->data['title'] = array();
			$this->data['formatted_stats'] = array();
			$this->data['limit'] = -1;
			$this->data['startIndex'] = 0;
			
			$this->data['title']['batters'] = "Batting";
			$this->data['colnames']=player_stat_column_headers(1, QUERY_STANDARD, true);
			$this->data['fields'] = player_stat_fields_list(1, QUERY_STANDARD, true);
			$this->data['player_stats'] = formatStatsForDisplay($stats['batters'], $this->data['fields'], $this->params['config'],$this->data['league_id']);
			$this->data['showTeam'] = -1;
			$this->data['formatted_stats']['batters'] = $this->load->view($this->views['STATS_TABLE'], $this->data, true);
	
			$this->data['title']['pitchers'] = "Pitching";
			$this->data['colnames']=player_stat_column_headers(2, QUERY_STANDARD, true);
			$this->data['fields'] = player_stat_fields_list(2, QUERY_STANDARD, true);
			$this->data['player_stats'] = formatStatsForDisplay($stats['pitchers'], $this->data['fields'], $this->params['config'],$this->data['league_id']);
			$this->data['formatted_stats']['pitchers'] = $this->load->view($this->views['STATS_TABLE'], $this->data, true);
		} else {
			$this->data['message']= "The ".$this->dataModel->teamname." roster is incomplete. No statsare available atthis time.";
		}
		$this->data['league_id'] = $this->dataModel->league_id;
		if (isset($this->data['league_id']) && $this->data['league_id'] != -1) {
			$this->data['thisItem']['fantasy_teams'] = getFantasyTeams($this->data['league_id']);
		}
		$this->makeNav();
		$this->params['pageType'] = PAGE_FORM;
		$this->params['subTitle'] = $this->data['subTitle'] = $this->dataModel->teamname." ".$this->dataModel->teamnick." Stats";
		$this->params['content'] = $this->load->view($this->views['STATS'], $this->data, true);
	    $this->displayView();
	}
	public function transactions() {
		$this->getURIData();
		$this->load->model($this->modelName,'dataModel');
		$team_id =-1;
		if(isset($this->uriVars['id'])) {
			$league_id = $this->uriVars['id'];
		} else if (isset($this->uriVars['team_id'])) {
			$team_id =$this->uriVars['team_id'];
		}
		$this->data['team_id'] = $team_id;
		$this->dataModel->load($team_id);
		$this->league_model->load($this->dataModel->league_id);
		$this->data['league_id'] = $this->dataModel->league_id;
		
		if (isset($this->data['league_id']) && $this->data['league_id'] != -1) {
			$this->data['thisItem']['fantasy_teams'] = getFantasyTeams($this->dataModel->league_id);
		}
		$this->data['limit'] = $limit = (isset($this->uriVars['limit'])) ? $this->uriVars['limit'] : 20;
		$this->data['pageId'] = $pageId = (isset($this->uriVars['pageId'])) ? $this->uriVars['pageId'] : 1;																			   
		
		$startIndex = 0;
		if ($limit != -1) {
			$startIndex = ($limit * ( - 1))-1;
		}
		if ($startIndex < 0) { $startIndex = 0; }
		$this->data['startIndex'] = $startIndex;
		$this->data['thisItem']['teamList'] = $this->league_model->getTeamDetails();
		$this->data['recCount'] = sizeof($this->league_model->getLeagueTransactions(-1, 0,$this->dataModel->id,$this->dataModel->league_id));
		$this->data['thisItem']['transactions'] = $this->league_model->getLeagueTransactions($this->data['limit'],$this->data['startIndex'],$this->dataModel->id,$this->dataModel->league_id);
		//echo("Transaction count = ".sizeof($this->data['thisItem']['transactions'])."<br />");
		$this->data['pageCount'] = 1;
		if ($limit != -1) {
			$this->data['pageCount'] = intval($this->data['recCount'] / $limit);
		}
		if ($this->data['pageCount'] < 1) { $this->data['pageCount'] = 1; }
		$this->params['subTitle'] = "Transactions";
		
		$this->data['thisItem']['subTitle'] = $this->dataModel->teamname." ".$this->dataModel->teamnick." Transactions";
			
		
		$this->makeNav();
		$this->data['transaction_summary'] = $this->load->view($this->views['TRANSACTION_SUMMARY'], $this->data, true);
		$this->params['content'] = $this->load->view($this->views['TRANSACTIONS'], $this->data, true);
	    $this->params['pageType'] = PAGE_FORM;
		$this->displayView();	
	}
	
	
	public function processTransaction() {
		$this->getURIData();
		$this->load->model($this->modelName,'dataModel');
		
		if (!function_exists('getCurrentScoringPeriod')) {
			$this->load->helper('admin');
		}
		$error = false;
		$result = '{';
		$code= -1;
		$status = '';
		if (!isset($this->uriVars['team_id']) || (!isset($this->uriVars['add']) 
			&& !isset($this->uriVars['drop']))) {
			$status = "error:Required params missing";
			$code = 501;
			$error = true;
		} else {
			$this->dataModel->load($this->uriVars['team_id']);
			
			if (!isset($this->params['currUser'])) {
				$this->params['currUser'] = (!empty($this->user_auth_model->id)) ? $this->user_auth_model->id : -1;
			}
			$addList = (isset($this->uriVars['add'])) ? explode("&",$this->uriVars['add']) : $this->uriVars['add'];
			$dropList = (isset($this->uriVars['drop'])) ? explode("&",$this->uriVars['drop']) : $this->uriVars['drop'];
			$tradeToList = isset($this->uriVars['tradeTo']) ? (strpos($this->uriVars['tradeTo'],"&") ? explode("&",$this->uriVars['tradeTo']) : $this->uriVars['tradeTo']) : '';	
			$tradeFromList = isset($this->uriVars['tradeFrom']) ? (strpos($this->uriVars['tradeFrom'],"&") ? explode("&",$this->uriVars['tradeFrom']) : $this->uriVars['tradeFrom']) : '';																  
																		  
			$teamRoster = $this->dataModel->getBasicRoster($this->params['config']['current_period']);
			
			$addSQL = array();
			$waiverSQL = array();
			$alreadyOnWaivers = array();
			$addStatusStr = "";
			//echo("Size of add list = ".sizeof($addList)."<br />");
			foreach($addList as $player) {
				$addError = false;
				$props = explode("_",$player);
				//echo("Player id = ".$props[0]."<br />");
				//echo("Size of teamRoster = ".sizeof($teamRoster)."<br />");
				if ($props[0] != -1) {
					foreach($teamRoster as $teamPlayer) {
						if ($props[0] == $teamPlayer['id']) {
							$addError = true;
							if (empty($addStatusStr)) { $addStatusStr = "addError:"; }
							if ($addStatusStr != "addError:") { $addStatusStr .= ","; }
							$addStatusStr .= $props[0];
							break;
						}
					}
					//echo("on team roster? = ".(($addError) ? 'true' : 'false')."<br />");
					if (!$addError) {
						$onWaivers = -1;
						$waiverClaims = array();
						if ($this->useWaivers) {
							// CHECK IF PLAYER IS ON WAIVERS
							if (!isset($this->player_model)) {
								$this->load->model('player_model');
							}
							$onWaivers = $this->player_model->getWaiverStatus($this->dataModel->league_id,$props[0]);
							$waiverClaims = $this->player_model->getWaiverClaims($this->dataModel->league_id,$props[0]);
						}
						//echo("on waivers? = ".$onWaivers."<br />");
						if ($onWaivers == -1) {
							$pos = get_pos_num($props[1]);
							if ($pos == 7 || $pos == 8 || $pos == 9) { $pos = 20; }
							else if ($pos == 13) { $pos = 12; }
							array_push($addSQL, array('player_id'=>$props[0],'league_id'=>$this->dataModel->league_id,
													  'team_id'=>$this->dataModel->id,'scoring_period_id'=>$this->params['config']['current_period'],
													  'player_position'=>$pos,'player_role'=>get_pos_num($props[2]),'player_status'=>-1));	
						} else {
							// CHECK FOR EXISTING CLAIM
							if (sizeof($waiverClaims) == 0 || (sizeof($waiverClaims) > 0 && !in_array($this->dataModel->id,$waiverClaims))) {
								// CREATE A WAIVER CLAIM	
								array_push($waiverSQL, array('player_id'=>$props[0],'league_id'=>$this->dataModel->league_id,
														 'team_id'=>$this->dataModel->id,'owner_id'=>$this->params['currUser']));
							} else {
								array_push($alreadyOnWaivers, $props[0]);
							} // END if
						} // END if
					} // END if
				} // END if
			} // END foreach
			//echo("Size of addSQL = ".sizeof($addSQL)."<br />");
			//echo("addStatusStr = '".$addStatusStr."'<br />");
			//echo("Size of drop list = ".sizeof($dropList)."<br />");
			$dropSQL = array();
			foreach($dropList as $player) {
				$props = explode("_",$player);
				if ($props[0] != -1) {
					array_push($dropSQL, array('player_id'=>$props[0],'team_id'=>$this->dataModel->id,
											   'scoring_period_id'=>$this->params['config']['current_period']));
				}
			}
			if (!function_exists('updateOwnership')) {
				$this->load->helper('roster');
			}
			//echo("Size of dropSQL = ".sizeof($dropSQL)."<br />");
			if (empty($addStatusStr)) {
				$playersAdded = array();
				foreach($addSQL as $data) {
					$this->db->insert('fantasy_rosters',$data);
					if ($this->db->affected_rows() == 1) {
						array_push($playersAdded,$data['player_id']);
					}
					$ownership = updateOwnership($data['player_id']);
					$pData = array('own'=>$ownership[0],'start'=>$ownership[1]);
					$this->db->flush_cache();
					$this->db->where('id',$data['player_id']);
					$this->db->update('fantasy_players',$pData); 
				}
				$waiverClaims = array();
				foreach($waiverSQL as $data) {
					$this->db->insert('fantasy_teams_waiver_claims',$data);
					if ($this->db->affected_rows() == 1) {
						array_push($waiverClaims,$data['player_id']);
					}
				}
				$playersDropped = array();
				foreach($dropSQL as $data) {
					$this->db->delete('fantasy_rosters',$data);
					if ($this->db->affected_rows() == 1) {
						array_push($playersDropped,$data['player_id']);
					}
					// IF WAIVER ENABLED, PUT PLAYER ON WAIVERS
					if ($this->useWaivers) {
						$waiverData = array('player_id'=>$data['player_id'],'league_id'=>$this->dataModel->league_id,
											'waiver_period'=>$this->params['config']['current_period']+1);
						$this->db->insert('fantasy_players_waivers',$waiverData);
					}
					$ownership = updateOwnership($data['player_id']);
					$pData = array('own'=>$ownership[0],'start'=>$ownership[1]);
					$this->db->flush_cache();
					$this->db->where('id',$data['player_id']);
					$this->db->update('fantasy_players',$pData); 
				}
				
				// LOG THE TRANSACTION
				$this->league_model->load($this->dataModel->league_id);
				$this->dataModel->logTransaction($playersAdded, $playersDropped, NULL, NULL, NULL, $this->league_model->commissioner_id, 
												 $this->params['currUser'], $this->params['accessLevel'] == ACCESS_ADMINISTRATE,
												 $this->params['config']['current_period']);
				
				if ($this->useWaivers) {
					if (!isset($this->player_model)) {
						$this->load->model('player_model');
					}
					 if (sizeof($waiverClaims) > 0) {
						$status = "notice:Your transaction was completed. Waivers claims were made for the following players ";
						$playerStr = "";
						foreach($waiverClaims as $playerId) {
							if (!empty($playerStr)) { $playerStr .= ";"; }
							$playerData = $this->player_model->getPlayerDetails($playerId);
							$playerStr .= get_pos($playerData['position'])." ".$playerData['first_name']." ".$playerData['last_name'];
						}
						$status .= $playerStr;
					}
					if (sizeof($alreadyOnWaivers) > 0) {
						if (empty($status)) { $status = "notice:Your transaction was completed. "; }
						$status .= "You already have waiver claims pending for the following players ";
						$playerStr = "";
						foreach($alreadyOnWaivers as $playerId) {
							if (!empty($playerStr)) { $playerStr .= ";"; }
							$playerData = $this->player_model->getPlayerDetails($playerId);
							$playerStr .= get_pos($playerData['position'])." ".$playerData['first_name']." ".$playerData['last_name'];
						}
						$status .= $playerStr;
					}
				}
				if (empty($status)) { $status = "OK"; }
				//echo("status = '".$status."'<br />");
				$code = 200;
			} else {
				$error = true;
				$code = 301;
				$status = $addStatusStr;
			}
		}
		if (!$error) {
			$result = $this->refreshPlayerList().',';
		}
		$result.='code:"'.$code.'",status:"'.$status.'"}';
		$this->output->set_header('Content-type: application/json'); 
		$this->output->set_output($result);
	}
	
	public function administrativeAdd() {
		$this->getURIData();
		if (!function_exists('getCurrentScoringPeriod')) {
			$this->load->helper('admin');
		}
		$result = '';
		$code= -1;
		$status = '';
		if (!isset($this->uriVars['team_id']) || !isset($this->uriVars['player_id']) 
			|| !isset($this->uriVars['curr_team']) || !isset($this->uriVars['league_id'])) {
			$status = "error:Required params missing.";
			$code = 501;
		} else {
			// LOG THE DROP TRANSACTION
			if ($this->uriVars['curr_team'] != -1) {
				$this->dataModel->logSingleTransaction($this->uriVars['player_id'], TRANS_TYPE_DROP, $this->league_model->commissioner_id, 
											 $this->params['currUser'], $this->params['accessLevel'] == ACCESS_ADMINISTRATE,
											 $this->uriVars['league_id'],$this->uriVars['curr_team']);
			}
			$update = false;
			$this->db->flush_cache();
			$this->db->select('id');
			$this->db->where('league_id',$this->uriVars['league_id']);
			$this->db->where('player_id',$this->uriVars['player_id']);
			$query = $this->db->get('fantasy_rosters');
			if ($query->num_rows() > 0) {
				$update = true;
			}
			$query->free_result();
			
			$this->db->flush_cache();
			$this->db->set('team_id',$this->uriVars['team_id']);
			if ($update) {
				$this->db->where('league_id',$this->uriVars['league_id']);
				$this->db->where('player_id',$this->uriVars['player_id']);
				if ($this->uriVars['curr_team'] != -1) {
					$this->db->where('team_id',$this->uriVars['curr_team']);
				}
				$this->db->where('scoring_period_id',$this->params['config']['current_period']);
				$this->db->update('fantasy_rosters');
			} else {
				$this->db->set('league_id',$this->uriVars['league_id']);
				$this->db->set('player_id',$this->uriVars['player_id']);
				$this->db->set('scoring_period_id',$this->params['config']['current_period']);
				$this->db->insert('fantasy_rosters');
			}
			if ($this->db->affected_rows() > 0) {
				if ($this->uriVars['curr_team'] != -1) {
					$this->dataModel->logSingleTransaction($this->uriVars['player_id'], TRANS_TYPE_ADD, $this->league_model->commissioner_id, 
												 $this->params['currUser'], $this->params['accessLevel'] == ACCESS_ADMINISTRATE,
												 $this->uriVars['league_id'],$this->uriVars['team_id']);
				}
			}
			$status = "OK";
			$code = 200;
			$result = '{result:"OK",code:"'.$code.'",status:"'.$status.'"}';
		}
		$this->output->set_header('Content-type: application/json'); 
		$this->output->set_output($result);
	}
	public function addAndDisplay() {
		
		$this->getURIData();
		if (!function_exists('getCurrentScoringPeriod')) {
			$this->load->helper('admin');
		}
		$result = '';
		$code= -1;
		$status = '';
		
		if (!isset($this->uriVars['team_id']) || !isset($this->uriVars['player_id']) 
			|| !isset($this->uriVars['position'])|| !isset($this->uriVars['role'])) {
			$status = "error:Required params missing";
			$code = 501;
		} else {
			//echo("team_id = ".$this->uriVars['team_id'].", player_id = ".$this->uriVars['player_id']."<br />");
			$onWaivers = -1;
			$waiverClaims = array();
			// IF ENABLED, CHECK WAIVER STATUS
			//echo("use waivers? ".$this->useWaivers."<br />");
			if ($this->useWaivers) {
				// CHECK IF PLAYER IS ON WAIVERS
				if (!isset($this->player_model)) {
					$this->load->model('player_model');
				}
				$onWaivers = $this->player_model->getWaiverStatus($this->uriVars['league_id'],$this->uriVars['player_id']);
				$waiverClaims = $this->player_model->getWaiverClaims($this->uriVars['league_id'],$this->uriVars['player_id']);
			}
			//echo("on waivers? ".$onWaivers."<br />");
			
			if ($this->useWaivers && $onWaivers != -1) {
				// CHECK FOR EXISTING CLAIM
				if (sizeof($waiverClaims) == 0 || (sizeof($waiverClaims) > 0 && !in_array($this->dataModel->id,$waiverClaims))) {
					$this->db->set('team_id',$this->uriVars['team_id']);
					$this->db->set('player_id',$this->uriVars['player_id']);
					$this->db->set('league_id',$this->uriVars['league_id']);
					$this->db->set('owner_id',$this->params['currUser']);
					$this->db->insert('fantasy_teams_waiver_claims');
					$status = "notice:The player is currently on waivers. A claim has been submitted for your team. It will be processed in waiver period ".$onWaivers;
				} else {
					$status = "notice:You have already placed a waiver claim for this player. It will be processed in waiver period ".$onWaivers;
				}
				$code = 200;
				$result = '{code:"'.$code.'",status:"'.$status.'"}';
			} else {
				// CHECK FOR DUPLICATE
				$this->db->select('id');
				$this->db->from('fantasy_rosters');
				$this->db->where('team_id',$this->uriVars['team_id']);
				$this->db->where('player_id',$this->uriVars['player_id']);
				$this->db->where('scoring_period_id',$this->params['config']['current_period']);
				$this->db->limit(1);
				$query = $this->db->get();
				if ($query->num_rows() > 0) {
					$status = "notice:The player is already on this team!";
					$code = 200;
				} else {
					$query->free_result();
					$this->db->flush_cache();
					if (isset($this->uriVars['league_id']) && !empty($this->uriVars['league_id']) && $this->uriVars['league_id'] != -1) {
						$this->db->set('league_id',$this->uriVars['league_id']);
					}
					$this->db->set('team_id',$this->uriVars['team_id']);
					$this->db->set('player_id',$this->uriVars['player_id']);
					$this->db->set('player_position',$this->uriVars['position']);
					$this->db->set('player_role',$this->uriVars['role']);
					$this->db->set('scoring_period_id',$this->params['config']['current_period']);
					$this->db->insert('fantasy_rosters');
					
					if (!function_exists('updateOwnership')) {
						$this->load->helper('roster');
					}
					$ownership = updateOwnership($this->uriVars['player_id']);
					$pData = array('own'=>$ownership[0],'start'=>$ownership[1]);
					$this->db->flush_cache();
					$this->db->where('id',$this->uriVars['player_id']);
					$this->db->update('fantasy_players',$pData); 
					
					// LOG THE TRANSACTION
					$this->dataModel->load($this->uriVars['team_id']);
					$this->league_model->load($this->dataModel->league_id);
					$this->dataModel->logTransaction(array($this->uriVars['player_id']), NULL, NULL, NULL, NULL, $this->league_model->commissioner_id, 
													 $this->params['currUser'], $this->params['accessLevel'] == ACCESS_ADMINISTRATE,
													 $this->params['config']['current_period']);
					
					$status = "OK";
					$code = 200;
				}
				$result = $this->refreshPlayerList().',code:"'.$code.'",status:"'.$status.'"}';
			}
		}
		$this->output->set_header('Content-type: application/json'); 
		$this->output->set_output($result);
	}
	public function removeAndDisplay() {
		$this->getURIData();
		$result = '';
		$status = '';
		if (!function_exists('getCurrentScoringPeriod')) {
			$this->load->helper('admin');
		}
		if (!isset($this->uriVars['team_id']) || !isset($this->uriVars['player_id'])) {
			$status = "error: Required params missing";
			$code = 501;
		} else {
			// CHECK FOR DUPLICATE
			$this->db->where('team_id',$this->uriVars['team_id']);
			$this->db->where('player_id',$this->uriVars['player_id']);
			$this->db->where('scoring_period_id',$this->params['config']['current_period']);
			$this->db->delete('fantasy_rosters');
			
			// IF WAIVER ENABLED, PUT PLAYER ON WAIVERS
			if ($this->useWaivers) {
				$this->dataModel->load($this->uriVars['team_id']);
				$waiverData = array('player_id'=>$this->uriVars['player_id'],'league_id'=>$this->dataModel->league_id,
									'waiver_period'=>$this->params['config']['current_period']+1);
				$this->db->insert('fantasy_players_waivers',$waiverData);
			}
					
			if (!function_exists('updateOwnership')) {
				$this->load->helper('roster');
			}
			$ownership = updateOwnership($this->uriVars['player_id']);
			$pData = array('own'=>$ownership[0],'start'=>$ownership[1]);
			$this->db->flush_cache();
			$this->db->where('id',$this->uriVars['player_id']);
			$this->db->update('fantasy_players',$pData);
			
			// LOG THE TRANSACTION
			$this->dataModel->load($this->uriVars['team_id']);
			$this->league_model->load($this->dataModel->league_id);
			$this->dataModel->logTransaction(NULL, array($this->uriVars['player_id']), NULL, NULL, NULL, $this->league_model->commissioner_id, 
												 $this->params['currUser'], $this->params['accessLevel'] == ACCESS_ADMINISTRATE,
												 $this->params['config']['current_period']);
					
			$status = "OK";
			$code = 200;
		}
		
		$result = $this->refreshPlayerList().',code:"'.$code.'",status:"'.$status.'"}';
		$this->output->set_header('Content-type: application/json'); 
		$this->output->set_output($result);
	}
	
	public function pullList($returnArray = false, $league_id = false, $list_type = 1) {
		$this->getURIData();
		$result = "";
		if (!isset($this->uriVars['league_id']) && $league_id != false) {
			$this->uriVars['league_id'] = $league_id;
		}
		if (!isset($this->uriVars['type']) || empty($this->uriVars['type'])) {
			$this->uriVars['type'] = "pos";
		}
		if (!isset($this->uriVars['param']) || empty($this->uriVars['param'])) {
			$this->uriVars['param'] = 2;
		}
		if (!isset($this->uriVars['list_type']) || empty($this->uriVars['list_type'])) {
			$this->uriVars['list_type'] = $list_type;
		}
		if (!isset($this->uriVars['limit']) || empty($this->uriVars['limit'])) {
			$this->uriVars['limit'] = -1;
		}
		if (!isset($this->uriVars['startIndex']) || empty($this->uriVars['startIndex'])) {
			$this->uriVars['startIndex'] = 0;
		}
		if (!isset($this->uriVars['pageId']) || empty($this->uriVars['pageId'])) {
			$this->uriVars['pageId'] = 1;
		}
		
		$this->data['list_type'] = $this->uriVars['list_type'];
		if (!function_exists('getFilteredFreeAgents')) {
			$this->load->helper('roster');
		}
		if ($this->uriVars['list_type'] == 2) {
			$this->prepForQuery();
			$player_type = 1;
			$title = "Batters";
			if ($this->uriVars['param'] == 11 || $this->uriVars['param'] == 12 || $this->uriVars['param'] == 13) {
				$player_type = 2;
				$title = "Pitchers";
			}
			$this->data['title']=$title;
			$this->data['limit']=$this->uriVars['limit'];
			$this->data['startIndex']=$this->uriVars['startIndex'];
			
			$this->data['colnames']=player_stat_column_headers($player_type, QUERY_BASIC, true, false, true);
			$this->data['fields'] = player_stat_fields_list($player_type, QUERY_BASIC, true, false, true);
		} else {
			$this->data['fields'] = array('id','player_name','pos','position','role','injury_is_injured','injury_dl_left');
		}
		//echo("List type= = ".$this->uriVars['list_type']."<br />");
		//echo("Search parsm = ".$this->uriVars['param']."<br />");
		//echo("Search type = ".$this->uriVars['type']."<br />");
		//echo("Return = ".(($returnArray === true) ? "true" : "false")."<br />");
		
		$this->data['years'] = $this->ootp_league_model->getAllSeasons();
		if (isset($this->uriVars['year'])) {
			$this->data['lgyear'] = $this->uriVars['year'];
		} else {
			$currDate = strtotime($this->ootp_league_model->current_date);
			$startDate = strtotime($this->ootp_league_model->start_date);
			if ($currDate <= $startDate) {
				$this->data['lgyear'] = (intval($this->data['years'][0]));
			} else {
				$this->data['lgyear'] = date('Y',$currDate);
			}
		}
		
		$results = getFreeAgentList($this->uriVars['league_id'], $this->uriVars['type'], 
								   $this->uriVars['param'], $this->params['config']['current_period'], 
								   $this->uriVars['list_type'],true,$this->scoring_period,$this->rules,
								   $this->uriVars['limit'],$this->uriVars['startIndex'], 
								   $this->params['config']['ootp_league_id'],$this->data['lgyear']);
		//echo("size of resault = ".sizeof($results)."<br />");
		if ($this->uriVars['list_type'] == 2) {
			$statsOnly = false;
			$showTrans = true;
			if ($returnArray !== true) {
				$statsOnly = true;
				$showTrans = false;
			}
			//echo("returnArray = ".((!$returnArray) ? "true" : "false")."<br />");
			//echo("stats only = ".(($statsOnly) ? "true" : "false")."<br />");
			//echo("showTrans = ".(($showTrans) ? "true" : "false")."<br />");
			$stats_results = formatStatsForDisplay($results, $this->data['fields'], $this->params['config'],$this->uriVars['league_id'],NULL,NULL,$statsOnly, $showTrans);
			if ($returnArray === true) {
				$this->data['player_stats'] = $stats_results;
				$this->data['recCount'] = sizeof(getFreeAgentList($this->uriVars['league_id'], $this->uriVars['type'], 
									   $this->uriVars['param'], $this->params['config']['current_period'], 
									   $this->uriVars['list_type'],true,$this->scoring_period,$this->rules,
									   -1,0,$this->params['config']['ootp_league_id'],true));
				$this->data['pageCount'] = 1;
				$this->data['pageId'] = $this->uriVars['pageId'];
				if ($this->uriVars['limit'] != -1) {
					$this->data['pageCount'] = intval($this->data['recCount'] / $this->uriVars['limit']);
				}
				$this->data['league_id'] = $this->uriVars['league_id'];
				$this->data['showTeam'] = -1;
				$this->data['showTrans'] = 1;			
				$stats_results = $this->load->view($this->views['STATS_TABLE'], $this->data, true);
			}
		} else {
			$stats_results = $results;
		}
		if ($returnArray === true) {
			return $stats_results;
		} else {
			$status = '';
			if (isset($stats_results) && sizeof($stats_results) > 0) {
				foreach ($stats_results as $row) {
					if ($result != '') { $result .= ","; }
					$result .= '{';
					$tmpResult = '';
					foreach($row as $key => $value) {
						if ($this->uriVars['list_type'] == 1 && !strpos($key,"injury")) {
							if ($key == "positions") {
								$value = makeElidgibilityString($value);
							}
							if ($key == "pos") {
								$value = get_pos($value);
							}
						}
						if ($tmpResult != '') { $tmpResult .= ','; }  // END if
						$tmpResult .= '"'.$key.'":"'.$value.'"';
					}
					// MAKE INJURY STRING
					$injStatus = "";
					if (isset($row['injury_is_injured']) && $row['injury_is_injured'] == 1) {
						$injStatus = makeInjuryStatusString($row);
					}
					$tmpResult .= ',"injStatus":"'.$injStatus.'"';	   
					$result .= $tmpResult.'}';
				} // END foreach
				if ($this->uriVars['list_type'] == 2) {
					$code = 300;
					$status .= 'stats:'.$this->data['colnames'].':';
				} else {
					$code = 200;
					$status .= "OK";
				}
			} // END if
			if (strlen($result) == 0) {
				$status .= "notice:No players found";
				$code = 201;
			} // END if
			$result = '{ result: { items: ['.$result.']},code:"'.$code.'",status: "'.$status.'"}';
			$this->output->set_header('Content-type: application/json'); 
			$this->output->set_output($result);
		}
	}
	public function avatar() {
		if ($this->params['loggedIn']) {
			$this->getURIData();
			$this->loadData();
			$this->data['avatar'] = $this->dataModel->avatar;
			$this->data['team_id'] = $this->dataModel->id;
			$this->data['teamname'] = $this->dataModel->teamname;
			$this->data['subTitle'] = 'Edit Team Avatar';
			
			//echo("Submitted = ".(($this->input->post('submitted')) ? 'true':'false')."<br />");
			if (!($this->input->post('submitted')) || ($this->input->post('submitted') && !isset($_FILES['avatarFile']['name']))) {
				if ($this->input->post('submitted') && !isset($_FILES['avatarFile']['name'])) {
					$fv = & _get_validation_object();
					$fv->setError('avatarFile','The avatar File field is required.');
				}
				$this->params['content'] = $this->load->view($this->views['AVATAR'], $this->data, true);
				$this->params['pageType'] = PAGE_FORM;
				$this->displayView();
			} else {
				if (!(strpos($_FILES['avatarFile']['name'],'.jpg') || strpos($_FILES['avatarFile']['name'],'.jpeg') || strpos($_FILES['avatarFile']['name'],'.gif') || strpos($_FILES['avatarFile']['name'],'.png'))) {
					$fv = & _get_validation_object();
					$fv->setError('avatarFile','The file selected is not a valid image file.');  
					$this->params['content'] = $this->load->view($this->views['AVATAR'], $this->data, true);
					$this->params['pageType'] = PAGE_FORM;
					$this->displayView();
				} else {
					if ($_FILES['avatarFile']['error'] === UPLOAD_ERR_OK) {
						$change = $this->dataModel->applyData($this->input, $this->params['currUser']); 
						if ($change) {
							$this->dataModel->save();
							$this->session->set_flashdata('message', '<p class="success">The image has been successfully updated.</p>');
							redirect('team/info/'.$this->dataModel->id);
						} else {
							$message = '<p class="error">Avatar Change Failed.';
							$message .= '</p >';
							$this->session->set_flashdata('message', $message);
							redirect('team/avatar');
						}
					} else {
						throw new UploadException($_FILES['avatarFiles']['error']);
					}
				}
			}
		} else {
	        $this->session->set_flashdata('loginRedirect',current_url());	
			redirect('user/login');
	    }
	}
	public function removeAvatar() {
		if ($this->params['loggedIn']) {
			$this->getURIData();
			$this->loadData();
			if ($this->dataModel->id != -1) {
				$success = $this->dataModel->deleteFile('avatar',PATH_TEAMS_AVATAR_WRITE,true);
			}
			if ($success) {
				$this->session->set_flashdata('message', '<p class="success">The image has been successfully deleted.</p>');
				redirect('team/info/'.$this->dataModel->id);
			} else {
				$message = '<p class="error">Avatar Delete Failed.';
				$message .= '<b>'.$this->dataModel->statusMess.'</b>';
				$message .= '</p >';
				$this->session->set_flashdata('message', $message);
				redirect('team/avatar');
			}
		}
	}
	public function setLineup() {
		$this->getURIData();
		$this->data['subTitle'] = "Set lineup";
		$this->load->model($this->modelName,'dataModel');
		$this->dataModel->load($this->uriVars['id']);
		$this->data['league_id'] = $this->uriVars['id'];
		if (!function_exists('getCurrentScoringPeriod')) {
			$this->load->helper('admin');
		}
		
		$error = false;
		$rosterError = false;
		$roster = $this->dataModel->applyRosterChanges($this->input,$this->params['config']['current_period'],$this->dataModel->id);
		if (!isset($roster)) {
			$error = true;
		} else {
			if (!$this->league_model->validateRoster($roster,$this->dataModel->league_id)) {
				$rosterError = $this->league_model->statusMess;
			}
			$error = !$this->dataModel->saveRosterChanges($roster,$this->params['config']['current_period']);
			
		}
		if ($error || $rosterError) {
			if ($rosterError) { $error = "<b>Your Rosters are currently illegal! Your team will score 0 points until roster errors are corrected.</b>".$rosterError; } 
			$this->data['message'] = $error;
			$this->data['messageType'] = 'error';
		} else {
			$this->data['message'] = "Your lineups have been successfully updated.";
			$this->data['messageType'] = 'success';
		}
		$this->showInfo();
	}
	
	/*--------------------------------
	/	PRIVATE FUNCTIONS
	/-------------------------------*/
	protected function prepForQuery() {
		$this->data['scoring_rules'] = $this->league_model->getScoringRules(0);
		if (!function_exists('getAvailableScoringPeriods')) {
			$this->load->helper('admin');
		}
		$this->data['scoring_periods'] = getAvailableScoringPeriods();
		
		$scoring_period_id = -1;
		if (isset($this->uriVars['scoring_period_id'])) {
			$scoring_period_id = $this->uriVars['scoring_period_id'];
		}
		$this->data['stat_source'] = 'ootp';
		if (isset($this->uriVars['stat_source'])) {
			$this->data['stat_source'] = $this->uriVars['stat_source'];
		}
		if ($this->data['stat_source'] != 'ootp' && $this->data['stat_source'] != 'sp_all') {
			$spid = explode("_",$this->data['stat_source']);
			if (sizeof($spid) > 0) { $scoring_period_id = $spid[1]; }
		}
		$this->scoring_period = array('id'=>-1,'date_start'=>$this->ootp_league_model->start_date,'date_end'=>$this->ootp_league_model->current_date);
		if ($scoring_period_id != -1) {
			$this->scoring_period = $this->data['scoring_periods'][$scoring_period_id-1];
		}
		$this->rules = $this->league_model->getScoringRules($this->dataModel->league_id);
		if (sizeof($this->rules) == 0) {
			$this->rules = $this->league_model->getScoringRules(0);
		}
		
		if (!isset($this->player_model)) {
			$this->load->model('player_model');
		}
	}
	
	protected function refreshPlayerList() {
		$result = '';
		if (!function_exists('getCurrentScoringPeriod')) {
			$this->load->helper('admin');
		}
		
		$this->load->model($this->modelName,'dataModel');
		if ($this->dataModel->load($this->uriVars['team_id'])) {
			$players = $this->dataModel->getBasicRoster($this->params['config']['current_period']);
			foreach ($players as $player) {
				if (!empty($result)) { $result .= ','; }
				$pos = '';
				if ($player['player_position'] != 1) {
					$pos = get_pos($player['player_position']); 
				} else {
					$pos = get_pos($player['player_role']); 
				}
				$result .= '{"id":"'.$player['id'].'","player_name":"'.$pos." ".$player['player_name'].'"}';
			}
		}
		$result = '{ result: { items: ['.$result.']}';
		return $result;
	}
	/**
	 *	GET URI DATA.
	 *	Parses out an id or other parameters from the uri string
	 *
	 */
	protected function getURIData() {
		parent::getURIData();
		if ($this->input->post('team_id')) {
			$this->uriVars['team_id'] = $this->input->post('team_id');
		} // END if
		if ($this->input->post('curr_team')) {
			$this->uriVars['curr_team'] = $this->input->post('curr_team');
		} // END if
		if ($this->input->post('team_id2')) {
			$this->uriVars['team_id2'] = $this->input->post('team_id2');
		} // END if
		if ($this->input->post('player_id')) {
			$this->uriVars['player_id'] = $this->input->post('player_id');
		} // END if
		if ($this->input->post('league_id')) {
			$this->uriVars['league_id'] = $this->input->post('league_id');
		} // END if
		if ($this->input->post('type')) {
			$this->uriVars['type'] = $this->input->post('type');
		} // END if
		if ($this->input->post('param')) {
			$this->uriVars['param'] = $this->input->post('param');
		} // END if
		if ($this->input->post('position')) {
			$this->uriVars['position'] = $this->input->post('position');
		} // END if
		if ($this->input->post('role')) {
			$this->uriVars['role'] = $this->input->post('role');
		} // END if
		if ($this->input->post('scoring_period_id')) {
			$this->uriVars['scoring_period_id'] = $this->input->post('scoring_period_id');
		} // END if
		if ($this->input->post('stat_source')) {
			$this->uriVars['stat_source'] = $this->input->post('stat_source');
		} // END if
		if ($this->input->post('list_type')) {
			$this->uriVars['list_type'] = $this->input->post('list_type');
		} // END if
		if ($this->input->post('add')) {
			$this->uriVars['add'] = $this->input->post('add');
		} // END if
		if ($this->input->post('drop')) {
			$this->uriVars['drop'] = $this->input->post('drop');
		} // END if
		if ($this->input->post('tradeTo')) {
			$this->uriVars['tradeTo'] = $this->input->post('tradeTo');
		} // END if
		if ($this->input->post('tradeFrom')) {
			$this->uriVars['tradeFrom'] = $this->input->post('tradeFrom');
		} // END if
		if ($this->input->post('limit')) {
			$this->uriVars['limit'] = $this->input->post('limit');
		} // END if
		if ($this->input->post('startIndex')) {
			$this->uriVars['startIndex'] = $this->input->post('startIndex');
		} // END if
		if ($this->input->post('pageId')) {
			$this->uriVars['pageId'] = $this->input->post('pageId');
		} // END if
		if ($this->input->post('uid')) {
			$this->uriVars['uid'] = $this->input->post('uid');
		} // END if
	}
	protected function makeForm() {
		$form = new Form();
		
		$form->open('/'.$this->_NAME.'/submit/','detailsForm|detailsForm');
		
		$form->fieldset('Team Details');
		
		$form->text('teamname','Team Name','required|trim',($this->input->post('teamname')) ? $this->input->post('teamname') : $this->dataModel->teamname,array('class','first'));
		$form->br();
		$form->text('teamnick','Nick Name','required|trim',($this->input->post('teamnick')) ? $this->input->post('teamnick') : $this->dataModel->teamnick,array('class','first'));
		$form->br();
		if ($this->params['accessLevel'] == ACCESS_ADMINISTRATE) {
			$form->select('division_id|division_id',listLeagueDivisions($this->dataModel->league_id),'Division',($this->input->post('division_id')) ? $this->input->post('division_id') : $this->dataModel->division_id);
			$form->br();
		}
		$form->fieldset('Draft Settings');
		$responses[] = array('1','Yes');
		$responses[] = array('-1','No');
		$form->fieldset('',array('class'=>'radioGroup'));
		$form->radiogroup ('auto_draft',$responses,'Auto Draft',($this->input->post('auto_draft') ? $this->input->post('auto_draft') : $this->dataModel->auto_draft));
		$form->space();
		$form->fieldset('',array('class'=>'radioGroup'));
		$form->radiogroup ('auto_list',$responses,'Use Draft List',($this->input->post('auto_list') ? $this->input->post('auto_list') : $this->dataModel->auto_list));
		$form->space();
		$form->text('auto_round_x','Auto Draft After Round','number',($this->input->post('auto_round_x')) ? $this->input->post('auto_round_x') : $this->dataModel->auto_round_x);
		$form->span('Set to -1 to disable',array('class'=>'field_caption'));
		$form->space();
		$form->fieldset('',array('class'=>'button_bar'));
		$form->span(' ','style="margin-right:8px;display:inline;"');
		$form->button('Cancel','cancel','button',array('class'=>'button'));
		$form->nobr();
		$form->span(' ','style="margin-right:8px;display:inline;"');
		$form->submit('Submit');
		$form->hidden('submitted',1);
		if ($this->recordId != -1) {
			$form->hidden('mode','edit');
			$form->hidden('id',$this->recordId);
		} else {
			$form->hidden('mode','add');
		}
		$this->form = $form;
		$this->data['form'] = $form->get();
		
		$this->makeNav();
		
	}
	
	
	protected function showInfo() {

		if (!function_exists('getScoringPeriod')) {
			$this->load->helper('admin');
		}
		if (isset($this->uriVars['period_id'])) {
			$curr_period_id = 	$this->uriVars['period_id'];
		} else {
			$curr_period_id = $this->params['config']['current_period'];
		}
		$curr_period = getScoringPeriod($curr_period_id);
		$this->data['curr_period'] = $curr_period_id;
		
		if (!isset($this->league_model)) {
			$this->load->model('league_model');
			$this->league_model->load($this->dataModel->league_id);
		}
		$this->league_model->load($this->dataModel->league_id);
		$this->data['avail_periods'] = $this->league_model->getAvailableRosterPeriods();
		
		// Setup header Data
		$this->data['thisItem']['league_id'] = $this->dataModel->league_id;
		$this->data['thisItem']['team_id'] = $this->dataModel->id;
		$this->data['thisItem']['teamname'] = $this->dataModel->teamname;
		$this->data['thisItem']['teamnick'] = $this->dataModel->teamnick;
		$this->data['thisItem']['avatar'] = $this->dataModel->avatar;
		//echo("Scoring period param = ".$curr_period_id."<br />");
		if (!$this->league_model->validateRoster($this->dataModel->getBasicRoster($curr_period_id))) {
			$this->data['message'] = "<b>Your Rosters are currently illegal! Your team will score 0 points until roster errors are corrected.</b>".$this->league_model->statusMess;
			$this->data['messageType'] = 'error';
		}
		
		if ($this->params['loggedIn']) {
			$this->data['thisItem']['userTeamId'] = $this->user_meta_model->getUserTeamIds($this->dataModel->league_id,$this->params['currUser']);
		}
		$players = $this->dataModel->getCompleteRoster($curr_period_id);
		if (isset($players[0])) {
			$this->data['thisItem']['players_active'] =	$players[0];
		} 
		if (isset($players[1])) {
			$this->data['thisItem']['players_reserve'] = $players[1];
		}
		if (isset($players[2])) {
			$this->data['thisItem']['players_injured'] = $players[2];
		}
		$this->data['thisItem']['team_list'] = getOOTPTeams($this->params['config']['ootp_league_id'],false);
		
		if (isset($this->data['thisItem']['league_id']) && $this->data['thisItem']['league_id'] != -1) {
			$this->data['thisItem']['fantasy_teams'] = getFantasyTeams($this->data['thisItem']['league_id']);
		}
		$this->data['thisItem']['visible_week'] = getVisibleDays($curr_period['date_start'],$this->params['config']['sim_length']);
		
		$this->data['thisItem']['schedules'] = getPlayerSchedules($players,$curr_period['date_start'],$this->params['config']['sim_length']);
		
		$this->data['thisItem']['owner_name'] = resolveOwnerName($this->dataModel->owner_id);
		$this->data['thisItem']['owner_id'] = $this->dataModel->owner_id;
		
		$divisionName = '';
		$divisionsList = listLeagueDivisions($this->dataModel->id,false);
		foreach($divisionsList as $key => $value) {
			if ($this->dataModel->division_id == $key) {
				$divisionName = $value;
				break;
			}
		}
		$this->data['thisItem']['divisionName'] = $divisionName;	

		$this->params['subTitle'] = "Team Overview";
		
		$this->data['showAdmin'] = (($this->params['currUser'] == $this->dataModel->owner_id && $curr_period_id == $this->params['config']['current_period']) || $this->params['accessLevel'] == ACCESS_ADMINISTRATE) ? true : false;

		$this->makeNav();
		
		
		parent::showInfo();
	}
	/**
	 *	MAKE NAV BAR
	 *
	 */
	protected function makeNav() {
		$navs = array();
		if (!isset($this->league_model)) {
			$this->load->model('league_model');
		}
		$this->league_model->load($this->dataModel->league_id);
		if ($this->league_model->id != -1) {
			$league_name = $this->league_model->league_name;
		} else {
			$league_name = "Unknown League";
		}
		$lg_admin = false;
		if (isset($this->params['currUser']) && ($this->params['currUser'] == $this->league_model->commissioner_id || $this->params['accessLevel'] == ACCESS_ADMINISTRATE)) {
			$lg_admin = true;
		}
		array_push($this->params['subNavSection'], league_nav($this->dataModel->league_id, $league_name,$lg_admin));
		
		$tm_admin = false;
		if (isset($this->params['currUser']) && ($this->params['currUser'] == $this->dataModel->owner_id || $this->params['accessLevel'] == ACCESS_ADMINISTRATE)) {
			$tm_admin = true;
		}
		array_push($this->params['subNavSection'],team_nav($this->dataModel->id,$this->dataModel->teamname." ".$this->dataModel->teamnick, $tm_admin));
	}
}
/* End of file team.php */
/* Location: ./application/controllers/team.php */ 