//----------------------
// WP Admin/modal stuff
//----------------------
jQuery(document).ready(function($) {
  // Add button to the editor
  $('#wp-admin-bar-root-default').append(`<li role="group" id="wp-admin-bar-dubbot"><a class="ab-item" role="menuitem" style="cursor: pointer">DubBot (${ dubbot.metadata.total_issues_count })</a></li>`);


  // Create the modal HTML
  $('body').append(`
        <div id="dubbot-modal" style="display: none;">
            <div id="dubbot-modal-content">
                <iframe id="dubbot-preview" src="" width="500" height="100%" frameborder="0"></iframe>
            </div>
        </div>
    `);

  // init dubbot js
  new DubbotHighlight("#editor iframe", dubbot.api_url).init();

  // Show modal with iframe
  $('#wp-admin-bar-dubbot a').on('click', function() {
    if (!$('#dubbot-preview').attr('src')) {
      // dubbot.iframeURL is passed in from PHP with wp_localize_script
      // the 'dubbot' object has whatever we're passing in from the PHP
      $('#dubbot-preview').attr('src', dubbot.iframeURL);
    }
    $('#dubbot-modal').show();
  });

  // Close the modal
  $('#dubbot-modal').on('click', function() {
    $('#dubbot-modal').hide();
  });
});
