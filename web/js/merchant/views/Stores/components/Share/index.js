import React from 'react';

import ModalHeader from 'common/ui/ModalHeader';
import Button from 'common/new-ui/Button';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';

import { isEmail, isPhone } from 'common/utils/validators';

import SocialShareOptions from './SocialShareOptions';

import { sendLink } from '../../model';

export default class extends React.PureComponent {
  state = {};

  onSubmit = (formData) => {
    const msg = [];

    if (formData.contact) msg.push('Mobile');
    if (formData.email) msg.push('Email');

    if (!msg.length) {
      this.props.showNotification({
        type: 'error',
        message: 'Please enter Mobile or Email to send URL',
      });

      return;
    }

    sendLink(this.props.id, formData)
      .then((resp) => {
        if (resp.data) {
          this.props.showNotification({
            type: 'success',
            message: `URL is successfully sent via ${msg.join(' and ')}`,
          });

          this.props.closeModal();
        }
      })
      .catch(({ errors }) => {
        let err = errors;

        if (Array.isArray(err)) {
          err = [];

          if (errors.length) {
            errors.forEach((e) => {
              if (e && e.toLowerCase().indexOf('status code') === -1) {
                err.push(e);
              }
            });
          }

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

  render() {
    const { closeModal } = this.props;

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
          title="Share Link"
          onCloseClick={() => {
            return closeModal();
          }}
        />

        <div className="modal-body" style={{ paddingTop: 0 }}>
          <div className="ModalForm ModalForm--Share">
            <div className="Share-section">
              <span className="label--faded">
                <i className="i i-share-circle" /> Share{' '}
              </span>
              <SocialShareOptions msgInPost={this.props.title} linkInPost={this.props.url} />
            </div>

            <Form className="Share-section" onSubmit={this.onSubmit}>
              <div
                style={{
                  textAlign: 'center',
                  marginBottom: 8,
                  overflow: 'auto',
                }}
              >
                {askToShare}
              </div>
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
              <Button
                className="Button Button--primary"
                style={{ marginTop: 20, width: '100%', textAlign: 'center' }}
                onClick={closeModal}
              >
                Back to Dashboard
              </Button>
            </Form>
          </div>
        </div>
      </div>
    );
  }
}
