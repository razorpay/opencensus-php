import { Link } from 'react-router-dom';
import ModalHeader from 'rzp/ui/ModalHeader';
import Button, { AsyncBtn } from 'component/Button';
import Form from 'component/Form';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import Input from 'component/Input';

import { isEmail, isPhone } from 'rzp/utils/validators';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';

import { isMobileAndTablet } from 'common/util';

const fbBase = 'https://www.facebook.com/sharer/sharer.php?u=';
const twitterBase = 'https://twitter.com/share?url=';
let whatsappBase;

if (isMobileAndTablet()) {
  whatsappBase = 'whatsapp://send?text=';
} else {
  whatsappBase = 'https://web.whatsapp.com//send?text=';
}

export default ({
  isNew,
  isPaymentPagesV2,
  handleClose,
  handleAction,
  showNotification,
  url,
  title,
  description,
  ...props
}) => {
  function onSubmit(formData) {
    const reqPayload = {};
    const msg = [];

    formData.contact && msg.push('Mobile');
    formData.email && msg.push('Email');

    if (!msg.length) {
      showNotification({
        type: 'error',
        message: 'Please enter Mobile or Email to send URL',
      });

      return;
    }

    return handleAction(formData)
      .then(resp => {
        if (resp.data) {
          showNotification({
            type: 'success',
            message: 'URL is successfully sent via ' + msg.join(' and '),
          });

          props.trackerFn('Send', getKeysSeparatedByPipe(formData));

          handleClose();
        }
      })
      .catch(({ errors }) => {
        let err = errors;

        if (Array.isArray(err)) {
          err = [];

          errors.length &&
            errors.forEach(e => {
              if (e && e.toLowerCase().indexOf('status code') === -1) {
                err.push(e);
              }
            });

          err = err.length ? err : null;
        }

        if (!err) {
          err = `Some network error has occured`;
        }

        showNotification({
          type: 'error',
          message: err,
        });
      });
  }

  function mediaWindowUrl(e) {
    const type = e.target.dataset['type'];
    let mediaUrl;
    const mediaMsg = _shareMessage(title, description);

    switch (type) {
      case 'fb':
        mediaUrl = fbBase + url + '&quote=' + mediaMsg;

        window.open(mediaUrl, 'facebook-share', 'width=550,height=235');
        break;

      case 'twitter':
        mediaUrl = twitterBase + url + '&text=' + mediaMsg;

        window.open(mediaUrl, 'twitter-share', 'width=550,height=235');
        break;

      case 'whatsapp':
        mediaUrl = whatsappBase + mediaMsg + ' ' + url;

        window.open(mediaUrl);
        break;
    }

    props.trackerFn('Click Social Media', type);

    return false;
  }

  const askToShare = (
    <React.Fragment>
      <span class="label--faded" style={{ float: 'left' }}>
        Share via SMS or email
      </span>
      <Button.Transparent
        style={{ float: 'right' }}
        type="submit"
        class="Button--Link"
      >
        <b>
          Send
          <i class="i i-arrow-forward" />
        </b>
      </Button.Transparent>
    </React.Fragment>
  );

  return (
    <div>
      <ModalHeader
        title={
          isNew ? (
            <span>
              <i
                class="i i-done text-success"
                style={{
                  fontSize: 16,
                  verticalAlign: 'middle',
                  marginRight: 8,
                }}
              />
              {isPaymentPagesV2
                ? 'Page created successfully'
                : 'Link created successfully'}
            </span>
          ) : (
            'Share Link'
          )
        }
        onCloseClick={() => {
          props.trackerFn('Close');
          return handleClose();
        }}
      />

      <div class="modal-body" style={{ paddingTop: 0 }}>
        <div class="ModalForm ModalForm--Share">
          {isNew && (
            <div class="Share-section">
              {!isPaymentPagesV2 && (
                <div class="label--faded m-b">
                  Use the following url to accept payments.
                </div>
              )}
              <div>
                <CustomClipboard
                  value={url}
                  onCopy={() => {
                    const ele = document.getElementsByName('short_url');
                    ele[0] && ele[0].focus();
                    props.trackerFn('Click Copy URL');
                  }}
                >
                  <Input
                    name="short_url"
                    value={url}
                    readOnly={true}
                    class="Input--inline is-focused"
                  />
                  <Button.Primary class="Button--input--right">
                    Copy URL
                  </Button.Primary>
                </CustomClipboard>
              </div>
              {isPaymentPagesV2 && (
                <div class="label--faded m-t">
                  You can customize this url from <b>Page Settings</b>
                </div>
              )}
            </div>
          )}

          <div class="Share-section">
            <span class="label--faded">Share on social media. </span>
            <div class="social-media" style={{ display: 'inline-block' }}>
              <a onClick={mediaWindowUrl} data-type="fb">
                <img src="/img/social-media/fb.png" alt="Facebook share" />
              </a>
              <a onClick={mediaWindowUrl} data-type="twitter">
                <img src="/img/social-media/twitter.png" alt="Twitter share" />
              </a>
              <a onClick={mediaWindowUrl} data-type="whatsapp">
                <img
                  src="/img/social-media/whatsapp.png"
                  alt="Whatsapp share"
                />
              </a>
            </div>
          </div>

          <Form class="Share-section" onSubmit={onSubmit}>
            {isPaymentPagesV2 && (
              <div
                style={{
                  textAlign: 'center',
                  marginBottom: 8,
                  overflow: 'auto',
                }}
              >
                {askToShare}
              </div>
            )}
            <Input
              name="contact"
              type="tel"
              placeholder="Mobile"
              addonBefore={<i class="i i-phone" />}
              validator={val => {
                if (!isPhone(val)) {
                  return 'Invalid phone';
                }
              }}
            />

            <Input
              name="email"
              type="email"
              placeholder="Email"
              addonBefore={<i class="i i-email" />}
              validator={val => {
                if (!isEmail(val)) {
                  return 'Invalid email';
                }
              }}
            />
            {!isPaymentPagesV2 && (
              <div
                style={{
                  textAlign: 'center',
                  marginBottom: 16,
                  overflow: 'auto',
                }}
              >
                {askToShare}
              </div>
            )}
            {isPaymentPagesV2 && (
              <Link
                class="Button Button--primary"
                to="/paymentpages"
                style={{ marginTop: 20, width: '100%', textAlign: 'center' }}
              >
                Back to Dashboard
              </Link>
            )}
          </Form>
        </div>
      </div>
    </div>
  );
};

function _shareMessage(title, description) {
  let msg = `"${title}"`;
  if (description) {
    msg += ': ' + description;
  }

  if (msg.length > 200) {
    msg = msg.substring(0, 200) + '...';
  }

  return msg;
}
