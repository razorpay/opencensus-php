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
  if (navigator?.clipboard) {
    navigator.clipboard.writeText?.(url).catch(() => {
      copyFallback(url);
    });
  } else {
    copyFallback(url);
  }
};

export default copyToClipboard;
