// Change the text name for the BBCode
function changeTextName(name) {
    text_name = name;
}

// This way of doing it binds automatically for dynamically added elements
$(document).on('select click keyup focus', '.inputbox', function() {
    var name = $(this).attr('name');
    changeTextName(name);
});

// Adding a new question
$('#cmx_quiz_new_question').click(function() {
    // Add a new question panel
    if ($('.cmx_quiz_panel').length >= maximumQuestions) {
        alert(alertTooManyQuestions);
    }

    else {
        // Keep count of the number of questions
        currentNumberOfQuestions++;

        // Add a new panel, but firstly copy the latest one
        var latestPanel = $(".cmx_quiz_panel").last().clone();

        // Need to clear the values in the panel
        latestPanel.find('.inputbox').val('');

        // Name question
        latestPanel.find('textarea').attr({ 
            name: "message[" + currentNumberOfQuestions + "]", 
            id: "message[" + currentNumberOfQuestions + "]"
        });

        // Only show one answer field for a new question, make sure the correct answer is unchecked
        latestPanel.find('.cmx_quiz_multiple_choice_answer:not(:eq(0))').remove();
        latestPanel.find('.cmx_quiz_multiple_choice_answer > input[type="checkbox"]').prop("checked", false);   

        // Name (multiple?) choice
        latestPanel.find('.cmx_quiz_multiple_choice_answer > input[type="text"]').attr({ 
            name: "answer[" + currentNumberOfQuestions + "][]", 
            id: "answer[" + currentNumberOfQuestions + "][]"
        });     

        // Name correct answer checkbox
        latestPanel.find('.cmx_quiz_multiple_choice_answer > input[type="checkbox"]').attr({ 
            name: "correct_answer[" + currentNumberOfQuestions + "][]", 
            id: "correct_answer[" + currentNumberOfQuestions + "][]"
        });

        // Give the question id to a data attribute
        latestPanel.attr({
            "data-question": currentNumberOfQuestions,
        });

        // Now add it to the bottom of the list
        latestPanel.appendTo("#cmx_quiz_question_list");
    }
});

// Deleting a question
$(document).on('click', '.cmx_quiz_question_delete', function() {
    if ($('.cmx_quiz_panel').length > 1) {
        // Only remove a question if there is more than one
        var parent = $(this).closest('.cmx_quiz_panel');
        parent.remove();
    }
});

// Deleting an answer
$(document).on('click', '.cmx_quiz_answer_delete', function() {
    var parent = $(this).parent();
    parent.parent().remove();
});

// Adding an answer
$(document).on('click', '.cmx_quiz_answer_add', function() {
    var parent = $(this).parent().find('.cmx_quiz_answers');

    // Get the question id from the outermost container
    var question = parent.parent().parent().parent().data('question');

    parent.append(
        '<div class="cmx_quiz_multiple_choice_answer">' + 
            '<input class="inputbox autowidth" type="text" name="answer[' + question + '][]" id="answer[' + question + '][]" size="45" maxlength="124" /> ' +
            '<input class="inputbox autowidth" type="checkbox" name="correct_answer[' + question + '][]" id="correct_answer[' + question + '][]" /> <a href="javascript:void(0)" class="cmx_quiz_answer_delete">[x]</a>' +
        '</div>'
    );
});

// Quick validation before submitting
$("#add_edit_form").submit(function(event) {
    // Ajax submission
    event.preventDefault();

    var form = $(this);

    var valid = true;
    var invalidReason = '';
    var questionIds = [];
    var questionSubmissionArray = {
        "questions": []
    };

    // Check each question has text
    $(".cmx_quiz_panel").each(function() {
        var value = $(this).data('question');
        questionIds.push(value);

        // Check question
        var questionText = $('textarea[name="message[' + value + ']"]').val();

        if (questionText.length < 1) {
            // Validation error: empty question
            valid = false;
console.log('4565');
            invalidReason = invalidQuestionEmpty;
        }

        // Check answer
        var answerTexts = [];
        $('input[name="answer[' + value + '][]"]').each(function() {
            answerTexts.push($(this).val());
        });

        // Check correct answers
        var correctAnswers = [];
        var atLeastOneCorrect = false;

        $('input[name="correct_answer[' + value + '][]"]').each(function() {
            var correctValue = ($(this).is(":checked") == true) ? true : false;
            correctAnswers.push(correctValue);

            if (correctValue) {
                atLeastOneCorrect = true;
            }
        });

        if (!atLeastOneCorrect) {
            // Validation error: this question has no answer marked as correct
            valid = false;
console.log('11');
            invalidReason = invalidQuestionMissingCorrect;
        }

        if (correctAnswers.length != answerTexts.length) {
            // Validation error: some mismatch with the correct answers and the answers
            valid = false;
console.log('1221');
            invalidReason = invalidQuestionMismatch;
        }

        var answerSubmissionSubArray = [];
        for (var i = 0; i < answerTexts.length; i++) {
            if (answerTexts[i].length < 1) {
                // Validation error: empty answer
                valid = false;
console.log('333');
                invalidReason = invalidQuestionMissingAnswer;
            }

            answerSubmissionSubArray.push({
                "answer": answerTexts[i],
                "correct": correctAnswers[i]
            });
        }

        questionSubmissionArray.questions.push({
            "question": questionText,
            "answers": answerSubmissionSubArray
        });
    });

    // Uncomment this to debug question data:
    // console.log(questionSubmissionArray);

    if (!valid) { 
        // Don't submit because the form is invalid, show a message to the user
        alert(invalidQuestionData + invalidReason);
    }  

    else {
        // Post the request if the form is valid
        $loadingIndicator = phpbb.loadingIndicator();

        $.ajax({
            url: formUrl,
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                // Name and description
                "quiz_name": $("#title").val(),
                "quiz_description": $("#description").val(),

                // Data fields
                "question_data": questionSubmissionArray,
                "tags_data": (document.getElementById('tags')) ? $('#tags').val() : [],

                // Configurable play settings
                "maximum_time_limit_minutes": (document.getElementById('limit')) ? $('#limit').val() : "",
                "maximum_attempts": (document.getElementById('attempts')) ? $('#attempts').val() : "",
                "minimum_pass_mark": (document.getElementById('percentage')) ? $('#percentage').val() : "",

                // Usergroup rewards
                "pass_mark_group_id": $('#group').val(),

                // Delete quiz
                "delete_quiz": (document.getElementById('delete_quiz')) ? $('#delete_quiz').is(':checked') : "",
            }),
            dataType: "JSON",
            success: function(response) { 
                phpbb.alert(response.MESSAGE_TITLE, response.MESSAGE_TEXT);

                if (response.IS_VALID) {
                    // If the quiz submitted successfully, return to the quiz index page
                    setTimeout(function() {
                        window.location = response.REFRESH_DATA.url;
                    }, response.REFRESH_DATA.time * 1000);
                }

                else {
                    // Otherwise re-enable the submit button so the user can re-submit
                    $('input[name="submit"]').prop("disabled", false);
                }
            }
        }).always(function() {
            // Show the spinny loader
            if ($loadingIndicator && $loadingIndicator.is(':visible')) {
                $loadingIndicator.fadeOut(phpbb.alertTime);
            }
        });

        // Disable the submit button to prevent double submission
        $('input[name="submit"]').prop("disabled", true);
    }
});

// Play functions
$('.cmx_quiz_play_button').click(function () {
    window.location = startQuizButton;
});