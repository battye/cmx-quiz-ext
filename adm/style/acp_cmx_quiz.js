// Delete quiz tag
var deleteEvent = function() {
    var tagId = $(this).data('id');
    var child = $('input[name="cmx_quiz_tags[' + tagId + ']');
    var parent = child.parent();

    // Remove the tag
    parent.remove();
};

$('.acp_cmx_quiz_tag_delete').click(deleteEvent);

// Add new tag
var newTags = -1;

$('#acp_cmx_quiz_tag_add').click(function() {
    $('.acp_cmx_quiz_tag_list').append(
        '<div class="acp_cmx_quiz_tag_edit">' + 
            '<input type="text" class="text" name="cmx_quiz_tags[' + newTags + ']" value="" /> <a href="javascript:void(0)" data-id="' + newTags + '" class="acp_cmx_quiz_tag_delete">[x]</a>' +
        '</div>'
    );

    // Because we are adding a new element to the DOM we have to rebind the click event
    $('.acp_cmx_quiz_tag_delete').click(deleteEvent);

    // Decrement the id so that when we submit we know it's new (because it's negative)
    // and we can keep it unique from other newly added tags
    newTags--;
});