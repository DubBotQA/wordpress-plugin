//----------------------
// WP Admin/modal stuff
//----------------------
jQuery(document).ready(function($) {
  // Add button to the editor
  $('#wp-admin-bar-root-default').append(`<li role="group" id="wp-admin-bar-dubbot"><a class="ab-item" role="menuitem" style="cursor: pointer">DubBot (${ dubbot.metadata.total_issues_count })</a></li>`);


  // Create the modal HTML
  $('body').append(`
        <div id="dubbot-modal" style="display: none;" title="DubBot Page Results">
            <div id="dubbot-modal-content">
                <iframe id="dubbot-preview" src="" width="100%" height="100%" frameborder="0"></iframe>
            </div>
        </div>
    `);

  // init dubbot js
  const dubbotHighlight = new DubbotHighlight(dubbot.editor_selector, dubbot.api_url);
  dubbotHighlight.init();

  // Show modal with iframe
  $('#wp-admin-bar-dubbot a').on('click', function() {
    if (!$('#dubbot-preview').attr('src')) {
      // dubbot.iframeURL is passed in from PHP with wp_localize_script
      // the 'dubbot' object has whatever we're passing in from the PHP
      $('#dubbot-preview').attr('src', dubbot.iframeURL);
    }
    $('#dubbot-modal').dialog( {
      minWidth: 500,
      height: 600,
      resizable: true,
      position: { my: "center top", at: "right bottom", of: "#wp-admin-bar-dubbot" }, // place it right under the toolbar toggle button
      close: (event, ui) => { dubbotHighlight.removeHighlighting(); }
    });
  });

  // Close the modal
  $('#dubbot-modal').on('click', function() {
    $('#dubbot-modal').hide();
  });
});
