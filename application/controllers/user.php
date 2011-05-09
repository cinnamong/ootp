<?php
/**
 *	USER.
 *	The primary controller for user profiles and perosnal user account management and functionality.
 *	@author			Jeff Fox
 *	@dateCreated	04/04/10
 *	@lastModified	01/10/11
 *
 */
class user extends MY_Controller {
	
	/*--------------------------------
	/	VARIABLES
	/-------------------------------*/
	/**
	 *	SLUG.
	 *	@var $_NAME:Text
	 */
	var $_NAME = 'user';
	/*-------------------------------------------
	/
	/	 SITE SPECIFIC METHODS
	/
	/------------------------------------------*/
	/**
	 *	GET URI DATA.
	 *	Parses out an id or other parameters from the uri string
	 *
	 */
	protected function getURIData() {
		parent::getURIData();
		if ($this->input->post('mode')) {
			$this->uriVars['mode'] = $this->input->post('mode');
		} // END if
		if ($this->input->post('id')) {
			$this->uriVars['id'] = $this->input->post('id');
		} // END if
		if ($this->input->post('userId')) {
			$this->uriVars['userId'] = $this->input->post('userId');
		} // END if
		if ($this->input->post('league_id')) {
			$this->uriVars['league_id'] = $this->input->post('league_id');
		} // END if
		if ($this->input->post('team_id')) {
			$this->uriVars['team_id'] = $this->input->post('team_id');
		} // END if
		if ($this->input->post('ck')) {
			$this->uriVars['ck'] = $this->input->post('ck');
		} // END if
		if ($this->input->post('ct')) {
			$this->uriVars['ct'] = $this->input->post('ct');
		} // END if
	}
	/**
	 *	MAKE NAV BAR
	 *
	 */
	protected function makeNav() {
		$loggedIn = $this->params['loggedIn'];
		array_push($this->params['subNavSection'],user_nav($loggedIn, $this->user_meta_model->firstName." ".$this->user_meta_model->lastName));
	}
	/**
	 *	CREATE LEAGUE
	 *	Runs a check of league limits and either allows or dienies a user to build a new league.
	 *
	 *	@since		1.0
	 *	@updated	1.0.3
	 */
	public function createLeague() {
		$addleague = false;
		if (!isset($this->league_model)) {
			$this->load->model('league_model');
		}
		/*-------------------------------------------------
		/ UPDATE 1.0.3 - LEGAUE RESTRICTION TEST
		/ UPDATED TO ALLOW FOR AMDINS TO OWN UNLIMITED LEAGUES AND TO SET A NUMBER OF LEAGUES PER USER
		/------------------------------------------------*/
		$isAdmin = $this->params['accessLevel'] == ACCESS_ADMINISTRATE;
		$session_auth = $this->session->userdata($this->config->item('session_auth'));
		$this->user_meta_model->load($session_auth,'userId');
		$leagueCount = $this->user_meta_model->getUserLeagueCount();
		if ($isAdmin && ($this->params['config']['restrict_admin_leagues'] == -1 || ($this->params['config']['restrict_admin_leagues'] == 1 && $this->params['config']['max_user_leagues'] < $leagueCount))) {
			$addleague = true;
		} else if ($this->params['config']['users_create_leagues'] == 1) {
			//echo("Max league count = ".$this->params['config']['max_user_leagues']."<br />");
			//echo("User league count = ".$leagueCount."<br />");
			if ($leagueCount < $this->params['config']['max_user_leagues']) {
				$addleague = true;
			} else {
				$plural = "s";
				if ($this->params['config']['max_user_leagues'] == 1) { $plural = ""; }
				$mess = str_replace('[MAX_LEAGUES]',$this->params['config']['max_user_leagues'],$this->lang->line('user_too_many_leagues'));
				$mess = str_replace('[PLURAL]',$plural,$mess);
				$mess = str_replace('[CONTACT_URL]',anchor('/about/contact/','contact the game owner'),$mess);
				$this->data['theContent'] = $mess;
			}
		} else if ($this->params['config']['users_create_leagues'] != 1) {
			$this->data['theContent'] = $this->lang->line('no_user_leagues');
		} // END if
		if ($addleague) {
			redirect('league/submit/mode/add');
		} else {
			$this->params['subTitle'] = $this->data['subTitle'] ="Create League";
			$this->params['content'] = $this->load->view($this->views['MESSAGE'], $this->data, true);
			$this->makeNav();
			$this->displayView();
		}
	}
	/**
	 * INVITE RESPONSE
	 *
	 * @return void
	 **/
	public function inviteResponse() {
		$error = false;
		$cleanDB = false;
		$url = '';
		$message = '';
		$inviteId = -1;
		$inviteObj = NULL;
		$accetped = false;
		$this->getURIData();
		if ((isset($this->uriVars['league_id']) && isset($this->uriVars['email']) && isset($this->uriVars['ck'])) || isset($this->uriVars['id'])) {
			$this->db->select('*');
			if (isset($this->uriVars['league_id']) && isset($this->uriVars['email'])) {
				$this->db->where('league_id',$this->uriVars['league_id']);
				$this->db->where('to_email',$this->uriVars['email']);
			} else if (isset($this->uriVars['id'])) {
				$this->db->where('id',$this->uriVars['id']);
			}
			$query = $this->db->get('fantasy_invites');
			if ($query->num_rows() > 0) {
				$inviteObj  = $query->row();
				$inviteId = $inviteObj->id;
			}
			$query->free_result();
		}
		if ($inviteId  == -1) {
			$error = true;
			$message = "An error occured when processing your invitation response. The invitation ID code could not be found in our records. Please contact the league commissioner to request a new invitation be sent or for help with this error.";
		} else {
			if ($this->params['loggedIn']) {
				// VALIDITY CHECK, prevent spam bots
				$confirm = md5($inviteObj->confirm_str.$inviteObj->confirm_key);
				if ($confirm == $this->uriVars['ck']) {
					if (isset($this->uriVars['ct']) && $this->uriVars['ct'] == 1) {
						$owners = $this->league_model->getOwnerIds($inviteObj->league_id);
						if (!in_array($this->params['currUser'],$owners)) {
							//echo("Loading team ".$inviteObj->team_id."<br />");
							//echo("setting owner id ".$this->params['currUser']."<br />");
							$this->load->model('team_model');
							$this->team_model->load($inviteObj->team_id);
							$this->team_model->owner_id = $this->params['currUser'];
							$this->team_model->save();
							//echo("Team owner id = ".$this->team_model->owner_id."<br />");
							$message = 'You have been set as the owner of the '.$this->team_model->teamname.'. You can now visit your '.anchor('/team/info/'.$inviteObj->team_id,'teams page').' and begin managing your team.';
							$url = 'user/profile/view/';
							$cleanDB = true;
							$accetped = true;
						} else {
							$error = true;
							$message = "<b>Invite Error</b><br /><br />We see that you already own a team in this league. You are not allowed to own more than one team in a league at a time.";
							$cleanDB = true;
						}
					} else if (isset($this->uriVars['ct']) && $this->uriVars['ct'] == -1) {
						$message = 'You have chosent to decline this invitation. We\'re sorry you decided not to join. An email has been sent to the league commissioner to inform them of your choice.';
						$cleanDB = true;
					} else {
						$error = true;
						$message = "A required confirmation parameter was not recieved. Please contact the league commissioer to let them know if this issue.";
					}
				} else {
					$message = 'A required validation key did not match that in our records. Your invitation could not be validated at this time.';
					$error = true;
				}
			} else {
				$this->session->set_userdata('inviteId',$inviteObj->id);
				$this->session->set_userdata('confirmKey',$this->uriVars['ck']);
				$this->session->set_userdata('confirmType',$this->uriVars['ct']);
				$this->session->set_userdata('loginRedirect',current_url());	
				redirect('user/login');
			}
		}
		if ($cleanDB) {
			$this->db->flush_cache();
			$this->db->where('id',$inviteObj->id);
			$this->db->delete('fantasy_invites');	
			
			$this->session->unset_userdata('inviteId');
			$this->session->unset_userdata('confirmKey');
			$this->session->unset_userdata('confirmType');
		}
		if (!$error) {
			$message = '<span class="success">'.$message .'</span>';
			if ($accetped) { 
				$message .= '<br /><br />'.anchor('/team/info/'.$inviteObj->team_id,'Go to your team page').'<br /><br />';
			}
		} else {
			$message = '<span class="error">'.$message .'</span >';
		}
		$this->data['subTitle'] = 'Team Invitation Response';
		$this->data['theContent'] = $message;
		$this->params['content'] = $this->load->view($this->views['MESSAGE'], $this->data, true);
		$this->displayView();
	}
	/*-------------------------------------------
	/
	/	 STATIC CLASS METHODS
	/
	/------------------------------------------*/
	
	/*--------------------------------
	/	C'TOR
	/-------------------------------*/
	/**
	 *	Creates a new instance of user.
	 */
	function user() {
		parent::MY_Controller();
		$this->views['REGISTER'] = DIR_VIEWS_USERS.'register';
		$this->views['LOGIN'] = DIR_VIEWS_USERS.'login';
		$this->views['CHANGE_PASSWORD'] = DIR_VIEWS_USERS.'change_password';
		$this->views['PROFILE'] = DIR_VIEWS_USERS.'profile_info';
		$this->views['PROFILE_EDIT'] = DIR_VIEWS_USERS.'profile_edit';
		$this->views['PROFILE_PICK'] = DIR_VIEWS_USERS.'profile_pick';
		$this->views['ACCOUNT'] = DIR_VIEWS_USERS.'account';
		$this->views['ACCOUNT_EDIT'] = DIR_VIEWS_USERS.'account_edit';
		$this->views['AVATAR_UPLOAD'] = DIR_VIEWS_USERS.'profile_avatar';
		$this->views['PENDING'] = 'content_pending';
		$this->views['MESSAGE'] = DIR_VIEWS_USERS.'user_message';
		$this->views['FORGOT_PASSWORD'] = DIR_VIEWS_USERS.'forgotten_password';
		$this->views['FORGOT_PASSWORD_VERIFY'] = DIR_VIEWS_USERS.'forgotten_password_verify';
		
		$this->enqueStyle('content.css');
		$this->debug = false;
	}
	/**
	 *	INDEX.
	 *	The default handler when the controller is called.
	 *	Checks for an existing auth session, and if found,
	 *	redirects to the dashboard. Otherwise, it redirects 
	 *	to the login.
	 */
	function index() {
		redirect('user/profile');
	}	
	/*---------------------------------------
	/	CONTROLLER SUBMISSION HANDLERS
	/--------------------------------------*/
	/**
	 * Account
	 *
	 * @return void 
	 **/
	public function account() {
		if ($this->params['loggedIn']) {
	        $this->getURIData();
			$func = "view";
			if (isset($this->uriVars['id'])) {
				$func = $this->uriVars['id'];
			} else if (isset($this->uriVars['mode'])) {
				$func = $this->uriVars['mode'];
			} // END if
			$this->data['account'] = $this->auth->accountDetails();
			/*--------------------------------------
			/	View the account details
			/-------------------------------------*/
			if ($func == "view") {
				$levelStr = '';
				if ($this->data['account']->levelId != -1 && $this->data['account']->levelId != 0) {
					$levelList = loadSimpleDataList('userLevel');
					foreach($levelList as $key => $value) {
						if ($this->data['account']->levelId == $key) {
							$levelStr = "L".$key." - ".$value;
							break;
						} // END if
					} // END foreach
				} // END if
				$this->data['account']->userLevel = $levelStr;
				
				$typeStr = '';
				if ($this->data['account']->typeId != -1 && $this->data['account']->typeId != 0) {
					$typeList = loadSimpleDataList('userType');
					foreach($typeList as $key => $value) {
						if ($this->data['account']->typeId == $key) {
							$typeStr = $value;
							break;
						} // END if
					} // END foreach
				} // END if
				$this->data['account']->userType = $typeStr;
				
				$accessStr = '';
				if ($this->data['account']->accessId != -1 && $this->data['account']->accessId != 0) {
					$accessList = loadSimpleDataList('accessLevel');
					foreach($accessList as $key => $value) {
						if ($this->data['account']->accessId == $key) {
							$accessStr = "L".$key." - ".$value;
							break;
						} // END if
					} // END foreach
				} // END if
				$this->data['account']->accessLevel = $accessStr;
				$this->params['subTitle'] = $this->data['subTitle'] = 'Account Details';
				$this->params['content'] = $this->load->view($this->views['ACCOUNT'], $this->data, true);
				$this->makeNav();
				$this->displayView();
			/*--------------------------------------
			/	Edit the account details
			/-------------------------------------*/
			} else {
				$this->form_validation->set_rules('email', 'Email Address', 'required|trim|valid_email');
				$this->form_validation->set_error_delimiters('<p class="error">', '</p>');
				
				if ($this->form_validation->run() == false) {
					$this->params['subTitle'] = $this->data['subTitle'] = 'Edit Account';
					$this->data['input'] = $this->input;
					$this->params['content'] = $this->load->view($this->views['ACCOUNT_EDIT'], $this->data, true);
					$this->params['pageType'] = PAGE_FORM;
					$this->makeNav();
					$this->displayView();
				} else {
					if ($this->input->post('email') != $this->data['account']->email) {
						$this->db->select('email')->from('users_core')->where('email',$this->input->post('email'));
						$query = $this->db->get();
						if ($query->num_rows() != 0) {
							$this->session->set_flashdata('message', '<p class="error">Account Update Failed. The email address <b>'.$this->input->post('email').'</b> is already in use. Please choose a different e-mail address.</p>');
							redirect('user/account/edit');
						} // END if
					} // END if
					$session_auth = $this->session->userdata($this->config->item('session_auth'));
					$change = $this->auth->account_update($this->input,$session_auth);
					if ($change) {
						$this->session->set_flashdata('message', '<p class="success">Your account details were successfully changed.</p>');
						redirect('user/account');
					} else {
						$message = '<p class="error">Account Update Failed.';
						if ($this->auth->get_status_code() != 0) {
							$message .= ' '.$this->auth->get_status_message().'</p>';
						} // END if
						$message .= '</p >';
						$this->session->set_flashdata('message', $message);
						redirect('user/account/edit');
					} // END if
				} // END if
			} // END if
	    } else {
	        $this->session->set_flashdata('loginRedirect',current_url());	
			redirect('user/login');
	    } // END if
	}
	/**
	 * Avatar
	 *
	 * @return void 
	 **/
	public function avatar() {
		if ($this->params['loggedIn']) {
			$session_auth = $this->session->userdata($this->config->item('session_auth'));
			$this->user_meta_model->load($session_auth,'userId');
			$this->data['avatar'] = $this->user_meta_model->avatar;
			$this->data['subTitle'] = 'Update Avatar';
			if (!($this->input->post('submitted')) || ($this->input->post('submitted') && !isset($_FILES['avatarFile']['name']))) {
				if ($this->input->post('submitted') && !isset($_FILES['avatarFile']['name'])) {
					$fv = & _get_validation_object();
					$fv->setError('avatarFile','The Avatar File field is required.');
				} // END if
				$this->params['content'] = $this->load->view($this->views['AVATAR_UPLOAD'], $this->data, true);
				$this->params['pageType'] = PAGE_FORM;
				$this->makeNav();
				$this->displayView();
			} else {
				$change = $this->user_meta_model->applyData($this->input, $this->params['currUser']); 
				if ($change) {
					$this->user_meta_model->save();
					$this->session->set_flashdata('message', '<p class="success">Your avatar has been successfully updated.</p>');
					redirect('user/profile');
				} else {
					$message = '<p class="error">Avatar Change Failed.';
					if ($this->auth->get_status_code() != 0) {
						$message .= ' '.$this->auth->get_status_message().'</p>';
					} // END if
					$message .= '</p >';
					$this->session->set_flashdata('message', $message);
					redirect('user/avatar');
				} // END if
			} // END if
		} else {
	        $this->session->set_flashdata('loginRedirect',current_url());	
			redirect('user/login');
	    } // END if
	}
	/**
	 * Preferences
	 * Placeholder for futre preferences editor and viewa.
	 * @return void
	 **/
	public function preferences() {
		if ($this->params['loggedIn']) {
	      	$this->params['content'] = $this->load->view('content_pending', null, true);
	        $this->makeNav();
			$this->displayView();
	    } else {
	        $this->session->set_flashdata('loginRedirect',current_url());	
			redirect('user/login');
	    }
	}
	/**
	 * change password
	 *
	 * @return void
	 **/
	function change_password() {	    
		if ($this->params['loggedIn']) {
			$this->form_validation->set_rules('old', 'Old password', 'required');
			$this->form_validation->set_rules('new', 'New Password', 'required|matches[new_repeat]');
			$this->form_validation->set_rules('new_repeat', 'Repeat New Password', 'required');

			if ($this->form_validation->run() == false) {
				$this->params['subTitle'] = "Profile";
				$this->params['content'] = $this->load->view($this->views['CHANGE_PASSWORD'], null, true);
				$this->params['pageType'] = PAGE_FORM;
				$this->makeNav();
				$this->displayView();
			} else {
				
				$session_auth = $this->session->userdata($this->config->item('session_auth'));
				$change = $this->auth->change_password($session_auth, $this->input->post('old'), $this->input->post('new'));
			
				if ($change) {
					$this->session->set_flashdata('message', '<p class="success">Your password was successfully changed.</p>');
					redirect('user/account');
				} else {
					$message = '<p class="error">Password Change Failed.';
					if ($this->auth->get_status_code() != 0) {
						$message .= ' '.$this->auth->get_status_message().'</p>';
					}
					$message .= '</p >';
					$this->session->set_flashdata('message', $message);
					redirect('user/change_password');
				}
			}
		} else {
	        $this->session->set_flashdata('loginRedirect',current_url());	
			redirect('user/login');
	    }
	}
	/**
	 * forgotten password
	 *
	 * @return void
	 * @author Mathew
	 **/
	function forgotten_password() {
	    $this->makeNav();
		$this->form_validation->set_rules('email', 'Email Address', 'required|valid_email|max_length[500]');
	    if ($this->form_validation->run() == false) {
	       	$this->data['subTitle'] = $this->lang->line('user_forgotpass_title');
			$this->data['theContent'] = str_replace('[SITE_URL]',$this->params['config']['fantasy_web_root'],$this->lang->line('user_forgotpass_instruct'));
		   	$this->params['content'] = $this->load->view($this->views['FORGOT_PASSWORD'], $this->data, true);
	        $this->params['pageType'] = PAGE_FORM;
	    } else {
	        $email = $this->input->post('email');
			$forgotten = $this->auth->forgotten_password($email,$this->debug);
			if ($forgotten) {
				$this->session->set_flashdata('message', '<p class="success">An email has been sent to '.$email.'. Please check your inbox. Message:<br />'.$forgotten.'<br /></p>');
	            $data['content'] = $this->load->view('home', null, true);
	        	$this->displayView();
			} else {
				$message = '<span class="error"><strong>An error has occured.</strong><br />The email failed to send.';
				if ($this->auth->get_status_code() != 0) {
					$message .= ' '.$this->auth->get_status_message();
				}
				$message .= '</span>';
	            $this->data['subTitle'] = $this->lang->line('user_forgotpass_title');
				$this->data['theContent'] = $message;
				$this->params['content'] = $this->load->view($this->views['FORGOT_PASSWORD'], $this->data, true);
				$this->params['pageType'] = PAGE_FORM;
				$this->displayView();
			}
	    }
		$this->displayView();
	}
	/**
	 * forgotten_password_verify
	 *
	 * @return void
	 **/
	public function forgotten_password_verify() {
	    $this->getURIData();
		$code = '';
		if (isset($this->uriVars['code']) && !empty($this->uriVars['code'])) {
			$code = $this->uriVars['code'];
		} else {
			$this->form_validation->set_rules('code', 'Verification Code', 'required');
			if ($this->form_validation->run() == false) {
				$this->params['content'] = $this->load->view($this->views['FORGOT_PASSWORD_VERIFY'], $this->data, true);
	       		$this->params['pageType']= PAGE_FORM;
				$this->makeNav();
				$this->displayView();
			} else {
				$code = $this->input->post('code');
			}
		}
		if (!empty($code)) {
			$forgotten = $this->auth->forgotten_password_complete($code);
			if ($forgotten) {
				$message = '<span class="success">An email has been sent, please check your inbox.<br />'.$forgotten.'</span>';
			} else {
				$message = '<span class="error">The email failed to send, try again.</span>';
			}
			$this->data['subTitle'] = $this->lang->line('user_forgotpass_title');
			$this->data['theContent'] = $message;
			$this->params['content'] = $this->load->view($this->views['FORGOT_PASSWORD_VERIFY'], $this->data, true);
			$this->params['pageType'] = PAGE_FORM;
			$this->displayView();
		}
	}
	/**
	 * login
	 *
	 * @return void
	 * @author Mathew
	 **/
	function login() {
	   
	   	if ($this->data['loggedIn']) {
			if ($this->data['accessLevel'] < ACCESS_ADMINISTRATE) {
				redirect('user');
			} else {
				redirect('admin');
			}
		} else {
			$this->form_validation->set_rules('username', 'Username', 'required');
			$this->form_validation->set_rules('password', 'Password', 'required');
			if ($this->form_validation->run() == false) {
				$this->data['input'] = $this->input;
				$this->params['content'] = $this->load->view($this->views['LOGIN'], $this->data, true);
				$this->params['subTitle'] = "User Login";
				$this->params['pageType'] = PAGE_FORM;
				$this->makeNav();
				$this->displayView();
			} else {
				if ($this->auth->login($this->input->post('username'), $this->input->post('password'))) {
					$inviteId = $this->session->userdata('inviteId');
					$redirect = $this->session->userdata('loginRedirect');
					if (isset($inviteId) && !empty($inviteId)) {
						redirect('/user/inviteResponse/id/'.$inviteId."/ck/".$this->session->userdata('confirmKey')."/ct/".$this->session->userdata('confirmType'));
					} else if (isset($redirect) && !empty($redirect)) {
						$this->session->unset_userdata('loginRedirect');
						redirect($redirect);
					} else {
						redirect('user');
					}
				} else {
					$message = '';
					if ($this->auth->get_status_code() != 0) {
						$message = '<span class="error">'.$this->auth->get_status_message().' Please try again.</span>';
					}
					$this->session->set_flashdata('message', $message);
					redirect('user/login');
				} // END if
			} // END if
		}
	}
	/**
	 * logout
	 *
	 * @return void
	 * @author Mathew
	 **/
	function logout($endSession = true) {
		$this->auth->logout($endSession);
		redirect('user');
	}
	/**
	 * Profile
	 *
	 * @return void
	 **/
	public function profile() {
		$this->getURIData();
		$func = "view";
		if (isset($this->uriVars['mode'])) {
			$func = $this->uriVars['mode'];
		}
		if (!isset($this->uriVars['mode']) && (isset($this->uriVars['id']) && ($this->uriVars['id'] == 'view' || $this->uriVars['id'] == 'edit'))) {
			$func = $this->uriVars['id'];
		}
		$view = $this->views['PROFILE'];
		if ($func == "view") {
			$view = $this->views['PROFILE'];
			$session_auth = $this->session->userdata($this->config->item('session_auth'));
			if ($session_auth && $this->user_meta_model->load($session_auth,'userId')) {
				$this->data['profile'] = $this->user_meta_model->profile();
				
				$this->data['subTitle'] = 'Your Profile';
				$this->data['userId'] = $this->user_meta_model->userId;
			} else {
				redirect('user/login');
			}
			if (!isset($this->data['profile'])) {
				$this->session->set_flashdata('message', '<span class="error">An error occured loading your profile information.</span>');
			} else {
				$countryStr = '';
				if (isset($this->data['profile']->country) && $this->data['profile']->country != -1 && $this->data['profile']->country != 0) {
					$countryList = loadCountries();
					foreach($countryList as $key => $value) {
						if ($this->data['profile']->country == $key) {
							$countryStr = $value;
							break;
						}
					}
				}
				$this->data['countryStr'] = $countryStr;
				
				$this->data['userTeams'] = $this->user_meta_model->getUserTeams();
				
				$userDrafts = $this->user_meta_model->getUserDrafts();
				
				if (!isset($this->draft_model)) {
					$this->load->model('draft_model');
				}
				foreach($this->data['userTeams'] as $data) {
					$userDrafts[$data['league_id']]['draftStatus'] = $this->draft_model->getDraftStatus($data['league_id']);
					$userDrafts[$data['league_id']]['draftDate'] = $this->draft_model->getDraftDate($data['league_id']);
				}
				$this->data['userDrafts'] = $userDrafts;
				
				
			}
			$this->params['content'] = $this->load->view($view, $this->data, true);
			$this->makeNav();
			$this->displayView();
		} else {
			$id = $this->uriVars['id'];
			if ($this->params['loggedIn']) {
				$session_auth = $this->session->userdata($this->config->item('session_auth'));
				$this->user_meta_model->load($session_auth,'userId');
				$this->data['profile'] = $this->user_meta_model->profile();
				$this->form_validation->set_rules('firstName', 'First Name', 'required|trim');
				$this->form_validation->set_rules('lastName', 'Last Name', 'required|trim');
				$this->form_validation->set_error_delimiters('<p class="error">', '</p>');
				
				if ($this->form_validation->run() == false) {
					$this->data['subTitle'] = 'Edit Profile';
					$this->data['input'] = $this->input;
					$this->params['content'] = $this->load->view($this->views['PROFILE_EDIT'], $this->data, true);
					$this->params['pageType'] = PAGE_FORM;
					$this->makeNav();
					$this->displayView();
				} else {
					$this->user_meta_model->applyData($this->input,$this->params['currUser']);
					$change =$this->user_meta_model->save();
					if ($change) {
						$this->session->set_flashdata('message', '<p class="success">Your profile has been successfully updated.</p>');
						redirect('/user/profile/');
					} else {
						$message = '<p class="error">Profile Update Failed.';
						if ($this->auth->get_status_code() != 0) {
							$message .= ' '.$this->auth->get_status_message().'</p>';
						}
						$message .= '</p >';
						$this->session->set_flashdata('message', $message);
						redirect('/user/profile/edit');
					}
				}
			} else {
	       		$this->session->set_flashdata('loginRedirect',current_url());	
				redirect('user/login');
	   		}
		}
	}
	public function profiles() {
		$this->getURIData();
		$view = $this->views['PROFILE'];
		$this->data['profile'] = NULL;
		if (isset($this->uriVars['id'])) {
			$userId = -1;
			if (preg_match('/^[0-9]$/',$this->uriVars['id'])) {
				$userId = $this->uriVars['id'];
			} else if (preg_match('/[A-z0-9]$/',$this->uriVars['id'])) {
				//echo("String id");
				// LOOK UP USER ID
				$this->db->select('id')->from('users_core')->where('username',$this->uriVars['id'])->limit(1);
				$query = $this->db->get();
				if ($query->num_rows > 0) {
					$userId = $query->row()->id;
				}
				$query->free_result();
			}
			if ($this->user_meta_model->load($userId,'userId')) {
				$this->data['profile'] = $this->user_meta_model->profile();
				$this->data['subTitle'] = 'User Profile';
			} else {
				$this->session->set_flashdata('message', '<span class="error">No user profile was be found. It\'s possible the record has been renamed, moved or deleted.</span>');
				$this->data['subTitle'] = 'User Profiles';
				$this->data['users'] = loadSimpleDataList('username');
				$view = $this->views['PROFILE_PICK'];
			}
		} else {
			$this->data['subTitle'] = 'User Profiles';
			$this->data['users'] = loadSimpleDataList('username');
			$view = $this->views['PROFILE_PICK'];
		}
		$this->params['content'] = $this->load->view($view, $this->data, true);
		$this->makeNav();
		$this->displayView();
		
	}
	/**
	 * register
	 *
	 * @return void
	 * @author Mathew
	 **/
	function register() {
	    if (!$this->params['loggedIn']) {
			$this->form_validation->set_rules('email', 'Email Address', 'required|callback_email_check|valid_email');
			$this->form_validation->set_rules('username', 'Username', 'required|min_length[3]|max_length[150]|callback_username_check');
			$this->form_validation->set_rules('password', 'Password', 'required|min_length[8]|max_length[32]|match[passwordConfirm]');			
			$this->form_validation->set_rules('passwordConfirm', 'Confirm Password', 'required|min_length[8]|max_length[32]');			
			if ($this->form_validation->run() == false) {
				$this->data['subTitle'] = $this->lang->line('user_register_title');
				$this->data['theContent'] = $this->lang->line('user_register_instruct');
		   		$this->data['activation'] = $this->lang->line('user_register_activation');
				$this->data['input'] = $this->input;
				$this->params['content'] = $this->load->view($this->views['REGISTER'], $this->data, true);
				$this->params['pageType'] = PAGE_FORM;
				$this->displayView();
			} else {       	       
				$message = $this->auth->register($this->input,$this->debug);
				if ($message !== false) {
					$this->session->set_flashdata('message', '<span class="success">You have now been successfully registered. Please login to begin using the site.</p>');
					redirect('user/login');
				} else {
					$message = '<p class="error">Something went wrong.';
					if ($this->auth->get_status_code() != 0) {
						$message .= "The server replied with the following status: <b>".$this->auth->get_status_message()."</b>";
					}	
					$message .= "</p>";
					$this->data['subTitle'] = $this->lang->line('user_register_title');
					$this->data['theContent'] = $message;
					$this->params['content'] = $this->load->view($this->views['REGISTER'], $this->data, true);
					$this->params['pageType'] = PAGE_FORM;
					$this->displayView();
				}
			}
		} else {
			$this->session->set_flashdata('message', $this->lang->line('user_register_existing'));
			redirect('home');
		}
	}
}
