import track from '../../../Wysiwyg/track';

const fbBase = 'https://www.facebook.com/sharer/sharer.php?u=';
const twitterBase = 'https://twitter.com/share?url=';
const whatsappBase = 'https://api.whatsapp.com/send?text='; // Shares on browser client / launches app on OSX / windows

function _shareMessage(title, description = '') {
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

const SocialShareOptions = ({ msgInPost, linkInPost, trackerFn = () => {} }) => {
  function mediaWindowUrl(e) {
    const type = e.target.dataset.type;
    let mediaUrl;

    const mediaMsg = _shareMessage(msgInPost);

    switch (type) {
      case 'fb':
        mediaUrl = `${fbBase + linkInPost}&quote=${mediaMsg}`;

        window.open(mediaUrl, 'facebook-share', 'width=550,height=235');

        trackerFn('facebook');
        break;

      case 'twitter':
        mediaUrl = `${twitterBase + linkInPost}&text=${mediaMsg}`;

        window.open(mediaUrl, 'twitter-share', 'width=550,height=235');

        trackerFn('twitter');
        break;

      case 'whatsapp':
        mediaUrl = `${whatsappBase + mediaMsg} ${linkInPost}`;

        window.open(mediaUrl);

        trackerFn('whatsapp');
        break;

      default:
    }

    track.success.clickShareUrlViaSocialMedia(type);

    return false;
  }

  return (
    <div className="social-media" style={{ display: 'inline-block' }}>
      <a onClick={mediaWindowUrl} data-type="fb">
        <img src="/img/social-media/fb.png" alt="Facebook share" />
      </a>
      <a onClick={mediaWindowUrl} data-type="twitter">
        <img src="/img/social-media/twitter.png" alt="Twitter share" />
      </a>
      <a onClick={mediaWindowUrl} data-type="whatsapp">
        <img src="/img/social-media/whatsapp.png" alt="Whatsapp share" />
      </a>
    </div>
  );
};

export default SocialShareOptions;
