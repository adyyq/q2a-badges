<?php

	class badge_check {
		
// main event processing function
		
		function process_event($event, $userid, $handle, $cookieid, $params) {
			switch ($event) {

				// when a new question, answer or comment is created. The $params array contains full information about the new post, including its ID in $params['postid'] and textual content in $params['text'].
				case 'q_post':
					$this->question_post($event,$userid,$params);
					break;
				case 'a_post':
					$this->answer_post($event,$userid,$params);
					break;
				case 'c_post':
					$this->comment_post($event,$userid,$params);
					break;

				// when a question, answer or comment is modified. The $params array contains information about the post both before and after the change, e.g. $params['content'] and $params['oldcontent'].
				case 'q_edit':
					$this->question_edit($event,$userid,$params);
					break;
				case 'a_edit':
					$this->answer_edit($event,$userid,$params);
					break;
				case 'c_edit':
					$this->comment_edit($event,$userid,$params);
					break;

				// when an answer is selected or unselected as the best answer for its question. The IDs of the answer and its parent question are in $params['postid'] and $params['parentid'] respectively.
				case 'a_select':
					$this->answer_select($event,$userid,$params);
					break;
				case'a_unselect':
					break;

				// when a question, answer or comment is hidden or shown again after being hidden. The ID of the question, answer or comment is in $params['postid'].
				case 'q_hide':
				case 'a_hide':
				case 'c_hide':
				case 'q_reshow':
				case 'a_reshow': 
				case 'c_reshow':
					break;

				// when a question, answer or comment is permanently deleted (after being hidden). The ID of the appropriate post is in $params['postid'].
				case 'a_delete':
				case 'q_delete':
				case 'c_delete':
					break;

				// when an anonymous question, answer or comment is claimed by a user with a matching cookie clicking 'I wrote this'. The ID of the post is in $params['postid'].
				case 'q_claim':
				case 'a_claim':
				case 'c_claim':
					break;

				// when a question is moved to a different category, with more details in $params.
				case 'q_move':
					break;

				// when an answer is converted into a comment, with more details in $params.
				case 'a_to_c':
					break;

				// when a question or answer is upvoted, downvoted or unvoted by a user. The ID of the post is in $params['postid'].
				case 'q_vote_up':
					$this->question_vote_up($event,$userid,$params);
					break;
				case 'a_vote_up':
					$this->answer_vote_up($event,$userid,$params);
					break;
				case 'q_vote_down':
					$this->question_vote_down($event,$userid,$params);
					break;
				case 'a_vote_down':
					$this->answer_vote_down($event,$userid,$params);
					break;
				case 'q_vote_nil':
				case 'a_vote_nil':
					break;
				// when a question, answer or comment is flagged or unflagged. The ID of the question, answer or comment is in $params['postid'].
				case 'q_flag':
					$this->question_flag($event,$userid,$params);
					break;
				case 'a_flag':
					$this->answer_flag($event,$userid,$params);
					break;
				case 'c_flag':
					$this->comment_flag($event,$userid,$params);
					break;
				case 'q_unflag':
					break;
				case 'a_unflag':
					break;
				case 'c_unflag':
					break;

				// when a new user registers. The email is in $params['email'] and the privilege level in $params['level'].
				case 'u_register':
					break;

				// when a user logs in or out of Q2A.
				case 'u_login': 
				case 'u_logout':
					break;

				// when a user successfully confirms their email address, given in $params['email'].
				case 'u_confirmed':
					$this->check_email_award($event,$userid,$params);
					break;

				// when a user successfully resets their password, which was emailed to $params['email'].
				case 'u_reset':
					break;

				// when a user saves (and has possibly changed) their Q2A account details.
					// check for full details
				case 'u_save':
					break;

				// when a user sets (and has possibly changed) their Q2A password.
				case 'u_password':
					break;

				// when a user's account details are saved by someone other than the user, i.e. an admin. Note that the $userid and $handle parameters to the process_event() function identify the user making the changes, not the user who is being changed. Details of the user being changed are in $params['userid'] and $params['handle'].
				case 'u_edit':
					break;

				// when a user's privilege level is changed by a different user. See u_edit above for how the two users are identified. The old and new levels are in $params['level'] and $params['oldlevel'].
					//$this->priviledge_flag($params['level'],$params['userid']);
				case 'u_level':
					break;

				// when a user is blocked or unblocked by another user. See u_edit above for how the two users are identified.
				case 'u_block':
				case 'u_unblock':
					break;

				// when a message is sent via the Q2A feedback form, with more details in $params.
				case 'feedback':
					break;

				// when a search is performed. The search query is in $params['query'] and the start position in $params['start'].
				case 'search':
					break;
			}
		}

// badge checking functions
		
	// check on post
		
		function question_post($event,$event_user,$params) {
			$id = $params['postid'];

			// asker check
			
			$this->check_question_number($event_user,$id);
			
		}
		
		function answer_post($event,$event_user,$params) {
			$id = $params['postid'];

			// answerer check
			
			$this->check_answer_number($event_user,$id);
			
		}
		
		function comment_post($event,$event_user,$params) {
			$id = $params['postid'];

			// commenter check
			
			$this->check_comment_number($event_user,$id);
			
		}
		
		// count total posts
		
		function check_question_number($uid,$oid) {
			$posts = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT postid FROM ^posts WHERE userid=# AND type=$',
					$uid, 'Q'
				),
				true
			);
			
			// sheer volume of posts
			
			$badges = array('asker','questioner','inquisitor');
			
			foreach($badges as $badge_slug) {
				if(count($posts) >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
							$uid, $badge_slug
						),
						true
					);
					
					if (!$result) { // not already awarded this badge
						$this->award_badge($oid, $uid, $badge_slug);
					}
				}
			}
		}
		
		function check_answer_number($uid,$oid) {
			$posts = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT postid FROM ^posts WHERE userid=# AND type=$',
					$uid, 'A'
				),
				true
			);

			// sheer volume of posts
			
			$badges = array('answerer','lecturer','preacher');
			
			foreach($badges as $badge_slug) {
				if(count($posts) >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
							$uid, $badge_slug
						),
						true
					);
					
					if (!$result) { // not already awarded this badge
						$this->award_badge($oid, $uid, $badge_slug);
					}
				}
			}
		}
		
		function check_comment_number($uid,$oid) {
			$posts = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT postid FROM ^posts WHERE userid=# AND type=$',
					$uid, 'C'
				),
				true
			);

			// sheer volume of posts
			
			$badges = array('commenter','commentator','annotator');
			
			foreach($badges as $badge_slug) {
				if(count($posts) >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
							$uid, $badge_slug
						),
						true
					);
					
					if (!$result) { // not already awarded this badge
						$this->award_badge($oid, $uid, $badge_slug);
					}
				}
			}
		}
		
	// check on votes
		
		function question_vote_up($event,$event_user,$params) {
			$id = $params['postid'];
			$post = $this->get_post_data($id);
			$votes = $post['netvotes'];
			$userid = $post['userid'];
			
			// vote volume check
			
			$this->check_voter($event_user,$id);

			// post owner upvotes check

			$badges = array('nice_question','good_question','great_question');

			foreach($badges as $badge_slug) {
				if($votes  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
							$userid, $id, $badge_slug
						),
						true
					);
					if (!$result) { // not already awarded for this question
						$this->award_badge($id, $userid, $badge_slug);
					}
				}
			}			
		}
		
		// check number of votes on answer
		
		function answer_vote_up($event,$event_user,$params) {
			$id = $params['postid'];
			$post = $this->get_post_data($id);
			$votes = $post['netvotes'];
			$userid = $post['userid'];

			// vote volume check
			
			$this->check_voter($event_user,$id);

			// post owner upvotes check

			$badges = array('nice_answer','good_answer','great_answer');

			foreach($badges as $badge_slug) {
				if($votes  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
							$userid, $id, $badge_slug
						),
						true
					);
					if (!$result) { // not already awarded for this answer
						$this->award_badge($id, $userid, $badge_slug);
					}

					// self-answer vote checks TODO

					// old question answer vote checks
					
					$qid = $params['parentid'];
					$create = new DateTime($post['created']);
					
					$parent = $this->get_post_data($id);
					$pcreate = new DateTime($parent['created']);
					
					$diffd = $pcreate->diff($create);
					$diff = $diffd->format('%d'); 
				error_log($votes);	
					$badge_slug2 = $badge_slug.'_old';
					
					if($diff  >= (int)qa_opt('badge_'.$badge_slug2.'_var') && qa_opt('badge_'.$badge_slug2.'_enabled') !== '0') {
						$result = qa_db_read_one_value(
							qa_db_query_sub(
								'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND object_id=# AND badge_slug=$',
								$userid, $id, $badge_slug2
							),
							true
						);
						if (!$result) { // not already awarded for this answer
							$this->award_badge($id, $userid, $badge_slug2);
						}
					}
				}
			}
		}

		function question_vote_down($event,$event_user,$params) {
			$id = $params['postid'];
			
			// vote volume check
			
			$this->check_voter($event_user,$id);
		}

		function answer_vote_down($event,$event_user,$params) {
			$id = $params['postid'];
			
			// vote volume check
			
			$this->check_voter($event_user,$id);
		}

		function check_voter($uid,$oid) {
			
			$votes = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT userid FROM ^uservotes WHERE userid=# AND vote !=#',
					$uid, 0
				),
				true
			);

			$badges = array('voter','avid_voter','devoted_voter');

			foreach($badges as $badge_slug) {
				if(count($votes)  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
							$uid, $badge_slug
						),
						true
					);
					
					if (!$result) { // not already awarded this badge
						$this->award_badge($oid, $uid, $badge_slug);
					}
				}
			}
		}

	// check on selected answer

		function answer_select($event,$event_user,$params) {
			$qid = $params['parentid'];
			$aid = $params['postid'];
			$a = $this->get_post_data($aid);
			$auid = $a['userid'];
			
			// sheer number of own answers selected by others
			
			$this->check_best_answers($auid);
			
			// sheer number of answers selected by self

			$this->check_selected_answers($event_user);
			
		
		}

		function check_best_answers($uid) {
			$count = qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT aselects FROM ^userpoints WHERE userid=#',
					$uid
				),
				true
			);

			$badges = array('gifted','wise','enlightened');

			foreach($badges as $badge_slug) {
				if((int)$count  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
							$uid, $badge_slug
						),
						true
					);
					
					if (!$result) { // not already awarded this badge
						$this->award_badge(NULL, $uid, $badge_slug);
					}
				}
			}			
		}
		
		function check_selected_answers($uid) {
			$count = qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT aselecteds FROM ^userpoints WHERE userid=#',
					$uid
				),
				true
			);

			$badges = array('grateful','respectful','reverential');

			foreach($badges as $badge_slug) {
				if((int)$count  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
							$uid, $badge_slug
						),
						true
					);
					
					if (!$result) { // not already awarded this badge
						$this->award_badge(NULL, $uid, $badge_slug);
					}
				}
			}			
		}
	
	// check on edit
	
		function question_edit($event,$event_user,$params) {

			if($params['content'] == $params['oldcontent']) return;
			
			$this->add_edit_count($event_user);
			
			// sheer edit volume
			$this->check_editor($event_user);
			
		}

		function answer_edit($event,$event_user,$params) {

			if($params['content'] == $params['oldcontent']) return;
			
			$this->add_edit_count($event_user);
			
			// sheer edit volume
			
			$this->check_editor($event_user);
			
		}

		function comment_edit($event,$event_user,$params) {

			if($params['content'] == $params['oldcontent']) return;
			
			$this->add_edit_count($event_user);
			
			// sheer edit volume
			
			$this->check_editor($event_user);
			
		}
		
		function add_edit_count($uid) {
			
			qa_db_query_sub(
				'UPDATE ^achievements SET posts_edited=posts_edited+1 WHERE user_id=#',
				$uid
			);
					
		}
		
		function check_editor($uid) {
			$count = qa_db_read_one_value(
				qa_db_query_sub(
					'SELECT posts_edited FROM ^achievements WHERE user_id=#',
					$uid
				),
				true
			);

			$badges = array('editor','copy_editor','senior_editor');

			foreach($badges as $badge_slug) {
				if((int)$count  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
							$uid, $badge_slug
						),
						true
					);
					
					if (!$result) { // not already awarded this badge
						$this->award_badge(NULL, $uid, $badge_slug);
					}
				}
			}			
		}		

	// check on flags

		function question_flag($event,$event_user,$params) {
			$id = $params['postid'];
			
			// flag volume check
			
			$this->check_flagger($event_user,$id);
		}

		function answer_flag($event,$event_user,$params) {
			$id = $params['postid'];
			
			// flag volume check
			
			$this->check_flagger($event_user,$id);
		}

		function comment_flag($event,$event_user,$params) {
			$id = $params['postid'];
			
			// flag volume check
			
			$this->check_flagger($event_user,$id);
		}

		function check_flagger($uid,$oid) {
			$flags = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT userid FROM ^uservotes WHERE userid=# AND flag = #',
					$uid, 1
				),
				true
			);

			$badges = array('watchdog','bloodhound','pitbull');

			foreach($badges as $badge_slug) {
				if(count($flags)  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
							$uid, $badge_slug
						),
						true
					);
					
					if (!$result) { // not already awarded this badge
						$this->award_badge($oid, $uid, $badge_slug);
					}
				}
			}
		}
		
		// verified email check for badge 
		function check_email_award($event,$event_user,$params) {
			
			$badge_slug = 'verified';
			
			if(qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
				$result = qa_db_read_one_value(
					qa_db_query_sub(
						'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
						$event_user, $badge_slug
					),
					true
				);
				
				if (!$result) { // not already awarded this badge
					$this->award_badge(null, $event_user, $badge_slug);
				}
			}
		}

	// check on badges
	
		function check_badges($uid) {
			$medals = qa_db_read_all_values(
				qa_db_query_sub(
					'SELECT user_id FROM ^userbadges WHERE user_id=#',
					$user_id
				),
				true
			);

			$badges = array('medalist','champion','olympian');

			foreach($badges as $badge_slug) {
				if(count($medals)  >= (int)qa_opt('badge_'.$badge_slug.'_var') && qa_opt('badge_'.$badge_slug.'_enabled') !== '0') {
					$result = qa_db_read_one_value(
						qa_db_query_sub(
							'SELECT badge_slug FROM ^userbadges WHERE user_id=# AND badge_slug=$',
							$uid, $badge_slug
						),
						true
					);
					
					if (!$result) { // not already awarded this badge
						$this->award_badge($oid, $uid, $badge_slug, true); // this is a "badge badge"
					}
				}
			}			
		}


// worker functions

		
		function award_badge($object_id, $user_id, $badge_slug, $badge_badge = false, $silent = false) {
			
			// add badge to userbadges
			
			qa_db_query_sub(
				'INSERT INTO ^userbadges (awarded_at, notify, object_id, user_id, badge_slug, id) '.
				'VALUES (NOW(), #, #, #, #, 0)',
				(silent?0:1), $object_id, $user_id, $badge_slug
			);
			
			// check for sheer number of badges, unless this badge was for number of badges (avoid recursion!)
			if(!$badge_badge) $this->check_badges($user_id);
		}

		function priviledge_notify($object_id, $user_id, $badge_slug) {
			
		}
		
		function get_post_data($id) {
			$result = qa_db_read_one_assoc(
				qa_db_query_sub(
					'SELECT * FROM ^posts WHERE postid=#',
					$id
				),
				true
			);
			return $result;
		}
	}
