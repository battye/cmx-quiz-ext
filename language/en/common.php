<?php
/**
*
* @package CMX Quiz
* @copyright (c) 2022 battye (CricketMX.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = [];
}

$lang = array_merge($lang, [
    'CMX_QUIZ'                          => 'Quiz',
    'CMX_QUIZ_NOT_ENABLED'              => 'Quizzes have been disabled.',
    'CMX_QUIZ_NOT_LOGGED_IN'            => 'You must be logged in to access this page.',
    'CMX_QUIZ_NOT_CREDENTIALLED'        => 'Only quiz moderators can access this page.',
    'CMX_QUIZ_NOT_AUTHORISED'           => 'You are not permitted to access this page.',
    'CMX_QUIZ_NOT_ENOUGH_POSTS'         => [
        1 => 'You must have at least one post to access this page.',
        2 => 'You must have at least %d posts to access this page.',
    ],

    // Submit
    'CMX_QUIZ_INFORMATION'                  => 'Quiz Information',
    'CMX_QUIZ_MINIMUM_AND_MAXIMUM_TEXT'     => 'You must enter between <strong>%d</strong> and <strong>%d</strong> questions.',
    'CMX_QUIZ_NEW_QUESTION'                 => 'New Question',
    'CMX_QUIZ_ENTER_QUESTION_TEXT'          => 'Enter the question text below:',
    'CMX_QUIZ_ENTER_ANSWER_TEXT'            => 'Enter the answer details below and <strong>tick any correct answers.</strong>',
    'CMX_QUIZ_ENTER_ANSWER_TEXT_EXPLAIN'    => 'If only one answer is supplied, players must type in the correct answer. If multiple answers are supplied, players will be shown the options as multiple choices.',
    'CMX_QUIZ_ALERT_TOO_MANY_QUESTIONS'     => 'You have reached the maximum number of questions.',
    'CMX_QUIZ_ALERT_INVALID_QUESTION_DATA'  => 'Please double check your quiz, there is an error stopping it from being submitted: ',
    'CMX_QUIZ_ALERT_EMPTY_QUESTION'         => 'There is missing question text.',
    'CMX_QUIZ_ALERT_MISSING_CORRECT'        => 'A question has no correct answer marked.',
    'CMX_QUIZ_ALERT_QUESTION_MISMATCH'      => 'There is a mismatch with the number of answers and correct answers.',
    'CMX_QUIZ_ALERT_MISSING_ANSWER'         => 'There is missing answer text.',
    'CMX_QUIZ_NAME'                         => 'Quiz name:',
    'CMX_QUIZ_DESCRIPTION'                  => 'Quiz description:',
    'CMX_QUIZ_NAME_EXPLAIN'                 => 'Enter a short title for this quiz',
    'CMX_QUIZ_DESCRIPTION_EXPLAIN'          => 'Enter a description which explains what this quiz is about',
    'CMX_QUIZ_TAGS'                         => 'Select tags:',
    'CMX_QUIZ_TIME_LIMIT'                   => 'Time limit:',
    'CMX_QUIZ_TIME_LIMIT_MINUTES'           => 'minutes',
    'CMX_QUIZ_MAXIMUM_ATTEMPTS'             => 'Maximum attempts:',
    'CMX_QUIZ_REWARD'                       => 'Reward:',
    'CMX_QUIZ_REWARD_SCORE'                 => 'If a user scores a pass mark of ',
    'CMX_QUIZ_REWARD_GROUP'                 => '% then move the user to the following usergroup: ',
    'CMX_QUIZ_REWARD_NO_GROUP'              => '-- Do not move -- ',
    'CMX_QUIZ_ANSWER_EXPLAIN'               => 'Enter an answer...',
    'CMX_QUIZ_SUBMIT_QUIZ'                  => 'Submit Quiz',
    'CMX_QUIZ_SUBMITTED_SUCCESSFULLY'       => 'Congratulations, your quiz has been successfully submitted!',
    'CMX_QUIZ_DELETED_SUCCESSFULLY'         => 'This quiz has been successfully deleted.',

    // Edit
    'CMX_QUIZ_EDIT_QUIZ'                    => 'Edit Quiz',
    'CMX_QUIZ_DELETE_QUIZ'                  => 'Delete Quiz',
    'CMX_QUIZ_DELETE_QUIZ_EXPLAIN'          => 'This action will be permanent and cannot be undone.',

    // Plays
    'CMX_QUIZ_PLAY_QUIZ'                    => 'Play Quiz',
    'CMX_QUIZ_PLAY_QUIZ_QUESTION_BLURB'     => [
        1   => 'You have <strong>1 question</strong> to complete.',
        2   => 'You have <strong>%d questions</strong> to complete.',
    ],
    'CMX_QUIZ_PLAY_QUIZ_TIME_BLURB'         => [
        1   => 'A time limit of <strong>1 minute</strong> will apply.',
        2   => 'A time limit of <strong>%d minutes</strong> will apply.',
    ],
    'CMX_QUIZ_PLAY_PASS_MARK_BLURB'         => 'The pass mark is <strong>%d%%</strong>.',
    'CMX_QUIZ_EXCEEDED_MAXIMUM_PLAYS'       => 'You have reached the maximum number of plays for this quiz.',

    // Index
    'CMX_QUIZ_NEW'                      => 'New Quiz',
    'CMX_QUIZ_SUBMITTER'                => 'Author',
    'CMX_QUIZ_DATE'                     => 'Submitted Date',
    'CMX_QUIZ_QUESTIONS'                => 'Questions',
    'CMX_QUIZ_TOTAL'                    => [
        0	=> 'No quizzes',
		1	=> '1 quiz',
		2	=> '%d quizzes',
    ],

    'CMX_QUIZ_PLAY'                     => 'Play',
    'CMX_QUIZ_PLAYS'                    => 'Plays',

    // Results
    'CMX_QUIZ_RESULTS'                  => 'Quiz Results',
    'CMX_QUIZ_RESULTS_QUESTION'         => 'Question',
    'CMX_QUIZ_RESULTS_CORRECT'          => 'Correct Answer',
    'CMX_QUIZ_RESULTS_USER'             => 'User Answer',
    'CMX_QUIZ_RESULTS_TIME_TAKEN'       => [
        0 => 'Completed in under one minute',
        1 => 'Completed in one minute',
        2 => 'Completed in %d minutes',
    ],
    'CMX_QUIZ_RESULTS_PASSED'           => 'Passed',
    'CMX_QUIZ_RESULTS_FAILED'           => 'Failed',
    'CMX_QUIZ_NO_RESULTS'               => 'There are no quiz results to view yet.',
    'CMX_QUIZ_NOT_ANSWERED'             => 'Not answered',

    // ACP modules and headings
    'ACP_CMX_QUIZ' => 'CMX Quiz',
    'ACP_CMX_QUIZ_CONFIGURATION' => 'Configuration',
    'ACP_CMX_QUIZ_SETTINGS' => 'Settings',
    'ACP_CMX_QUIZ_TAGS' => 'Tags',

    // ACP configuration
    'ACP_CMX_QUIZ_ENABLED' => 'Enable the CMX Quiz feature:',

    'ACP_CMX_QUIZ_ALLOW_TIME_LIMITS' => 'Allow time limits:',
    'ACP_CMX_QUIZ_ALLOW_TIME_LIMITS_EXPLAIN' => 'Credentialled users can specify a maximum time limit for their quizzes.',

    'ACP_CMX_QUIZ_ALLOW_MULTIPLE_ATTEMPTS' => 'Allow multiple attempts:',
    'ACP_CMX_QUIZ_ALLOW_MULTIPLE_ATTEMPTS_EXPLAIN' => 'Credentialled users can specify the maximum number of attempts for their quizzes. If set to "No", then all quizzes can be played a maximum of once per user.',

    'ACP_CMX_QUIZ_ALLOW_MOVE_TO_USERGROUP' => 'Allow usergroup rewards:',
    'ACP_CMX_QUIZ_ALLOW_MOVE_TO_USERGROUP_EXPLAIN' => 'Credentialled users can specify a usergroup that a player can be moved into if they reach a selected score on a quiz.',

    'ACP_CMX_QUIZ_ALLOW_USER_SUBMISSIONS' => 'Allow user submissions:',
    'ACP_CMX_QUIZ_ALLOW_USER_SUBMISSIONS_EXPLAIN' => 'Non-credentialled registered users will be permitted to submit quizzes.',

    'ACP_CMX_QUIZ_MINIMUM_QUESTIONS' => 'Minimum questions per quiz:',
    'ACP_CMX_QUIZ_MAXIMUM_QUESTIONS' => 'Maximum questions per quiz',

    'ACP_CMX_QUIZ_SHOW_CORRECT_ANSWERS' => 'Show correct answers:',
    'ACP_CMX_QUIZ_SHOW_CORRECT_ANSWERS_EXPLAIN' => 'Allow users to see the correct answers after they have finished a quiz.',

    'ACP_CMX_QUIZ_PER_PAGE' => 'Quizzes per page:',
    'ACP_CMX_QUIZ_PER_PAGE_EXPLAIN' => 'The number of quizzes to show per page on the index.',

    'ACP_CMX_QUIZ_MINIMUM_POSTS' => 'Minimum posts to play:',
    'ACP_CMX_QUIZ_MINIMUM_POSTS_EXPLAIN' => 'The minimum number of posts a user must have in order to play quizzes.',

    'ACP_CMX_QUIZ_MODERATE_GROUP_ID' => 'Credentialled quiz moderator usergroup:',
    'ACP_CMX_QUIZ_MODERATE_GROUP_ID_EXPLAIN' => 'Specify a usergroup if you wish to have quiz moderators become credentialled users for certain quiz operations, such as those in the settings above, editing and deleting a quiz. If left empty, only Administrators can perform this role.',

    // ACP save message
    'ACP_CMX_QUIZ_SETTINGS_SAVED' => 'CMX Quiz settings have been updated.',
]);
