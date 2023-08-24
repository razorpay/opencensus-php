const fbBase = 'https://www.facebook.com/sharer/sharer.php?u=';
const twitterBase = 'https://twitter.com/share?url=';
const whatsappBase = 'https://api.whatsapp.com/send?text=';

export const mediaWindowUrl = ({ type, title, url, description }): boolean => {
  let mediaUrl;

  const mediaMsg = _shareMessage(title, description);

  switch (type) {
    case 'fb':
      mediaUrl = `${fbBase + url}&quote=${mediaMsg}`;

      window.open(mediaUrl, 'facebook-share', 'width=550,height=235');
      break;

    case 'twitter':
      mediaUrl = `${twitterBase + url}&text=${mediaMsg}`;

      window.open(mediaUrl, 'twitter-share', 'width=550,height=235');
      break;

    case 'whatsapp':
    default:
      mediaUrl = `${whatsappBase + mediaMsg} ${url}`;

      window.open(mediaUrl);
      break;
  }

  return false;
};

function _shareMessage(title, description): string {
  let msg = `"${title}"`;
  if (description) {
    msg += `: ${description}`;
  }

  if (msg.length > 200) {
    msg = `${msg.substring(0, 200)}...`;
  }

  msg = window.encodeURIComponent(msg);

  return msg;
}
