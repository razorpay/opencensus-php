import React from 'react';

import { Link } from 'react-router-dom';
import ModalHeader from 'common/ui/ModalHeader';
import Button from 'common/new-ui/Button';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import CustomClipboard from 'common/ui/Clipboard/Custom';

import Popover, { PopoverBody } from 'common/ui/Popover';
import CreateEmbedButton from 'merchant/views/PaymentPages/PaymentPages/components/Modals/CreateEmbedButton';

import { isEmail, isPhone } from 'common/utils/validators';

import SocialShareOptions from './SocialShareOptions';

export default class extends React.PureComponent {
  state = {};

  static defaultProps = {
    trackerFn: () => {},
  };

  onSubmit = (formData) => {
    const msg = [];

    formData.contact && msg.push('Mobile');
    formData.email && msg.push('Email');

    if (!msg.length) {
      this.props.showNotification({
        type: 'error',
        message: 'Please enter Mobile or Email to send URL',
      });

      return;
    }

    this.props
      .handleAction(formData)
      .then((resp) => {
        if (resp.data) {
          this.props.showNotification({
            type: 'success',
            message: `URL is successfully sent via ${msg.join(' and ')}`,
          });

          this.props.trackerFn('send', formData);
          this.props.handleClose();
        }
      })
      .catch(({ errors }) => {
        let err = errors;

        if (Array.isArray(err)) {
          err = [];

          errors.length &&
            errors.forEach((e) => {
              if (e && e.toLowerCase().indexOf('status code') === -1) {
                err.push(e);
              }
            });

          err = err.length ? err : null;
        }

        if (!err) {
          err = `Some network error has occured`;
        }

        this.props.showNotification({
          type: 'error',
          message: err,
        });
      });
  };

  openEmbedButtonView = () => {
    this.props.trackClickOnCreateEmbedButton && this.props.trackClickOnCreateEmbedButton();

    this.props.openModal({
      size: 'small',
      component: <CreateEmbedButton id={this.props.id} />,
    });
  };

  render() {
    const { isNew, isPaymentPagesV2, isEditExistingId, handleClose, url, AddonAction, closeModal } =
      this.props;

    const askToShare = (
      <React.Fragment>
        <span className="label--faded" style={{ float: 'left' }}>
          Share via SMS or email
        </span>
        <Button.Transparent style={{ float: 'right' }} type="submit" className="Button--Link">
          <b>
            Send
            <i className="i i-arrow-forward" />
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
                  className="i i-done text-success"
                  style={{
                    fontSize: 16,
                    verticalAlign: 'middle',
                    marginRight: 8,
                  }}
                />
                {isPaymentPagesV2
                  ? isEditExistingId
                    ? 'Page updated successfully'
                    : 'Page created successfully'
                  : 'Link created successfully'}
              </span>
            ) : (
              'Share Link'
            )
          }
          onCloseClick={() => {
            this.props.trackerFn('close');
            return handleClose();
          }}
        />

        <div className="modal-body" style={{ paddingTop: 0 }}>
          <div className="ModalForm ModalForm--Share">
            {isNew && (
              <div className="Share-section">
                {!isPaymentPagesV2 && (
                  <div className="label--faded m-b">Use the following url to accept payments.</div>
                )}
                <div>
                  <CustomClipboard
                    value={url}
                    onCopy={() => {
                      const ele = document.getElementsByName('short_url');
                      ele[0] && ele[0].focus();
                      this.props.trackerFn('click_copy_url');
                    }}
                  >
                    <Input
                      name="short_url"
                      value={url}
                      readOnly={true}
                      className="Input--inline is-focused"
                    />
                    <Button.Primary className="Button--input--right">Copy URL</Button.Primary>
                  </CustomClipboard>
                </div>
                {AddonAction}
              </div>
            )}

            {isPaymentPagesV2 && isNew && (
              <div className="Share-section">
                <span className="label--faded">
                  <i className="i i-embed-btn" />
                  Embed Hyperlink Button
                </span>
                <div style={{ display: 'inline-block' }}>
                  <span className="help-content">
                    <i className="i i-info-outline" style={{ marginLeft: 4 }} />
                    <Popover align="top" theme="dark" parentQuerySelector=".ReactModal__Content">
                      <PopoverBody>
                        Your customers can pay from your website by clicking on this Hyperlink
                        Button
                      </PopoverBody>
                    </Popover>
                  </span>
                </div>
                <Button.Transparent
                  type="button"
                  className="Button--Link"
                  onClick={this.openEmbedButtonView}
                  style={{ float: 'right' }}
                >
                  <b>Create</b>
                </Button.Transparent>
              </div>
            )}

            <div className="Share-section">
              <span className="label--faded">
                <i className="i i-share-circle" /> Share{' '}
              </span>
              <SocialShareOptions
                msgInPost={this.props.title}
                linkInPost={this.props.url}
                trackerFn={this.props.trackerFn}
              />
            </div>

            <Form className="Share-section" onSubmit={this.onSubmit}>
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
                addonBefore={<i className="i i-phone" />}
                validator={(val) => {
                  if (!isPhone(val)) {
                    return 'Invalid phone';
                  }
                  return '';
                }}
              />

              <Input
                name="email"
                type="email"
                placeholder="Email"
                addonBefore={<i className="i i-email" />}
                validator={(val) => {
                  if (!isEmail(val)) {
                    return 'Invalid email';
                  }
                  return '';
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
                  className="Button Button--primary"
                  to="/paymentpages"
                  style={{ marginTop: 20, width: '100%', textAlign: 'center' }}
                  onClick={closeModal}
                >
                  Back to Dashboard
                </Link>
              )}
            </Form>
          </div>
        </div>
      </div>
    );
  }
}
