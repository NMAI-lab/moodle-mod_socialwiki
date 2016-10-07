// Wikipedia JavaScript support functions
// If this is true, the toolbar will no longer overwrite the infobox when you move the mouse over individual items.
var noOverwrite = false;
var alertText;
var clientPC = navigator.userAgent.toLowerCase(); // Get client info.
var is_gecko = ((clientPC.indexOf('gecko') != -1) && (clientPC.indexOf('spoofer') == -1)
        && (clientPC.indexOf('khtml') == -1) && (clientPC.indexOf('netscape/7.0') == -1));
var is_safari = ((clientPC.indexOf('AppleWebKit') != -1) && (clientPC.indexOf('spoofer') == -1));

$('.socialwiki_wikicontent textarea').addClass('socialwikieditor-content');

var $styleheads = $('#styleheads ul');
var $toolbar = $('.socialwiki_wikicontent .socialwikieditor-toolbar').show();

$('#styleprops').on('click', function(e) {
    e.preventDefault();
    $styleheads.toggle();
});
$toolbar.find('#styleheads a').on('click', function() {
    $styleheads.hide();
    var el = $(this);
    insertTags(el.attr('start_tag'), el.attr('end_tag'), el.text());
});
$toolbar.find('button:not(#styleprops)').on('click', function(e) {
    e.preventDefault();
    var el = $(this);
    insertTags(el.attr('start_tag'), el.attr('end_tag'), el.attr('sample') == 1 ? el.attr('title') : '')
});

/**
 * Apply tagOpen/tagClose to selection in textarea,
 * use sampleText instead of selection if there is none copied and adapted from phpBB
 * @param tagOpen
 * @param tagClose
 * @param sampleText
 */
function insertTags(tagOpen, tagClose, sampleText) {

    tagOpen = decodeURIComponent(tagOpen);
    tagClose = decodeURIComponent(tagClose);

    var txtarea = document.forms['mform1'].newcontent;

    // IE.
    if (document.selection && !is_gecko) {
        var theSelection = document.selection.createRange().text;
        if (!theSelection) {
            theSelection = sampleText;
        }
        txtarea.focus();
        if (theSelection.charAt(theSelection.length - 1) == " ") { // Exclude ending space char, if any.
            theSelection = theSelection.substring(0, theSelection.length - 1);
            document.selection.createRange().text = tagOpen + theSelection + tagClose + " ";
        } else {
            document.selection.createRange().text = tagOpen + theSelection + tagClose;
        }

        // Mozilla.
    } else if (txtarea.selectionStart || txtarea.selectionStart == '0') {
        var startPos = txtarea.selectionStart;
        var endPos = txtarea.selectionEnd;
        var scrollTop = txtarea.scrollTop;
        var myText = (txtarea.value).substring(startPos, endPos);
        if (!myText) {
            myText = sampleText;
        }
        var subst = '';
        if (myText.charAt(myText.length - 1) == " ") { // Exclude ending space char, if any.
            subst = tagOpen + myText.substring(0, (myText.length - 1)) + tagClose + " ";
        } else {
            subst = tagOpen + myText + tagClose;
        }
        txtarea.value = txtarea.value.substring(0, startPos) + subst + txtarea.value.substring(endPos, txtarea.value.length);
        txtarea.focus();

        var cPos = startPos + (tagOpen.length + myText.length + tagClose.length);
        txtarea.selectionStart = cPos;
        txtarea.selectionEnd = cPos;
        txtarea.scrollTop = scrollTop;

        // All others.
    } else {
        var copy_alertText = alertText;
        var re1 = new RegExp("\\$1", "g");
        var re2 = new RegExp("\\$2", "g");
        copy_alertText = copy_alertText.replace(re1, sampleText);
        copy_alertText = copy_alertText.replace(re2, tagOpen + sampleText + tagClose);
        var text;
        if (sampleText) {
            text = prompt(copy_alertText);
        } else {
            text = "";
        }
        if (!text) {
            text = sampleText;
        }
        text = tagOpen + text + tagClose;
        document.infoform.infobox.value = text;
        // In Safari this causes scrolling.
        if (!is_safari) {
            txtarea.focus();
        }
        noOverwrite = true;
    }
    // Reposition cursor if possible.
    if (txtarea.createTextRange) {
        txtarea.caretPos = document.selection.createRange().duplicate();
    }
}
