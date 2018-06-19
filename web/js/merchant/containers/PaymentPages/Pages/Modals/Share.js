import ModalHeader from 'rzp/ui/ModalHeader';
import Button, { AsyncBtn } from 'component/Button';
import Form from 'component/Form';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import Input from 'component/Input';

import { isEmail, isPhone } from 'rzp/utils/validators';

const fbBase = 'https://www.facebook.com/sharer/sharer.php?u=';
const twitterBase = 'https://twitter.com/share?url=';
const whatsappBase = 'whatsapp://send?text=';

export default ({
  isNew,
  handleClose,
  handleAction,
  showNotification,
  url,
  title,
  description,
}) => {
  function onSubmit(formData) {
    const reqPayload = {};
    const msg = [];

    formData.contact && msg.push('Mobile');
    formData.email && msg.push('Email');

    if (!msg.length) {
      showNotification({
        type: 'error',
        message: 'Please enter Mobile or Email to send link',
      });

      return;
    }

    return handleAction(formData)
      .then(resp => {
        if (resp.data) {
          showNotification({
            type: 'success',
            message: 'Link is successfully sent via ' + msg.join(' and '),
          });

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
          err = `Some Network error occured`;
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

    return false;
  }

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
              Link created successfully
            </span>
          ) : (
            'Share Link'
          )
        }
        onCloseClick={handleClose}
      />

      <div class="modal-body" style={{ paddingTop: 0 }}>
        <div class="ModalForm ModalForm--Share">
          {isNew && (
            <div class="Share-section">
              <span class="label--faded">
                Use the following Link to accept payments.
              </span>
              <div>
                <CustomClipboard
                  value={url}
                  onCopy={() => {
                    const ele = document.getElementsByName('short_url');
                    ele[0] && ele[0].focus();
                  }}
                >
                  <Input
                    name="short_url"
                    value={url}
                    readOnly={true}
                    class="Input--inline is-focused"
                  />
                  <Button.Primary class="Button--input--right">
                    Copy Link
                  </Button.Primary>
                </CustomClipboard>
              </div>
            </div>
          )}

          <div class="Share-section">
            <span class="label--faded">Share link on social media. </span>
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
            <div
              style={{ textAlign: 'center', marginTop: 16, overflow: 'auto' }}
            >
              <span class="label--faded" style={{ float: 'left' }}>
                Share via SMS or email
              </span>
              <Button.Transparent
                style={{ float: 'right' }}
                type="submit"
                class="Button--Link"
              >
                <b>
                  Send Link
                  <i class="i i-arrow-forward" />
                </b>
              </Button.Transparent>
            </div>
          </Form>
        </div>
      </div>
    </div>
  );
};

function _shareMessage(title, description) {
  let msg = title;
  if (description) {
    msg += ': ' + description;
  }

  if (msg.length > 200) {
    msg = msg.substring(0, 200) + '...';
  }

  return msg;
}
