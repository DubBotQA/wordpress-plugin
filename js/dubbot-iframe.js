//----------------------
// WP Admin/modal stuff
//----------------------

jQuery(document).ready(function($) {

  // init dubbot js
  const dubbotHighlight = new DubbotHighlight(dubbot.editor_selector, dubbot.api_url);
  dubbotHighlight.init();

  const dialogOptions = function(el) {
    return {
      minWidth: 500,
      height: 600,
      resizable: true,
      position: { my: "center top", at: "right bottom", of: el },
      close: (event, ui) => { dubbotHighlight.removeHighlighting(); }
    }
  };

  // helper function to wait for this element because I can't figure out if there's a hook in wordpress to use to add this button to the top menu by the save button on the editor
  function waitForElementVisible(selector, callback) {
    var intervalId = setInterval(function() {
      if ($(selector).is(':visible')) {
        clearInterval(intervalId);
        callback();
      }
    }, 100);
  }

  // Add button to the top menu
  waitForElementVisible('#wp-admin-bar-root-default', function() {
    $('#wp-admin-bar-root-default').append(`<li role="group" id="wp-admin-bar-dubbot"><a class="ab-item" role="menuitem" style="cursor: pointer">DubBot (${ dubbot.metadata.total_issues_count })</a></li>`);
    $('#wp-admin-bar-dubbot a').on('click', function() { $('#dubbot-modal').dialog(dialogOptions('#wp-admin-bar-dubbot')) });
  })

  // Add button to the editor by the Save button
  waitForElementVisible('.editor-header__settings', function() {
    $('.editor-header__settings').prepend(`<a id="editor-header-settings-dubbot" class="components-button is-compact is-secondary">DubBot (${ dubbot.metadata.total_issues_count })</a>`);
    $('#editor-header-settings-dubbot').on('click', function() { $('#dubbot-modal').dialog(dialogOptions('#editor-header-settings-dubbot')) });
  });

  // Create the modal HTML
  $('body').append(`
        <div id="dubbot-modal" style="display: none;" title="DubBot Page Results">
            <div id="dubbot-modal-content">
                <iframe id="dubbot-preview" src="${ dubbot.iframeURL }" width="100%" height="100%" frameborder="0"></iframe>
            </div>
        </div>
    `);


  // Close the modal
  $('#dubbot-modal').on('click', function() {
    $('#dubbot-modal').hide();
  });
});
