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


//----------------------
// highlighting stuff
//----------------------

// Set global values before including the JS file?
const previewPaneSelector = '#editor iframe';
const dubbotApiUrl = dubbot.api_url;


// TODO: I think the following function can be extracted out, and enhanced,
// to handle the messages coming from the embeds.
// This still needs to handle other types of highlighting, like spelling
window.addEventListener("message", (event) => {
  if (event.origin.startsWith(dubbotApiUrl)) {
    if (event.data.type === 'highlight') {
      const selectors = event.data.selectors;
      let previewPane = document.querySelector(previewPaneSelector);

      // when the node is an iframe, use its contentDocument
      if(previewPane.nodeName == "IFRAME") {
        previewPane = previewPane.contentDocument;
      }

      // add the highlight CSS to the preview pane (when needed)
      addDubbotCSS(previewPane);
      previewPane.documentElement.style.setProperty("--dubbot-highlight-color", event.data.color);


      const elements = previewPane.querySelectorAll(selectors);

      elements.forEach(el => {
        el.classList.toggle('dubbot-highlight')
      });
    }
  }
});

function addDubbotCSS(previewPane) {
  if(!previewPane.querySelector('#dubbot-css')) {
    const style = document.createElement('style');
    style.id = "dubbot-css";
    style.type = 'text/css';

    const styles = `
        .dubbot-highlight {
          padding: 3px 0px 3px 3px !important;
          margin: 5px 1px 3px 0px !important;
          border: 3px solid var(--dubbot-highlight-color) !important;
          border-radius: 3px !important;
        }

        .dubbot-highlight:focus {
          box-shadow: 0 0 10px var(--dubbot-highlight-color) !important;
          outline: none !important;
        }

        mark.dubbot-highlight {
          color: inherit;
          background-color: inherit;
        }
      `;
    style.appendChild(document.createTextNode(styles));

    if (previewPane.head) {
      previewPane.head.appendChild(style);
    } else {
      previewPane.appendChild(style);
    }
  }
}
