// Todo: delete this file, it's available in @dashboard/shared-utils
/* TODO : Create a common share util in which first sharing panel
  from navigator share is used & if not present then copyToClipboard 
  is kept as a fallback.
*/

const copyFallback = (url) => {
  // fallback for android webview where navigator clipboard.write needs write permission.
  const textarea = document.createElement('textarea');
  textarea.textContent = url;
  textarea.style.position = 'fixed'; // Prevent scrolling to bottom of page in Microsoft Edge.
  document.body.appendChild(textarea);
  textarea.select();
  document.execCommand('copy');
  document.body.removeChild(textarea);
};

const copyToClipboard = (url) => {
  // Use in future, if needed https://developer.mozilla.org/en-US/docs/Web/API/Permissions
  if (navigator?.clipboard?.writeText && window.isSecureContext) {
    try {
      navigator.clipboard.writeText(url).catch((err) => {
        if (err) {
          copyFallback(url);
        }
      });
    } catch (error) {
      copyFallback(url);
    }
  } else {
    copyFallback(url);
  }
};

export default copyToClipboard;
