<?php
/**
 *	LEAGUE MODEL CLASS.
 *	
 *	The League Model is the powerhouse of the Fantasy process. It manages many of the 
 *  admin functionality and provides tools and methods to runt he league.
 *	@author		Jeff Fox
 *	@version	1.0
 *
*/
class league_model extends base_model {

	var $_NAME = 'league_model';
	
	var $league_name  = '';
	var $description  = '';
	var $league_type  = -1;
	var $games_per_team = 0;
	var $access_type = -1;
	var $avatar = '';
	var $league_status = 1;
	var $commissioner_id = -1;
	var $regular_scoring_periods = 0;
	var $playoff_rounds = 0;
	var $max_teams = 0;
	
	function league_model() {
		parent::__construct();
		
		$this->tblName = 'fantasy_leagues';
		$this->tables['TRANSACTIONS'] = 'fantasy_transactions';
		$this->tables['WAIVERS'] = 'fantasy_players_waivers';
		$this->tables['WAIVER_CLAIMS'] = 'fantasy_teams_waiver_claims';
		$this->tables['TEAMS'] = 'fantasy_teams';
		$this->tables['ROSTER_RULES'] = 'fantasy_roster_rules';
		$this->tables['SCORING_RULES_BATTING'] = 'fantasy_leagues_scoring_batting';
		$this->tables['SCORING_RULES_PITCHING'] = 'fantasy_leagues_scoring_pitching';
		
		$this->fieldList = array('league_name','description','league_type','games_per_team','access_type','league_status','regular_scoring_periods','max_teams','playoff_rounds');
		$this->conditionList = array('avatarFile','new_commisioner');
		$this->readOnlyList = array('avatar','commissioner_id');  
		$this->textList = array('description');
		
		$this->columns_select = array('id','league_type','description','league_name','max_teams','access_type','avatar','commissioner_id');
		
		$this->addSearchFilter('league_type','Scoring Type','leagueType','leagueType');
		$this->addSearchFilter('access_type','Public/Private','accessType','accessType');
		
		parent::_init();
	}
	/*--------------------------------------------------
	/
	/	PUBLIC FUNCTIONS
	/
	/-------------------------------------------------*/
	public function applyData($input,$userId = -1) {
		$success = parent::applyData($input,$userId);
		if ($success) {
			if ($input->post('new_commisioner')) {
				if ($this->ownerCanBeCommish($input->post('new_commisioner'))) {
					$this->commissioner_id = $input->post('new_commisioner');
				}
			}
			if (isset($_FILES['avatarFile']['name']) && !empty($_FILES['avatarFile']['name'])) { 
				$success = $this->uploadFile('avatar',PATH_LEAGUES_AVATAR_WRITE,$input,'avatar',$this->league_name);
			}
		}
		return $success;
	}
	
	
	// SPECIAL QUERIES
	public function getLeagueName($league_id = false) {
		
		if ($league_id === false) { $league_id = $this->id; }
		$this->db->select('league_name');
		$this->db->from($this->tblName);
		$this->db->where("id",$league_id);
		$league_name = '';
		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			$row = $query->row();
			$league_name = $row->league_name;
		}
		$query->free_result();
		return $league_name;
	}
	
	public function hasTeams($league_id = false) {
		if ($league_id === false) { $league_id = $this->id; }
		$this->db->select('id');
		$this->db->from('fantasy_teams');
		$this->db->where("league_id",$league_id);
		$count = $this->db->count_all_results();
		//echo("League ".$league_id." team count = ".$count."<br />");
		if ($count != 0) {
			return true;
		} else {
			return false;
		}	
	}
	public function hasValidRosters($league_id = false) {
		
		// VALIDATE ROSTER COUNTS
		if ($league_id === false) { $league_id = $this->id; }
		$this->db->select('fantasy_rosters.player_id');
		$this->db->from('fantasy_teams');
		$this->db->join('fantasy_rosters','fantasy_rosters.team_id = fantasy_teams.id','left');
		$this->db->where("fantasy_teams.league_id",$league_id);
		$count = $this->db->count_all_results();
		if ($count != 0) {
			return true;
		} else {
			return false;
		}	
	}
	protected function ownerCanBeCommish($userId) {
		if ($userId != $this->commissioner_id && !$this->userIsCommish($userId)) {
			return true;
		} else {
			return false;
		}
	}
	public function userIsCommish($userId, $league_id = false) {
		$this->db->select('id');
		$this->db->from($this->tblName);
		$this->db->where("commissioner_id",$userId);
		if ($league_id !== false) {
			$this->db->where("id",$league_id);
		}
		$count = $this->db->count_all_results();
		if ($count != 0) {
			return true;
		} else {
			return false;
		}	
	}
	public function getOwnerIds($league_id = false) {
		
		$owners = array();
		if ($league_id === false) { $league_id = $this->id; }
		$this->db->select('owner_id');
		$this->db->from('fantasy_teams');
		$this->db->where("league_id",$league_id);
		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				array_push($owners,$row->owner_id);
			}
		}
		$query->free_result();
		return $owners;
	}
	public function getOwnerInfo($league_id = false) {
		
		$owners = array();
		if ($league_id === false) { $league_id = $this->id; }
		$this->db->select('fantasy_teams.id, firstName, lastName');
		$this->db->from('fantasy_teams');
		$this->db->join('users_meta','users_meta.userId = fantasy_teams.owner_id');
		$this->db->where("league_id",$league_id);
		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				$owners = $owners + array($row->id=>$row->firstName." ".$row->lastName);
			}
		}
		$query->free_result();
		return $owners;
	}
	
	public function getLeagueInvites($league_id = false) {
		
		$invites = array();
		if ($league_id === false) { $league_id = $this->id; }
		
		$this->db->select('to_email, send_date, team_id, teamname, teamnick');
		$this->db->from('fantasy_invites');
		$this->db->join('fantasy_teams','fantasy_teams.id = fantasy_invites.team_id','right outer');
		$this->db->where("fantasy_invites.league_id",$league_id);
		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				array_push($invites,array('to_email'=>$row->to_email, 'send_date'=>$row->send_date,
										  'team_id'=>$row->team_id,'team'=>$row->teamname." ".$row->teamnick));
			}
		}
		$query->free_result();
		return $invites;
	}
	
	/**
	 *	GET LEAGUES.
	 *	Returns a list of public leagues.
	 *  @param	$league_id - If not specified, no league filter is applied.
	 *	@param	$type - 1 = Public, -1 = all (admin only)
	 *	@return	array of league information
	 */
	public function getLeagues($league_id = false, $type=1) {
		$leagues = array();
		$this->db->select($this->tblName.'.id, league_name, description, avatar, shortDesc, commissioner_id,access_type');
		$this->db->join('fantasy_leagues_types','fantasy_leagues_types.id = '.$this->tblName.'.league_type','left');
		if ($type != -1) $this->db->where('access_type',1);
		$query = $this->db->get($this->tblName);
		if ($query->num_rows() > 0) {
			foreach ($query->result() as $row) {
				$commish = resolveUsername($row->commissioner_id);
				$leagues = $leagues + array($row->id=>array('league_name'=>$row->league_name,'avatar'=>$row->avatar,
															'commissioner_id'=>$row->commissioner_id,'commissioner'=>$commish,
															'league_type'=>$row->shortDesc,'description'=>$row->description,
															'access_type'=>$row->access_type));
			}
		}
		$query->free_result();
		return $leagues;
	}
	/**
	 *	CREATE LEAGUE SCHEDULE.
	 *	Builds a scheudle for all teams based on the number of teams, number of scoring periods 
	 *	and the number of games per team.
	 *  @param	$league_id - If not specified, no league filter is applied.
	 *	@return	"OK" on success, false on failure
	 */
	public function createLeagueSchedule($league_id = false) {
		
		if ($league_id === false) { $league_id = $this->id; }
		
		// GET ALL TEAMS
		$teams = array();
		$this->db->select("id"); 
		$this->db->where("league_id",$league_id);
		$query = $this->db->get("fantasy_teams");
		if ($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				array_push($teams,$row->id);
			}
		}
		$query->free_result();
		
		// DELETE ALL GAMES FOR THIS LEAGUE IF THEY EXIST
		$this->db->flush_cache();
		$this->db->where('league_id',$league_id);
		$this->db->delete('fantasy_leagues_games');

		$matchups = sizeof($teams) * $this->games_per_team;
			
		//echo("matchups per period = ".$matchups."<br />");
		
		for ($s = 1; $s < ($this->regular_scoring_periods +1); $s++) {
			
			$data = array();
			//echo("Scoring period ".$s."<br />");
			$teamCount = 0;
			$periodDone = false;
			$gamesPerTeam = array();
			$type = 0;
			for ($p = 0; $p < $matchups; $p++) {
				
				
				$team_id = $teams[intval(rand(0, (sizeof($teams)-1)))];
				if (isset($gamesPerTeam[$team_id]) && $gamesPerTeam[$team_id] >= $this->games_per_team) { }
				else {
					
					// PICK THREE RANDOM OPPONENTS FOR THE CURRENT TEAM
					// ALTERNATE HOME AND AWAY
					$type = ($type == 0) ? 1 : 0;
					$tries = 0;
					while (true) {
						$opponent = $teams[intval(rand(0, (sizeof($teams)-1)))];
						//echo("Opponent = ".$opponent."<br />");
						if ($opponent != $team_id) {
							$gamesForTeam = isset($gamesPerTeam[$opponent]) ? $gamesPerTeam[$opponent] : 0;
							if ($gamesForTeam < $this->games_per_team) {
								if ($type == 0) {
									$home_team_id = $team_id;
									$away_team_id = $opponent;
								} else {
									$home_team_id = $opponent;
									$away_team_id = $team_id;
								} // END if
								array_push($data,array('league_id'=>$league_id,'home_team_id'=>$home_team_id, 
											  'away_team_id'=>$away_team_id,'scoring_period_id'=>$s));
								
								if (isset($gamesPerTeam[$opponent]))
									$gamesPerTeam[$opponent] = $gamesPerTeam[$opponent] + 1;
								else 
									$gamesPerTeam[$opponent] = 1; // END if
									
								if (isset($gamesPerTeam[$team_id]))
									$gamesPerTeam[$team_id] = $gamesPerTeam[$team_id] + 1;
								else 
									$gamesPerTeam[$team_id] = 1; // END if			
								break;
							} // END if
						} // END if
						$tries++;
						if ($tries > 500) { 
							//echo("In loop = true");
							//echo("Current team id = ".$team_id."<br />");
							//echo("Has any games? ".(isset($gamesPerTeam[$team_id]) ? "true" : "false")."<br />");
							// ATEMPT A MATCHUP MANUALLY
							if (isset($gamesPerTeam[$team_id])) {
								//echo("Games for team ".$team_id." = ".$gamesPerTeam[$team_id]."<br />");
								if ($gamesPerTeam[$team_id] < $this->games_per_team) {
								// FIND ANOTHER TEAM UNDER THE MINIMUM
									foreach($gamesPerTeam as $team_id_val => $game_count) {
										if ($game_count < $this->games_per_team) {
											if ($type == 0) {
												$home_team_id = $team_id;
												$away_team_id = $team_id_val;
											} else {
												$home_team_id = $team_id_val;
												$away_team_id = $team_id;
											} // END if
											array_push($data,array('league_id'=>$league_id,'home_team_id'=>$home_team_id, 
											 'away_team_id'=>$away_team_id,'scoring_period_id'=>$s));
											if (isset($gamesPerTeam[$home_team_id]))
												$gamesPerTeam[$home_team_id] = $gamesPerTeam[$home_team_id] + 1;
											else 
												$gamesPerTeam[$home_team_id] = 1; // END if
											if (isset($gamesPerTeam[$away_team_id]))
												$gamesPerTeam[$away_team_id] = $gamesPerTeam[$away_team_id] + 1;
											else 
												$gamesPerTeam[$away_team_id] = 1; // END if
											break;
										}
									}
								}
							} else {
								//echo("No matchups.<br />");
								// THIS TEAM HAS NO MATCHUPS THIS PERIOD
								// BREAK UP ANOTHER GAME AND GIVE THE OPPOENTS TO THIS TEAM
								$gameData = array_shift($data);
								//echo("# of games saved = ".sizeof($data)."<br />");
								//echo("fields in gameData = ".sizeof($gameData)."<br />");
								$home_team_id = $gameData['home_team_id'];
								$away_team_id = $gameData['away_team_id'];
								//echo("home_team_id = ".$home_team_id."<br />");
								//echo("away_team_id = ".$away_team_id."<br />");
								
								array_push($data,array('league_id'=>$league_id,'home_team_id'=>$team_id, 
									  'away_team_id'=>$home_team_id,'scoring_period_id'=>$s));
								array_push($data,array('league_id'=>$league_id,'home_team_id'=>$away_team_id, 
											  'away_team_id'=>$team_id,'scoring_period_id'=>$s));
								if (!isset($gamesPerTeam[$team_id]))
									$gamesPerTeam[$team_id] = 2; // END if

							}
							$periodDone = true; break; 
						}
					} // END while
					if ($periodDone) { break; }
				} // END if
			} // END for
			$totalGames = 0;
			if (sizeof($gamesPerTeam) > 0) {
				foreach($gamesPerTeam as $team_id_val => $game_count) {
					//echo($team_id_val ." has ".$game_count." games this period.<br />");
					$totalGames += $game_count;
				}
			}
			$loops = 0;
			while (true) {
				//echo("Total games before correct for period = ".$totalGames."<br />");
				//echo("Total games less than matchups? = ".(($totalGames < $matchups) ? "true" : "false")."<br />");
				if ($totalGames < $matchups) {
					if (sizeof($gamesPerTeam) > 0 && sizeof($gamesPerTeam) == sizeof($teams)) {
						$team1Id = -1;
						// FIND A TEAM UNDER THE MINIMUM
						foreach($gamesPerTeam as $team_id_val => $game_count) {
							if ($game_count < $this->games_per_team) {
								$team1Id = $team_id_val;
								break;
							}
						}
						//echo("team under the minimum ".$team1Id." = ".$game_count."<br />");
						// FIND ANOTHER TEAM UNDER THE MINIMUM
						foreach($gamesPerTeam as $team_id_val => $game_count) {
							if ($team_id_val != $team1Id && $game_count < $this->games_per_team) {
								//echo("second under the minimum ".$team_id_val." = ".$game_count."<br />");
								
								$type = ($type == 0) ? 1 : 0;
								if ($type == 0) {
									$home_team_id = $team1Id;
									$away_team_id = $team_id_val;
								} else {
									$home_team_id = $team_id_val;
									$away_team_id = $team1Id;
								} // END if
								array_push($data,array('league_id'=>$league_id,'home_team_id'=>$home_team_id, 
								 'away_team_id'=>$away_team_id,'scoring_period_id'=>$s));
								$gamesPerTeam[$home_team_id] = $gamesPerTeam[$home_team_id] + 1;
								$gamesPerTeam[$away_team_id] = $gamesPerTeam[$away_team_id] + 1;
								$totalGames += 2;
							}
						}
					} else {
						$team1Id = -1;
						// FIND A TEAM WITH NO GAMES ENTRY
						foreach($teams as $team_id_val) {
							if (!isset($gamesPerTeam[$team_id_val])) {
								$team1Id = $team_id_val;
								break;
							}
						}
						//echo("Team missing games = ".$team1Id."<br />");
						// THIS TEAM HAS NO MATCHUPS THIS PERIOD
						// BREAK UP ANOTHER GAME AND GIVE THE OPPOENTS TO THIS TEAM
						$gameData = array_shift($data);
						//echo("# of games saved = ".sizeof($data)."<br />");
						//echo("fields in gameData = ".sizeof($gameData)."<br />");
						$home_team_id = $gameData['home_team_id'];
						$away_team_id = $gameData['away_team_id'];
						//echo("home_team_id = ".$home_team_id."<br />");
						//echo("away_team_id = ".$away_team_id."<br />");
						array_push($data,array('league_id'=>$league_id,'home_team_id'=>$team1Id, 
									  'away_team_id'=>$home_team_id,'scoring_period_id'=>$s));
						
						array_push($data,array('league_id'=>$league_id,'home_team_id'=>$away_team_id, 
									  'away_team_id'=>$team1Id,'scoring_period_id'=>$s));
						if (!isset($gamesPerTeam[$team1Id]))
							$gamesPerTeam[$team1Id] = 2; // END if	
						$totalGames += 2;
					}
				} else {
					break;
				}
				$loops++;
				if ($loops == 100) { break; }
			}
			$totalGames = 0;
			if (sizeof($gamesPerTeam) > 0) {
				foreach($gamesPerTeam as $team_id_val => $game_count) {
					//echo($team_id_val ." has ".$game_count." games this period.<br />");
					$totalGames += $game_count;
				}
			}
			//echo("Total games for period = ".$totalGames."<br />");
			foreach($data as $query) {
				$this->db->flush_cache();
				$this->db->insert('fantasy_leagues_games',$query);
			}
		} // END for
		return "OK";
	} // END function
	/**
	 *	GET LEAGUE SCHEDULE.
	 *	Returns either the entire schdule for the specified league OR only games for a 
	 *	specific team (if specified).
	 *  @param	$team_id - If not specified, the schedule for the entire league is returned.
	 *  @param	$excludeScores - Set to TRUE to not return score information
	 *  @param	$league_id - If not specified, no league filter is applied.
	 *	@return	schedule array, false on failure
	 */
	public function getLeagueSchedule($team_id = false, $excludeScores = false, $league_id = false) {
		
		if ($league_id === false) { $league_id = $this->id; }
		
		$teamNames = array();
		$this->db->select("id, teamname, teamnick"); 
		$this->db->where("league_id",$league_id);
		$query = $this->db->get("fantasy_teams");
		if ($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				$teamNames[$row->id] = $row->teamname." ".$row->teamnick;
			}
		}
		$query->free_result();
		
		$schedule = array();
		$score_period_id = 0;
		$this->db->flush_cache();
		$this->db->select('away_team_id, away_team_score, home_team_score, home_team_id,  scoring_period_id');
		$this->db->where('league_id',$league_id);
		if ($team_id != false) {
			$this->db->where('(home_team_id = '.$team_id.' OR away_team_id = '.$team_id.')');
		}
		$this->db->order_by('scoring_period_id','asc');
		$query = $this->db->get('fantasy_leagues_games');
		if ($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				if ($score_period_id != $row->scoring_period_id) {
					$score_period_id = $row->scoring_period_id;
					$schedule[$score_period_id] = array();
				}
				$homeTeam = $teamNames[$row->home_team_id];
				$awayTeam = $teamNames[$row->away_team_id];
				$stats = array('home_team'=>$homeTeam, 'away_team'=>$awayTeam);
				if (!$excludeScores && (isset($row->home_team_score) && isset($row->away_team_score))) {
					$stats = $stats + array('home_team_score'=>$row->home_team_score,'away_team_score'=>$row->away_team_score);
				}
				array_push($schedule[$score_period_id],$stats);
			}
		}
		$query->free_result();
		return $schedule;
	}
	/**
	 *	GET LEAGUE TRANSACTIONS.
	 *	Returns either the entire schdule for the specified league OR only games for a 
	 *	specific team (if specified).
	 *  @param	$team_id - If not specified, the schedule for the entire league is returned.
	 *  @param	$excludeScores - Set to TRUE to not return score information
	 *  @param	$league_id - If not specified, no league filter is applied.
	 *	@return	schedule array, false on failure
	 */
	public function getLeagueTransactions($limit = -1, $startIndex = 0, $team_id = false, $league_id = false) {
		
		if ($league_id === false) { $league_id = $this->id; }
		
		$transactions = array();
		$this->db->select("trans_date, team_id, added, dropped, claimed, tradedTo, tradedFrom, trans_owner, effective"); 
		$this->db->where("league_id",$league_id);
		if ($team_id !== false) {
			$this->db->where('team_id',$team_id);
		}
		if ($limit != -1 && $startIndex == 0) {
			$this->db->limit($limit);
		} else if ($limit != -1 && $startIndex > 0) {
			$this->db->limit($startIndex,$limit);
		}
		$this->db->order_by('trans_date','desc');
		$query = $this->db->get($this->tables['TRANSACTIONS']);
		if ($query->num_rows() > 0) {
			$transTypes = array('added','dropped','claimed','tradedTo','tradedFrom');
            
			if (!function_exists('getFantasyPlayersDetails')) {
				$this->load->helper('roster');
			}
			foreach($query->result() as $row) {
				$transArrays = array();
				foreach ($transTypes as $field) {
					//echo($field."<br />");
					$transArrays[$field] = array();
					if (isset($row->$field) && !empty($row->$field) && strpos($row->$field,":")) {
						$fieldData = unserialize($row->$field); 
						if (is_array($fieldData) && sizeof($fieldData) > 0) {
							//echo("size of ".$field." data = ".sizeof($fieldData)."<br />");
							$playerDetails = getFantasyPlayersDetails($fieldData);
							foreach ($fieldData as $playerId) {
								//echo($field." player id = ".$playerId."<br />");
								$transStr = '';
								if (isset($playerDetails[$playerId])) {
									$pos = $playerDetails[$playerId]['position'];
									if ($pos == 1) { $pos = $playerDetails[$playerId]['role']; }
									$transStr .= get_pos($pos);
									$transStr .= "&nbsp; ".anchor('/players/info/league_id/'.$league_id.'/player_id/'.$playerId,$playerDetails[$playerId]['first_name']." ".$playerDetails[$playerId]['last_name']);
								} // END if
								//echo($transStr."<br />");
								if (!empty($transStr)) { array_push($transArrays[$field], $transStr); } 
							} // END foreach
						} // END if
					} // END if
				} // END foreach
				if (!function_exists('getScoringPeriodByDate')) {
					$this->load->helper('admin');
				}
				array_push($transactions,array('trans_date'=>$row->trans_date, 'team_id'=>$row->team_id, 
													  'added'=>$transArrays['added'], 'dropped'=>$transArrays['dropped'], 
													  'claimed'=>$transArrays['claimed'], 'tradedTo'=>$transArrays['tradedTo'], 'tradedFrom'=>$transArrays['tradedFrom'], 
													  'trans_owner'=>$row->trans_owner, 'effective'=>$row->effective));
			}
		}
		$query->free_result();
		return $transactions;
	}
	/**
	 *	GET WAIVER CLAIMS.
	 *	Returns pending waiver claims for the specified league.
	 *  @param	$team_id - If not specified, the schedule for the entire league is returned.
	 *  @param	$league_id - If not specified, no league filter is applied.
	 *	@return	schedule array, false on failure
	 */
	public function getWaiverClaims($limit = -1, $startIndex = 0, $team_id = false, $league_id = false) {
		
		if ($league_id === false) { $league_id = $this->id; }
		
		$claims = array();
		$this->db->select($this->tables['WAIVER_CLAIMS'].".id, ".$this->tables['WAIVER_CLAIMS'].".team_id, teamname, teamnick, ".$this->tables['WAIVER_CLAIMS'].".player_id, first_name, last_name, waiver_period"); 
		$this->db->join("fantasy_teams","fantasy_teams.id = ".$this->tables['WAIVER_CLAIMS'].".team_id", "left");
		$this->db->join("fantasy_players","fantasy_players.id = ".$this->tables['WAIVER_CLAIMS'].".player_id", "left");
		$this->db->join("fantasy_players_waivers","fantasy_players_waivers.player_id = fantasy_players.id", "right outer");
		$this->db->join("players","fantasy_players.player_id = players.player_id", "right outer");
		$this->db->where($this->tables['WAIVER_CLAIMS'].".league_id",$league_id);
		if ($team_id !== false) {
			$this->db->where($this->tables['WAIVER_CLAIMS'].'.team_id',$team_id);
		}
		if ($limit != -1 && $startIndex == 0) {
			$this->db->limit($limit);
		} else if ($limit != -1 && $startIndex > 0) {
			$this->db->limit($startIndex,$limit);
		}
		$this->db->order_by('waiver_period, teamname, last_name','asc');
		$query = $this->db->get($this->tables['WAIVER_CLAIMS']);
		//echo($this->db->last_query()."<br />");
		if ($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				array_push($claims,array('id'=>$row->id,'team_id'=>$row->team_id, 'teamname'=>$row->teamname." ".$row->teamnick, 
										 'player_id'=>$row->player_id, 'player_name'=>$row->first_name." ".$row->last_name, 
										 'waiver_period'=>$row->waiver_period));
			}
		}
		$query->free_result();
		return $claims;
	}
	/**
	 *	PROCESS WAIVERS.
	 *	Processes pending waiver claims for the specified league and clears waivers for the selected scoring period.
	 *  @param	$period_id - The scoring period to process waivers for.
	 *  @param	$league_id - If not specified, no league filter is applied.
	 *  @param	$debug - 	 TRUE TO enabled tracing, FALSE to disable
	 *	@return	schedule array, false on failure
	 */
	public function processWaivers($period_id = false, $league_id = false, $debug = false) {
		
		if ($period_id === false) { return; }
		if ($league_id === false) { $league_id = $this->id; }
		
		// GET LEAGUE TEAM ID LIST
		if (!function_exists('getPlayersOnWaivers')) {
			$this->load->helper('roster');	
		}
		$playersOnWaivers = getPlayersOnWaivers($period_id, $league_id);
		$claims = $this->getWaiverClaims(-1,0,false,$league_id);
		$waiverOrder = getWaiverOrder($league_id, true);
		
		if ($debug) {
			echo("# of players on waivers = ".sizeof($playersOnWaivers)."<br />");
			echo("# of claims by teams = ".sizeof($claims)."<br />");
			echo("waiver order = ".sizeof($waiverOrder)."<br />");
		}
			
		foreach($playersOnWaivers as $player) {
			// SEE IF THERE IS A WAIVER CLAIM FOR THIS PLAYER
			$numClaims = 0;
			$claimList = array();
			$claimCount = 1;
			foreach($claims as $claim) {
				if ($debug) {
					echo("claim ".$claimCount." team = ".$claim['team_id'].", player = ".$claim['player_id']."<br />");
				}
				if ($claim['player_id'] == $player['player_id']) {
					// CLAIMS FOUND
					$numClaims++;
					array_push($claimList, $claim['team_id']);
				}
			}
			if ($debug) {
				echo("current player = ".$player['player_id']."<br />");
				echo("# of claims for player ".$player['player_id']." = ".$numClaims."<br />");
			}
			if ($numClaims > 0) {
				$index = 0;
				foreach($waiverOrder as $team_id) {
					if (in_array($team_id, $claimList)) {
						if ($debug) {
							echo("claim found for player ".$player['player_id']." by team = ".$team_id."<br />");
						}
						
						// CLAIM THIS PLAYER FOR TEAM
						$this->db->set('team_id',$team_id);
						$this->db->set('player_id',$player['player_id']);
						$this->db->set('league_id',$league_id);
						if ($player['position'] == 7 || $player['position'] == 8 || $player['position'] == 9) { $player['position'] = 20; }
						if ($player['role'] == 13) { $player['role'] = 12; }
						$this->db->set('player_position',$player['position']);
						$this->db->set('player_role',$player['role']);
						$this->db->set('scoring_period_id',$period_id);
						$this->db->insert('fantasy_rosters');
						
						if (!function_exists('updateOwnership')) {
							$this->load->helper('roster');
						}
						$ownership = updateOwnership($player['player_id']);
						$pData = array('own'=>$ownership[0],'start'=>$ownership[1]);
						$this->db->flush_cache();
						$this->db->where('id',$claim['player_id']);
						$this->db->update('fantasy_players',$pData); 
						
						// LOG THE TRANSACTION
						logTransaction(NULL, NULL, array($player['player_id']),NULL, NULL, -1,1, false,$period_id,$league_id,$team_id,-1);
						
						// REMOVE TEAM FROM WAIVER ORDER ARRAY AND PUT IT AT THE END
						$waiveTeam = array_splice($waiverOrder,$index, 1);
						array_push($waiverOrder,$waiveTeam[0]);
						break;
					}
					$index++;
				}
				// REMOVE ALL WAIVER CLAIMS FOR THIS PLAYER
				if (!$debug) {
					$this->db->where('player_id',$player['player_id']);
					$this->db->where('league_id',$league_id);
					$this->db->delete($this->tables['WAIVER_CLAIMS']);
				}
				if ($debug) {
					echo("cleared = ".$this->db->affected_rows()."  of ".$numClaims." waiver claims for this player<br />");
				}
			}
			// REMOVE PLAYER FROM WAIVERS
			if (!$debug) {$this->db->where('player_id',$player['player_id']);
				$this->db->where('league_id',$league_id);
				$this->db->where('waiver_period',$period_id);
				$this->db->delete($this->tables['WAIVERS']);
			}
			if ($debug) {
				echo("cleared = ".$this->db->affected_rows()." of ".$numClaims." waiver records for player ".$player['player_id']."<br />");
			}
		}
		// UPDATE THE WAIVER ORDER OF THE TEAMS IN THE LEAGUE
		$waiverList = array();
		$rank = 1;
		if ($debug) {
			echo("New waiver order:<br />");
		}
		foreach($waiverOrder as $waiveTeam) {
			$this->db->set('waiver_rank',$rank);
			$this->db->where('id',$waiveTeam);
			$this->db->update($this->tables['TEAMS']);
			if ($debug) {
				echo($rank." = ".$waiveTeam."<br />");
			}
			$rank++;
		}
		return true;
	}
	
	/**
	 *	GET SCORING RULES.
	 *	Returns the scoring rules for a specific league (if they exist) or the 
	 *	global scoring rules in they don't
	 *  @param	$league_id - If not specified, no league filter is applied.
	 *	@return	Rules array on success, false on failure
	 */
	public function getScoringRules($league_id = false) {
		
		if ($league_id === false) { $league_id = $this->id; }
		$rules = array('batting'=>array(),'pitching'=>array());
		$default = false;
		foreach($rules as $key => $data) {
			// TEST for custom scoring rules
			// if not present, use the default rules for the league (league_id 0)
			$this->db->where('league_id',$league_id);
			$this->db->from('fantasy_leagues_scoring_'.$key);
			$count = $this->db->count_all_results();
			if ($count == 0) {
				$league_id = 0;
			}
			$this->db->select('*');
			$this->db->where('league_id',$league_id);
			$query = $this->db->get('fantasy_leagues_scoring_'.$key);
			if ($query->num_rows() > 0) {
				$cats = array();
				foreach ($query->result() as $row) {
					for ($i = 0; $i < 12; $i++) {
						$columnT = "type_".$i;
						$columnV = "value_".$i;
						if ($row->$columnT != -1) {
							$cats = $cats + array($row->$columnT=>$row->$columnV);
						}
					}
				}
				$rules[$key] = $cats;
			}
			$query->free_result();
		}
		$rules['league_id'] = $league_id;
		return $rules;
	}
	
	/**
	 * SET SCORING RULES
	 * This function accepts a form input object and applies the passed values to 
	 * the scoring rules tables. 
	 * @param	$input		CodeIgniter form input object
	 * @param	$league_id 	Optional league ID. Defaults to "0" if no id is passed.
	 */
	public function setScoringRules($input, $league_id = false) {
		if ($league_id === false) { $league_id = 0; }
		
		$this->db->where('league_id', $league_id);
		$this->db->delete($this->tables['SCORING_RULES_BATTING']);
		$this->db->where('league_id', $league_id);
		$this->db->delete($this->tables['SCORING_RULES_PITCHING']);
		
		$types = array('batting','pitching');
		foreach($types as $type) {
			$lineCount = 0;
			$data = array('league_id'=>$league_id);
			while ($lineCount < 11) {
				if ($input->post($type.'_type_'.$lineCount) && $input->post($type.'_type_'.$lineCount) != -1) {
					$data = $data + array('type_'.$lineCount=>$input->post($type.'_type_'.$lineCount),
								  'value_'.$lineCount=>$input->post($type.'_value_'.$lineCount));
				}
				$lineCount++;
			}
			$this->db->insert($this->tables['SCORING_RULES_'.strtoupper($type)],$data);
			//echo($this->db->last_query()."<br />");
		}
		return true;
	}
	/**
	 *	GET SCORING RULES.
	 *	Returns the scoring rules for a specific league (if they exist) or the 
	 *	global scoring rules in they don't
	 *  @param	$league_id - If not specified, no league filter is applied.
	 *	@return	Rules array on success, false on failure
	 */
	public function getRosterRules($league_id = false) {
		if ($league_id === false) { $league_id = $this->id; }
		$rules = array();
		$this->db->select();
		$this->db->from('fantasy_roster_rules');
		$this->db->where('league_id',$league_id);
		if ($this->db->count_all_results() == 0) {
			$league_id = 0;
			$this->db->where('league_id',$league_id);
		}
		$this->db->select();
		$this->db->order_by('position', 'asc');
		$query = $this->db->get('fantasy_roster_rules');
		//echo($this->db->last_query()."<br />");
		if ($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				//echo($row->position."<br />");
				$rules = $rules + array($row->position=>array('position'=>$row->position,
										'active_min'=>$row->active_min,'active_max'=>$row->active_max));
				
			}
		}
		return $rules;
	}
	/**
	 * SET ROSTER RULES
	 * This function accepts a form input object and applies the passed values to 
	 * the roster rules table. 
	 * @param	$input		CodeIgniter form input object
	 * @param	$league_id 	Optional league ID. Defaults to "0" if no id is passed.
	 */
	public function setRosterRules($input, $league_id = false) {
		if ($league_id === false) { $league_id = 0; }
		
		$this->db->where('league_id', $league_id);
		$this->db->delete($this->tables['ROSTER_RULES']);
		
		$lineCount = 0;
		while ($lineCount < 10) {
			$data = array();
			if ($input->post('pos'.$lineCount) && $input->post('pos'.$lineCount) != -1) {
				$data = array('league_id'=>$league_id,
							  'position'=>$input->post('pos'.$lineCount),
							  'active_min'=>$input->post('min'.$lineCount),
							  'active_max'=>$input->post('max'.$lineCount));
				$this->db->insert($this->tables['ROSTER_RULES'],$data);
			}
			$lineCount++;
		}
		$types = array(100=>'active', 101=>'reserve',102=>'injured');
		foreach($types as $code => $label) {
			$this->db->insert($this->tables['ROSTER_RULES'],array('league_id'=>$league_id,
								  'position'=>$code,
								  'active_min'=>$input->post('total_'.$label.'_min'),
								  'active_max'=>$input->post('total_'.$label.'_max')));
		}		
		return true;
	}
	
	public function validateRoster($roster,$league_id = false) {
		
		//echo("Validate Roser<br />");
		$valid = true;
		if ($league_id === false) { $league_id = $this->id; }
		$errors = "";
		
		$rules = $this->getRosterRules($league_id);
		
		$activePos = array();
		$activeCount = 0;
		$reserveCount = 0;
		$injuredCount = 0;
		//echo("Roster size = ".sizeof($roster)."<br />");
		foreach($roster as $player_info) {
			//echo("Player ".$player_info['player_name']."<br />");
			if ($player_info['player_status'] == 1) {
				if ($player_info['player_position'] != 1) {
					$pos = $player_info['player_position'];
				} else {
					$pos = $player_info['player_role'];
				}
				//echo("Player pos = ".get_pos($pos)."<br />");
			
				if (!isset($activePos[$pos])) {
					$activePos[$pos] = 1;
				} else {
					$activePos[$pos] += 1;
				}
				$activeCount++;
			} else if ($player_info['player_status'] == -1) {
				$reserveCount++;
			} else if ($player_info['player_status'] == 2) {
				$injuredCount++;
			}
		}
		foreach($rules as $position => $ruleData) {
			if ($position < 100) {
				if (isset($activePos[$position])) {
					if ($activePos[$position] < $ruleData['active_min']) {
						//echo("Count for ".strtoupper(get_pos($position))." is below the minimum of ".$ruleData['active_min']."<br />");
						$valid = false;
						$errors .= "<br />The position ".strtoupper(get_pos($position))." has ".$activePos[$position]." active players. At least ".$ruleData['active_min']." ".($ruleData['active_min']>1 ? "are" : "is")." required.";
					} else if ($activePos[$position] > $ruleData['active_max']) {
						$valid = false;
						$errors .= "<br />The position ".strtoupper(get_pos($position))." has ".$activePos[$position]." active players. At most, ".$ruleData['active_max']." ".($ruleData['active_min']>1 ? "are" : "is")." allowed.";
					}
				} else {
					if ($ruleData['active_min'] > 0) {
						$valid = false;
						$errors .= "<br />The position ".strtoupper(get_pos($position))." has 0 active players. At least ".$ruleData['active_min']." ".($ruleData['active_min']>1 ? "are" : "is")." required.";
					}
				}
			} else {
				if ($position == 100) {
					//$active = sizeof($activeCount);
					if ($activeCount < $ruleData['active_min']) {
						$valid = false;
						$errors .= "<br />Your teams has ".$activeCount." active players. At least ".$ruleData['active_min']." ".($ruleData['active_min']>1 ? "are" : "is")." required.";
					} else if ($activeCount > $ruleData['active_max']) {
						$valid = false;
						$errors .= "<br />Your teams has ".$activeCount." active players. At most, ".$ruleData['active_max']." ".($ruleData['active_max']>1 ? "are" : "is")." allowed.";
					}
				}
				if ($position == 101) {;
					if ($reserveCount < $ruleData['active_min']) {
						$valid = false;
						$errors .= "<br />Your teams has ".$reserveCount." reserve players. At least ".$ruleData['active_min']." ".($ruleData['active_min']>1 ? "are" : "is")." required.";
					} else if ($reserveCount > $ruleData['active_max']) {
						$valid = false;
						$errors .= "<br />Your teams has ".$reserveCount." reserve players. At most, ".$ruleData['active_max']." ".($ruleData['active_max']>1 ? "are" : "is")." allowed.";
					}
				}
				if ($position == 102) {;
					if ($injuredCount < $ruleData['active_min']) {
						$valid = false;
						$errors .= "<br />Your teams has ".$injuredCount." inured reserrve players. At least ".$ruleData['active_min']." ".($ruleData['active_min']>1 ? "are" : "is")." required.";
					} else if ($injuredCount > $ruleData['active_max']) {
						$valid = false;
						$errors .= "<br />Your teams has ".$injuredCount." injured reserve players. At most, ".$ruleData['active_max']." ".($ruleData['active_max']>1 ? "are" : "is")." allowed.";
					}
				}
			}
					
		}
		if (!$valid) $this->errorCode = 1;
		$this->statusMess = $errors;
		return $valid;
	}
	
	public function getGamesForPeriod($period_id = false, $excludeList = array(), $league_id = false) {
		
		if ($period_id === false) { $period_id = 1; }
		if ($league_id === false) { $league_id = $this->id; }
		$games = array();
		$this->db->select('id, home_team_id, home_team_score, away_team_id, away_team_score');
		$this->db->where('league_id',$league_id);
		$this->db->where('scoring_period_id',$period_id);
		$query = $this->db->get('fantasy_leagues_games');
		if ($query->num_rows() > 0) {
			foreach ($query->result() as $row) {
				// RESOLVE TEAM NAMES
				$homeTeamName = resolveTeamName($row->home_team_id);
				$awayTeamName = resolveTeamName($row->away_team_id);
				$home_score = $row->home_team_score;
				$away_score = $row->away_team_score;
				if (sizeof($excludeList) > 0) {
					if (in_array($row->home_team_id, $excludeList)) {
						$home_score = 0;
					}
					if (in_array($row->away_team_id, $excludeList	)) {
						$away_score = 0;
					}
				}
				$games = $games + array($row->id=>array('home_team_id'=>$row->home_team_id, 'home_team_name'=>$homeTeamName, 'home_team_score'=>$home_score, 
														'away_team_id'=>$row->away_team_id, 'away_team_name'=>$awayTeamName, 'away_team_score'=>$away_score));
			}
		}
		$query->free_result();
		return $games;
	}
	public function getAvailableScoringPeriods($league_id = false) {
		if ($league_id === false) { $league_id = $this->id; }
		$periods = array();

		$this->db->flush_cache();
		$this->db->select('scoring_period_id');
		$this->db->where('league_id',$league_id);
		$this->db->group_by('scoring_period_id');
		$query = $this->db->get('fantasy_players_scoring');
		if ($query->num_rows() == 0) {
			$league_id = 0;
		}
		$query->free_result();
		$this->db->flush_cache();
		$this->db->select('scoring_period_id');
		$this->db->where('league_id',$league_id);
		$this->db->group_by('scoring_period_id');
		$query = $this->db->get('fantasy_players_scoring');
		if ($query->num_rows() > 0) {
			foreach ($query->result() as $row) {
				array_push($periods,$row->scoring_period_id);
			}
		}
		$query->free_result();
		asort($periods);
		return $periods;
	}
	public function getAvailableRosterPeriods($league_id = false) {
		if ($league_id === false) { $league_id = $this->id; }
		$periods = array();

		$this->db->flush_cache();
		$this->db->select('scoring_period_id');
		$this->db->where('league_id',$league_id);
		$this->db->group_by('scoring_period_id');
		$query = $this->db->get('fantasy_rosters');
		if ($query->num_rows() == 0) {
			$league_id = 0;
		}
		$query->free_result();
		$this->db->flush_cache();
		$this->db->select('scoring_period_id');
		$this->db->where('league_id',$league_id);
		$this->db->group_by('scoring_period_id');
		$query = $this->db->get('fantasy_rosters');
		if ($query->num_rows() > 0) {
			foreach ($query->result() as $row) {
				array_push($periods,$row->scoring_period_id);
			}
		}
		$query->free_result();
		asort($periods);
		return $periods;
	}
	public function copyRosters($old_scoring_period, $new_scoring_period, $league_id = false) {
		if ($league_id === false) { $league_id = $this->id; }
		
		$this->db->select('*');
		$this->db->from('fantasy_rosters');
		$this->db->where('league_id',$league_id);
		$this->db->where('scoring_period_id',$old_scoring_period);
		$pQuery = $this->db->get();
		if ($pQuery->num_rows() > 0) {
			foreach($pQuery->result() as $row) {
				$data = array('league_id'=>$league_id, 'team_id'=>$row->team_id, 'player_id'=>$row->player_id, 
							  'player_position'=>$row->player_position, 'scoring_period_id'=>$new_scoring_period, 'player_role'=>$row->player_role, 'player_status'=>$row->player_status);
				$this->db->insert('fantasy_rosters',$data);
			}
		}
		$pQuery->free_result();
		return true;
	}
	public function updateTeamRecords($scoring_period, $league_id = false, $excludeList = array()) {
		
		if ($league_id === false) { $league_id = $this->id; }
		
		// GET ALL TEAMS
		$teams = array();
		$this->db->select("fantasy_teams.id, g, w, l"); 
		$this->db->join("fantasy_teams_record","fantasy_teams_record.team_id = fantasy_teams.id","left");
		$this->db->where("fantasy_teams.league_id",$league_id);
		$query = $this->db->get("fantasy_teams");
		if ($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				$games = 0;
				$wins = 0;
				$losses = 0;
				$this->db->flush_cache();
				$this->db->select('id, away_team_id, away_team_score, home_team_id, home_team_score');
				$this->db->where('(away_team_id = '.$row->id.' OR home_team_id = '.$row->id.')');
				$this->db->where('scoring_period_id',$scoring_period['id']);
				$this->db->where('league_id',$league_id );
				$gQuery = $this->db->get('fantasy_leagues_games');
				if ($gQuery->num_rows() > 0) {
						foreach($gQuery->result() as $gRow) {
						if ($gRow->away_team_id == $row->id) {
							$teamScore = $gRow->away_team_score;
							$oppScore = $gRow->home_team_score;
						} else {
							$teamScore = $gRow->home_team_score;
							$oppScore = $gRow->away_team_score;
						}
						if ($teamScore > $oppScore) {
							$wins++;
						} else {
							$losses++;
						}
						$games++;
					}
				}
				$gQuery->free_result();
				$games += $row->g;
				$wins += $row->w;
				$losses += $row->l;
				$perc = 0;
				if ($games > 0) {
					$perc = ($wins/$games);
				}
				$data = array("w"=>$wins,"l"=>$losses,"g"=>$games,'pct'=>$perc);
				
				$this->db->flush_cache();
				$this->db->select('id');
				$this->db->where('team_id',$row->id);
				$this->db->where("league_id",$league_id);
				$tQuery = $this->db->get('fantasy_teams_record');
				if ($tQuery->num_rows() == 0) {
					$this->db->flush_cache();
					$data['team_id'] = $row->id;
					$data['league_id'] = $league_id;
					$this->db->insert('fantasy_teams_record',$data);
				} else {
					$this->db->flush_cache();
					$this->db->where('team_id',$row->id);
					$this->db->where("league_id",$league_id);
					$this->db->update('fantasy_teams_record',$data);
				}
			}
		}
		$query->free_result();
	}
	/**
	 * 	AUTO DRAFT.
	 *  Runs automatic roster drafts for a given league.
	 *
	 *	@param	$max_rounds 	Maximum number of rounds to run
	 *	@param	$curr_year		The current league year
	 *	@param	$league_id		The fatntasy league ID, defaults to $id property if nothing is passed
	 *	@return					Error string or "OK" on success
	 */
	public function auto_draft($max_rounds,$curr_year, $league_id = false) {
		$errors = "";
		if ($league_id === false) { $league_id = $this->id; }
		
		// GET ALL TEAMS
		$teams = array();
		$this->db->select("id"); 
		$this->db->where("league_id",$league_id);
		$query = $this->db->get("fantasy_teams");
		if ($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				array_push($teams,$row->id);
				$this->db->where("team_id",$row->id);
				$this->db->delete("fantasy_rosters");
				//echo("Roster rows deleted = ".$this->db->affected_rows()."<br />");
			}
		}
		
		$last_year = date('Y',strtotime($curr_year)-(60*60*24*365));
		$query->free_result();
		$pos_batters = array(2,3,4,5,6,7,8,9,25);
		for ($i = 0; $i < sizeof($pos_batters); $i++) {
			$batters = array();
			if ($pos_batters[$i] == 25) {
				$sqlPos = $pos_batters[rand(0,(sizeof($pos_batters)-2))];
				$pickedBatters = array();
				$this->db->select("player_id"); 
				$this->db->where('league_id',$league_id);
				$query = $this->db->get("fantasy_rosters");
				if ($query->num_rows() > 0) {
					foreach($query->result() as $row) {
						array_push($pickedBatters,$row->player_id);
					}
				}
				$query->free_result();
			} else {
				$sqlPos = $pos_batters[$i];
			}
			$this->db->select("fantasy_players.id"); 
			$this->db->join("players_career_batting_stats",'players_career_batting_stats.player_id = fantasy_players.player_id','left');
			$this->db->join("players",'players.player_id = fantasy_players.player_id','left');
			$this->db->where("(fantasy_players.player_status = 1 OR fantasy_players.player_status = 3)");
			$this->db->where('players.position',$sqlPos);
			$this->db->where('players.retired',0);
			$this->db->where('players_career_batting_stats.year',$last_year);
			if(isset($pickedBatters) && !empty($pickedBatters)) $this->db->where_not_in('fantasy_players.id',$pickedBatters);
			$this->db->order_by("players_career_batting_stats.vorp",'desc');
			$query = $this->db->get("fantasy_players");
			
			echo("sql = ".$this->db->last_query()."<br />");
			if ($query->num_rows() > 0) {
				$count = 0;
				foreach($query->result() as $row) {
					array_push($batters,$row->id);
					//echo("batter id = ".$row->id."<br />");
					$count++;
					if ($count >= sizeof($teams)) break;
				}
				shuffle($batters);
				foreach($teams as $team_id) {
					if ($pos_batters[$i] == 7 || $pos_batters[$i] == 8 || $pos_batters[$i] == 9) {
						$pos = 20;
					} else {
						$pos = $pos_batters[$i];
					}
					$data = array('player_id'=>$batters[0],'league_id'=>$league_id,'team_id'=>$team_id,'scoring_period_id'=>1,'player_position'=>$pos,
								  'player_role'=>-1,'player_status'=>1);
					$this->db->insert('fantasy_rosters',$data);
					array_shift($batters);
				}
			}
		}
		$pos_pitchers = array(11=>5,12=>2);
		foreach ($pos_pitchers as $pos => $draftCount) {
			$pitchers = array();
			$this->db->select("fantasy_players.id"); 
			$this->db->join("players_career_pitching_stats",'players_career_pitching_stats.player_id = fantasy_players.player_id','left');
			$this->db->join("players",'players.player_id = fantasy_players.player_id','left');
			$this->db->where("fantasy_players.player_status",1);
			$this->db->where('players.position',1);
			if ($pos == 12) {
				$this->db->where('players.role',13);
			} else {
				$this->db->where('players.role',$pos);
			}
			$this->db->where('players.retired',0);
			$this->db->where('players_career_pitching_stats.year',$last_year);
			$this->db->order_by("players_career_pitching_stats.vorp",'desc');
			$query = $this->db->get("fantasy_players");
			//echo("last query = ".$this->db->last_query()."<br />");
			if ($query->num_rows() > 0) {
				$count = 0;
				foreach($query->result() as $row) {
					array_push($pitchers,$row->id);
					$count++;
					if ($count >= (sizeof($teams)*$draftCount)) break;
				}
				shuffle($pitchers);
				for ($i = 0; $i < $draftCount; $i++) {
					foreach($teams as $team_id) {
						$data = array('player_id'=>$pitchers[0],'league_id'=>$league_id,'team_id'=>$team_id,'scoring_period_id'=>1,'player_position'=>1,
									  'player_role'=>$pos,'player_status'=>1);
						$this->db->insert('fantasy_rosters',$data);
						array_shift($pitchers);
					}
				}
			}
		}
		if (empty($errors)) $errors = "OK"; else  $errors = $errors;
		return $errors;
	}
	/**
	 * 	UPDATE LEAGUE SCORING
	 *  Runs scoring against each leagues scoring rules for all players.
	 *
	 *	@param	$scoring_period The scoring period to compile
	 *	@param	$league_id		The fatntasy league ID, defaults to $id property if nothing is passed
	 *	@param	$ootp_league_id	The OOTP League ID to run stats from
	 *	@return	TRUE on success, FALSE on ERROR
	 */
	public function updateLeagueScoring($scoring_period, $excludeList = array(), $league_id = false, $ootp_league_id = 100) {
		
		if ($league_id === false) { $league_id = $this->id; }
		
		// LOAD RELEVANT SCORING CATEGORIES
		$rules = $this->getScoringRules($league_id);
		
		if (isset($rules) && sizeof($rules) > 0) {
		
			// UPDATE SCORING FOR ALL PLAYERS FOR THIS PERIOD
			$player_list = array();
			$this->db->flush_cache();
			$this->db->select("fantasy_players.id, fantasy_players.player_id, position, role, player_status");
			$this->db->join("players","players.player_id = fantasy_players.player_id","left");
			$this->db->where("player_status",1);
			$query = $this->db->get("fantasy_players");
			//echo($this->db->last_query()."<br />");
			if ($query->num_rows() > 0) {
				//echo("Number of players found = ".$query->num_rows()."<br />");
				foreach($query->result() as $row) {
					// BUILD QUERY TO PULL CURRENT GAME DATA FOR THIS PLAYER
					if ($row->position != 1) {
						$type = "batting";
						$table = "players_game_batting";
					} else {
						$type = "pitching";
						$table = "players_game_pitching_stats";
					}
					$select = "";
					foreach($rules[$type] as $cat => $val) {
						if ($select != '') { $select.=","; }
						$select .= strtolower(get_ll_cat($cat, true));
					}
					$this->db->flush_cache();
					$this->db->select($select);
					$this->db->join($table,'games.game_id = '.$table.'.game_id','left');
					$this->db->where($table.'.player_id',$row->player_id);
					$this->db->where("DATEDIFF('".$scoring_period['date_start']."',games.date)<=",0);
					$this->db->where("DATEDIFF('".$scoring_period['date_end']."',games.date)>=",0);
					$gQuery = $this->db->get('games');
					//echo("Num of games found for player ".$row->player_id." = ".$gQuery->num_rows() .", status = ".$row->player_status."	<br/>");
					//echo($this->db->last_query()."<br />");
					if ($gQuery->num_rows() > 0) {
						
						$score_vals = array();
						$totalVal = 0;
						foreach ($gQuery->result() as $sRow) {
							$colCount = 0;
							// APPLY VALUES TO THE STATS AND SAVE THEM TO THE SCORING TABLE
							foreach($rules[$type] as $cat => $val) {
								$fVal = 0;
								$colName = strtolower(get_ll_cat($cat, true));
								if (isset($score_vals['value_'.$colCount])) {
									$score_vals['value_'.$colCount] += $sRow->$colName;
								} else {
									$score_vals['value_'.$colCount] = $sRow->$colName;
								}
								if ($sRow->$colName != 0) {
									$totalVal += $sRow->$colName * $val;
								}
								$colCount++;
							}
						}
						$score_vals['total'] = $totalVal;
						//echo("Player ".$row->player_id." total = ".$totalVal.", status = ".$row->player_status."	<br/>");
						//if ($row->player_status == 1) { $team_score += $totalVal; }
						//echo("Team ".$team_id." total = ".$team_score."<br/>");
						if (sizeof($score_vals) > 0) {
							$this->db->flush_cache();
							$this->db->select('id');
							$this->db->where('player_id',$row->id);
							$this->db->where('scoring_period_id',$scoring_period['id']);
							$this->db->where('league_id',$rules['league_id']);
							$tQuery = $this->db->get('fantasy_players_scoring');
							if ($tQuery->num_rows() == 0) {
								$this->db->flush_cache();
								$score_vals['player_id'] = $row->id;
								$score_vals['scoring_period_id'] = $scoring_period['id'];
								$score_vals['league_id'] = $rules['league_id'];
								$this->db->insert('fantasy_players_scoring',$score_vals);
							} else {
								$this->db->flush_cache();
								$this->db->where('player_id',$row->id);
								$this->db->where('scoring_period_id',$scoring_period['id']);
								$this->db->where('league_id',$rules['league_id']);
								$this->db->update('fantasy_players_scoring',$score_vals);
							}
							$tQuery->free_result();
						}
					}
					$gQuery->free_result();
				}
			}
		}
		// GET ALL TEAMS
		$teams = array();
		$this->db->select("id"); 
		$this->db->where("league_id",$league_id);
		$query = $this->db->get("fantasy_teams");
		if ($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				array_push($teams,$row->id);
			}
		}
		$query->free_result();
		foreach($teams as $team_id) {
			//echo("Team Id = ".$team_id."<br />");
			
			// GET PLAYERS FOR TEAM
			$teamRoster = array();
			$team_score = 0;
			// ONLE GET ROSTERS AND CORES IF THJIS TEAM IF IT HAS VALID ROSTERS
			if (sizeof($excludeList) == 0 || (sizeof($excludeList) > 0  && !in_array($team_id, $excludeList))) {
				$this->db->select("player_id");
				$this->db->where("team_id",intval($team_id));
				$this->db->where("player_status",1);
				$this->db->where("scoring_period_id",intval($scoring_period['id']));
				$query = $this->db->get("fantasy_rosters");
				if ($query->num_rows() > 0) {
					foreach($query->result() as $row) {
						array_push($teamRoster,$row->player_id);
					}
				}
				$query->free_result();

				$this->db->flush_cache();
				$this->db->distinct();
				$this->db->where_in("player_id",$teamRoster);
				$this->db->where("fantasy_players_scoring.scoring_period_id",intval($scoring_period['id']));
				$query = $this->db->get("fantasy_players_scoring");
				//echo($this->db->last_query()."<br />");
					if ($query->num_rows() > 0) {
					foreach($query->result() as $row) {
						$team_score += $row->total;
					}
				}
				$query->free_result();
			}
			// LOOK UP AND UPDATE THE SCORES OF ANY GAMES THIS TEAM IS PLAYING IN
			$this->db->flush_cache();
			$this->db->select('id, away_team_id, home_team_id');
			$this->db->where('(away_team_id = '.$team_id.' OR home_team_id = '.$team_id.')');
			$this->db->where('scoring_period_id',$scoring_period['id']);
			$this->db->where('league_id',$league_id );
			$query = $this->db->get('fantasy_leagues_games');
			//echo($this->db->last_query()."<br />");
			//echo("scoring period id = ".$scoring_period['id']."<br />");
			//echo("Number of games found for team ".$team_id." = ".$query->num_rows()."<br />");
			if ($query->num_rows() > 0) {
				foreach($query->result() as $row) {
					$score = array();
					if ($row->away_team_id == $team_id) {
						$col = 'away_team';
					} else {
						$col = 'home_team';
					}
					//echo("update col = ".$col."<br />");
					$score[$col.'_score'] = $team_score;
					$this->db->flush_cache();
					$this->db->where('id',$row->id);
					$this->db->update('fantasy_leagues_games',$score);
					//echo($this->db->last_query()."<br />");
				}
			}
			$query->free_result();
		}
		return false;
	}
	
	public function loadGameData($game_id = false, $team_model, $excludeList = array()) {
		if ($game_id === false) return false;
		// FIRST GET THE TEAMS INVOLVED
		$teams = array();
		$scoring_period = 0;
		$this->db->select('home_team_id, away_team_id, scoring_period_id');
		$this->db->where('id',$game_id);
		$query = $this->db->get('fantasy_leagues_games');
		if ($query->num_rows() > 0) {
			$row = $query->row();
			$teams = array('home'=>$row->home_team_id, 'away'=>$row->away_team_id);
			$scoring_period = $row->scoring_period_id;
		}
		$query->free_result();
		
		
		// LOAD RELEVANT SCORING CATEGORIES
		$rules = $this->getScoringRules($this->id);
			
		// NOW GET EACH TEAMS ROSTERS
		$rosters = array('home'=>array(),'away'=>array());
		foreach ($teams as $key => $team_id) {
			// GET ACTIVE BATTERS
			if ($team_model->load($team_id)) {
				$team_data = array('id'=>$team_id,'team_name'=>$team_model->teamname." ".$team_model->teamnick,
								   'players_active'=>array(),'players_reserve'=>array());
				$statuses = array(1, -1,2);
				foreach ($statuses as $status) {
					$player_list = array();
					$players = $team_model->getBatters($scoring_period, false,$status) + $team_model->getPitchers($scoring_period, false,$status);
					foreach ($players as $player_id => $player_data) {
						//echo("PLayer position = ".$player_data['player_position']."<br />");
						if ($player_data['player_position'] != 1) {
							$type = "batting";
							$pos = $player_data['player_position'] ;
						} else {
							$type = "pitching";
							$pos = $player_data['player_role'];
						}
						// GET PLAYER DATA
						$this->db->select('*');
						$this->db->where('fantasy_players_scoring.player_id', $player_data['id']);
						$this->db->where('fantasy_players_scoring.league_id',$rules['league_id']);
						$this->db->where('fantasy_players_scoring.scoring_period_id',$scoring_period);
						$pQuery = $this->db->get('fantasy_players_scoring');
						$pRow = false;
						$stats = "";
						$total = 0;
						if (sizeof($excludeList) == 0 || (sizeof($excludeList) > 0 && !in_array($team_id,$excludeList))) {
							if ($pQuery->num_rows() > 0) {
								$pRow = $pQuery->row();
								$colCount = 0;
								foreach($rules[$type] as $cat => $val) {
									$colName = "value_".$colCount;
									if ($pRow->$colName != 0) {
										$stats .= $pRow->$colName." ".strtoupper(get_ll_cat($cat));
										if (($colCount+1) != sizeof($rules[$type])) { $stats.=", "; }
									}
									$colCount++;
								}
								$total = $pRow->total;
							}
							$pQuery->free_result();
						}
						$player_list = $player_list + array($player_data['id']=>array('name'=>$player_data['first_name']." ".$player_data['last_name'],
																			  'stats'=>$stats,'total'=>$total,'position'=>$pos,
																			  'injury_is_injured'=>$player_data['injury_is_injured'],
																			  'injury_dl_left'=>$player_data['injury_dl_left'], 'injury_left'=>$player_data['injury_left'], 'injury_dtd_injury'=>$player_data['injury_dtd_injury'],
																			  'injury_id'=>$player_data['injury_id'],'injury_career_ending'=>$player_data['injury_career_ending']));
					}
					if ($status == 1) $team_data['players_active'] = $player_list; 
					else $team_data['players_reserve'] = $team_data['players_reserve'] + $player_list; 
				}
				$rosters[$key] = $team_data;
			}
		}
		return $rosters;
	}
	
	public function getMemberCount($league_id = false) {
		if ($league_id === false) { $league_id = $this->id; }
		$count = 0;
		$this->db->where('league_id',$league_id);
		$this->db->from('fantasy_teams');
		$count = $this->db->count_all_results();
		return $count;
	}
	public function getDivisionNames($league_id = false) {
		if ($league_id === false) { $league_id = $this->id; }
		$divisions = array();
		$this->db->select('id, division_name');
		$this->db->where('league_id',$league_id);
		$query = $this->db->get('fantasy_divisions');
		if ($query->num_rows() > 0) {
			foreach ($query->result() as $row) {
				$divisions = $divisions + array($row->id=>$row->division_name);
			}
		}
		$query->free_result();
		return $divisions;
	}
	public function getTeamDetails($league_id = false,$selectBox = false) {
		
		if ($league_id === false) { $league_id = $this->id; }
		
		$this->db->select('id, teamname, teamnick, owner_id');
		$this->db->where('league_id',$league_id);
		$this->db->order_by('id','asc');
		$query2 = $this->db->get('fantasy_teams');
		$teams = array();
		if ($selectBox != false) { $teams = array('-1'=>""); }
		if ($query2->num_rows() > 0) {
			//echo("Teams for league".$league_id." = <br/>");
			foreach ($query2->result() as $trow) {
				if ($selectBox != false) { 
					$teams = $teams + array($trow->id=>$trow->teamname." ".$trow->teamnick);
				} else {
					$teams = $teams + array($trow->id=>array('teamname'=>$trow->teamname,'teamnick'=>$trow->teamnick,
													     'owner_id'=>$trow->owner_id));
				}
			}
		}
		$query2->free_result();
		return $teams;
	}
	public function getTeamIdList($league_id = false) {
		if ($league_id === false) { $league_id = $this->id; }
		$this->db->select('id');
		$this->db->where('league_id',$league_id);
		$this->db->order_by('id','asc');
		$query2 = $this->db->get('fantasy_teams');
		$teams = array();
		if ($query2->num_rows() > 0) {
			foreach ($query2->result() as $trow) {
				array_push($teams, $trow->id);
			}
		}
		$query2->free_result();
		return $teams;
	}
		
	public function getFullLeageDetails($league_id = false) {
		if ($league_id === false) { $league_id = $this->id; }
		$divisions = array();
		$this->db->select('id, division_name');
		$this->db->where('league_id',$league_id);
		$this->db->order_by('division_name','asc');
		$query = $this->db->get('fantasy_divisions');
		if ($query->num_rows() > 0) {
			foreach ($query->result() as $row) {
				$this->db->flush_cache();
				$this->db->select('fantasy_teams.id, teamname,teamnick,fantasy_teams.owner_id,firstName, lastName, email,fantasy_teams.avatar,auto_draft,auto_list,auto_round_x');
				$this->db->join('users_core','users_core.id = fantasy_teams.owner_id','left');
				$this->db->join('users_meta','users_meta.userId = fantasy_teams.owner_id','left');
				$this->db->where('league_id',$league_id);
				$this->db->where('division_id',$row->id);
				$this->db->order_by('teamname, teamnick','asc');
				$query2 = $this->db->get('fantasy_teams');
				$teams = array();
				if ($query2->num_rows() > 0) {
					foreach ($query2->result() as $trow) {
						$ownerName = $trow->firstName." ".$trow->lastName;
						if ($trow->owner_id == $this->commissioner_id) {
							$ownerName .= " (Commisioner)";
						}
						$teams = $teams + array($trow->id=>array('teamname'=>$trow->teamname,'teamnick'=>$trow->teamnick,
																'owner_id'=>$trow->owner_id,'owner_name'=>$ownerName ,
																'owner_aim'=>'','owner_email'=>$trow->email,
																'avatar'=>$trow->avatar,'auto_draft'=>$trow->auto_draft,
																'auto_list'=>$trow->auto_list,'auto_round_x'=>$trow->auto_round_x));
					}
				}
				$query2->free_result();
				$divisions = $divisions + array($row->id=>array('division_name'=>$row->division_name,'teams'=>$teams));
			}
		}
		$query->free_result();
		return $divisions;
	}
	public function getLeagueStandings($league_id = false) {
		if ($league_id === false) { $league_id = $this->id; }
		$divisions = array();
		$this->db->select('id, division_name');
		$this->db->where('league_id',$league_id);
		$query = $this->db->get('fantasy_divisions');
		if ($query->num_rows() > 0) {
			foreach ($query->result() as $row) {
				$this->db->flush_cache();
				$this->db->select('fantasy_teams.id, teamname, teamnick, g, w, l, pct');
				$this->db->join('fantasy_teams_record','fantasy_teams_record.team_id = fantasy_teams.id','left');
				$this->db->where('fantasy_teams.league_id',$league_id);
				$this->db->where('fantasy_teams.division_id',$row->id);
				$this->db->order_by('pct','desc');
				$query2 = $this->db->get('fantasy_teams');
				$teams = array();
				if ($query2->num_rows() > 0) {
					foreach ($query2->result() as $trow) {
						$teams = $teams + array($trow->id=>array('teamname'=>$trow->teamname,'teamnick'=>$trow->teamnick,
																'g'=>$trow->g,'w'=>$trow->w,'l'=>$trow->l,'pct'=>$trow->pct));
					}
				}
				$query2->free_result();
				$divisions = $divisions + array($row->id=>array('division_name'=>$row->division_name,'teams'=>$teams));
			}
		}
		$query->free_result();
		return $divisions;
	}

	/*---------------------------------------
	/	PRIVATE/PROTECTED FUNCTIONS
	/--------------------------------------*/

}  