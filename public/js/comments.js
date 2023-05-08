function drawCommentTree(container, data) {
    container.html(data.commentTree)
    $("button[name='reply_button']").on("click", replyClick);
    $("button[name='show_more']").on("click", showMore);
    $("button[name='delete_comment']").on("click", deleteComment);
    $("button[name='edit_comment']").on("click", editComment);

}

function addNewComment() {
    $(this).hide()
    $(this).parent().append("<div id=\"add_new_comment\" style=\"display: flex; padding: 15px 0\">\n" +
        "        <div style=\"flex: 0 0 30%; flex-direction: column\">\n" +
        "            <textarea rows=\"4\" placeholder=\"Введите комментарий\" style=\"display: block; margin-bottom: 5px\"></textarea>\n" +
        "            <button  name=\"send_button\" data-parent-id=\"" + null + "\">Отправить</button><button id=\"add_new_comment_cancel\">Отмена</button>\n" +
        "        </div>\n" +
        "    </div>")
    $("button[name='send_button']").on("click", sendClick);
    $("#add_new_comment_cancel").on("click", addNewCommentCancel);
}


function addNewCommentCancel() {
    $("#add_new_comment").remove()
    $("#add_new_comment_button").show()
}

function addCommentInTree(comment) {

    let element
    if (comment.parentId !== null) {
        $("#field_reply_"+comment.parentId).remove()
        element = $("#replies_for_" + comment.parentId)
    } else {
        addNewCommentCancel()
        element = $("#comment-tree-wrapper")
    }
    element.append("<div id=\"comment_"+comment.id+"\" style=\"padding: 5px 20px; display: flex; flex-direction: column\">\n" +
        "    <div style=\"padding: 5px 0\">Author: "+comment.author+"</div>\n" +
        "    <div style=\"padding: 5px 0\"><span>Comment:</span> <span id=\"comment_"+comment.id+"_text\" >"+comment.comment+"</span></div>\n" +
        "    <div style=\"margin-top: 15px\">\n" +
        "        <button name=\"reply_button\" data-parent-id=\""+comment.id+"\">Ответить</button>\n" +
        "        <button name=\"edit_comment\" id=\"edit_comment_"+comment.id+"\" data-comment-id=\""+comment.id+"\">Редактировать</button>\n" +
        "         <button name=\"delete_comment\" id=\"delete_comment_"+comment.id+"\" data-comment-id=\""+comment.id+"\">Удалить</button>\n" +
        "    </div>\n" +
        "    <div id=\"replies_for_"+comment.id+"\" style=\"margin-left: "+ 30*comment.rang +"px\">\n" +
        "    </div>" +
        "</div>")
    $("button[name='reply_button']").on("click", replyClick);
    $("button[name='delete_comment']").on("click", deleteComment);
    $("button[name='edit_comment']").on("click", editComment);
}

function replyClick() {
    let parentId = $(this).data("parentId")
    if ($(this).parent().find("#field_reply_" + parentId).length === 0) {
        $(this).parent().append("<div id=\"field_reply_" + parentId + "\" style=\"display: flex; padding: 15px 0\">\n" +
            "        <div style=\"flex: 0 0 30%; flex-direction: column\">\n" +
            "            <textarea rows=\"4\" placeholder=\"Введите комментарий\" style=\"display: block; margin-bottom: 5px\"></textarea>\n" +
            "            <button name=\"send_button\" data-parent-id=\"" + parentId + "\">Отправить</button>\n" +
            "        </div>\n" +
            "    </div>")
        $("button[name='send_button']").on("click", sendClick);
    }
}

function showMore() {
    let parentId = $(this).data("parentId")
    getCommentDeeperThenFour(parentId)
}

function hideShowMoreButton(thirdLevelRoot) {
    $("#show_more_"+thirdLevelRoot).remove()
}

function deleteComment() {
    let commentId = $(this).data("commentId")
    deleteCommentRequest(commentId)
}

function deleteCommentFromTree(commentId) {
    $("#comment_"+commentId+"_text").text("Комментарий был удален автором")
    $("#edit_comment_"+commentId).remove()
    $("#delete_comment_"+commentId).remove()
}

function editComment() {
    $(this).hide()
    let commentId = $(this).data("commentId")
    let textSpan = $("#comment_"+commentId+"_text");
    let text = textSpan.text()
    textSpan.html("<div style='display: flex; flex-direction: column'><textarea  id=\"edit_comment_"+commentId+"\" rows=\"4\" placeholder=\"Введите комментарий\" style=\"display: block; flex: 0 0 50%; margin-bottom: 5px\">" +
        text + "</textarea><button name=\"send_edit_button\" data-comment-id=\"" + commentId + "\">Отправить</button></div>")
    $("button[name='send_edit_button']").on("click", sendEditButton);
}

function sendEditButton() {
    let commentId = $(this).data("commentId")
    let text = $("#edit_comment_"+commentId).val()
    editCommentRequest(commentId, {comment: text})
}

function editCommentInTree(commentId, data) {
    $("#comment_"+commentId+"_text").text(data.comment)
    $("#edit_comment_"+commentId).show()

}

function sendClick() {
    let parentId = $(this).data("parentId")
    let comment = $(this).parent().find("textarea").val()
    saveCommentRequest(parentId, comment)
}


$(document).ready(() => {
    let name = null

    $("#save_name").on("click", () => {
        name = $("#name_input").val()
        document.cookie = "username="+name+ ";";
        showAuthComplete(name)
        getFirstComments()
    })

    $("#delete_name").on("click", () => {
        showAuthProcess()
        getFirstComments()
    })

    let usernameCookie = document.cookie.match(new RegExp("username=(.*);?"))
    if (usernameCookie != null) {
        name = usernameCookie[1]
    }
    if (name === null) {
        showAuthProcess()
    } else {
        showAuthComplete(name)
    }

    $("#add_new_comment_button").on("click", addNewComment)

    getFirstComments()
})

function showAuthProcess() {
    document.cookie = "username=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    $("#process_auth").show()
    $("#auth_complete").hide()
}

function showAuthComplete(name) {
    $("#process_auth").hide()
    $("#auth_complete").show()
    $("#span_name").text(name)
}

function getFirstComments() {
    let commentWrapper = $('#comment-tree-wrapper');
    fetch('/api/comment', {
        method: "GET"
    })
        .then(response => response.json())
        .then(data => drawCommentTree(commentWrapper, data))
}

function getCommentDeeperThenFour(thirdLevelRoot) {
    let containerForNewComments = $("#replies_for_"+thirdLevelRoot)
    fetch("/api/"+thirdLevelRoot+"/deeper-comment", {
        method: "GET"
    })
        .then(response => response.json())
        .then(data =>
            {
                drawCommentTree(containerForNewComments, data)
                hideShowMoreButton(thirdLevelRoot)
            })
}

function saveCommentRequest(parentId, comment) {
    fetch('/api/comment', {
        headers: { "Content-Type": "application/json; charset=utf-8" },
        method: 'POST',
        body: JSON.stringify({
            parentId: parentId,
            comment: comment,
        })
    }).then(response => {
        if (response.status === 200) {
            return response.json()
        }
    }).then(data => {
        addCommentInTree(data.comment)
    })
}

function deleteCommentRequest(commentId) {
    let success = false;
    fetch("/api/comment/"+commentId, {
        method: "delete"
    })
        .then(response => {
            if (response.status === 200) {
                return true
            }
        }).then(success => {
            if (success) {
                deleteCommentFromTree(commentId)
            }
    })
}

function editCommentRequest(commentId, data) {
    fetch("/api/comment/"+commentId, {
        method: "PATCH",
        body: JSON.stringify(data)
    })
        .then(response => {
            if (response.status === 200) {
                return response.json()
            }
        })
        .then(data => editCommentInTree(commentId, data.comment))
}
