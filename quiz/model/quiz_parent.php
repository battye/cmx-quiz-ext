<?php
/**
*
* @package CMX Quiz
* @copyright (c) 2022 battye (CricketMX.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace battye\cmxquiz\quiz\model;

/**
 * CMX Quiz - Parent to store common functions
 */
abstract class quiz_parent
{
    /**
     * Need a function to fill the class fields in the sub-classes
     */
    public abstract function populate(int $id); 

    /**
     * Need a function to prepare data for insert or update
     */
    public abstract function prepare(): array; 

    /**
     * Unmodified question data
     */
    public function get_raw_question_data()
    {
        $question_data = (is_array($this->question_data)) ? json_encode($this->question_data) : $this->question_data;
        return $question_data;
    }

    /**
     * Quick access for the next function
     */
    public function get_question_data_for_display()
    {
        return $this->get_question_data(false);
    }

    /** 
     * Return question data as an array
     * If $edit is false, we generate BBCode for display
     */
    public function get_question_data(bool $edit = true)
    {
        $questions = [];

        if ($this->question_data != null)
        {
            if (!is_array($this->question_data))
            {
                $questions = json_decode($this->question_data, true);
            }

            else 
            {
                $questions = $this->question_data;
            }
        }

        // Now we do some BBCode parsing
        $is_question_present = true;
        $is_submission_present = true;

        if (array_key_exists('questions', $questions))
        {
            foreach ($questions['questions'] as &$question)
            {
                if ($edit)
                {
                    // BBCode parsing for editing
                    $text = generate_text_for_edit($question['question'], $this->bbcode_uid, OPTION_FLAG_BBCODE);
                    $question['question'] = $text['text'];
                }

                else 
                {
                    // BBCode parsing for displaying
                    $question_parsed = generate_text_for_display($question['question'], $this->bbcode_uid, $this->bbcode_bitfield, OPTION_FLAG_BBCODE);
                    $question['question'] = $question_parsed;
                }

                foreach ($question['answers'] as &$answer)
                {
                    if ($edit)
                    {
                        // BBCode parsing for editing
                        $text = generate_text_for_edit($answer['answer'], $this->bbcode_uid, OPTION_FLAG_BBCODE);
                        $answer['answer'] = $text['text'];
                    }
    
                    else 
                    {
                        // BBCode parsing for displaying
                        $answer_parsed = generate_text_for_display($answer['answer'], $this->bbcode_uid, $this->bbcode_bitfield, OPTION_FLAG_BBCODE);
                        $answer['answer'] = $answer_parsed;
                    }   
                }
            }
        }

        return $questions;
    }
}