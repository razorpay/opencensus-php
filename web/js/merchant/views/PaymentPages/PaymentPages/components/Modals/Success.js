import { Link } from 'react-router-dom';
import ModalHeader from 'common/ui/ModalHeader';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import CustomClipboard from 'common/ui/Clipboard/Custom';

import Popover, { PopoverBody } from 'common/ui/Popover';
import CreateEmbedButton from './CreateEmbedButton';
import PreviewEmbedButton from './CreateEmbedButton/PreviewEmbedButton';

import { isEmail, isPhone } from 'common/utils/validators';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';

import SocialShareOptions from './Share/SocialShareOptions';
import Collapsible from 'merchant/components/Collapsible';
import ProductCard from 'merchant/components/ProductCard/ProductCard';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export default class extends React.PureComponent {
  state = {};

  onSubmit = (formData) => {
    const reqPayload = {};
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

    return this.props
      .handleSendLink(formData)
      .then((resp) => {
        if (resp.data) {
          this.props.showNotification({
            type: 'success',
            message: 'URL is successfully sent via ' + msg.join(' and '),
          });

          this.props.trackerFn('Send', getKeysSeparatedByPipe(formData));
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
    analyticsTrack({
      objectName: 'get hyperlink',
      actionName: 'button',
      screen: 'create payment page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.props.trackClickOnCreateEmbedButton && this.props.trackClickOnCreateEmbedButton('new');

    this.props.openModal({
      size: 'small',
      component: <CreateEmbedButton id={this.props.id} />,
    });
  };

  render() {
    const { isEditExistingId, handleClose, url, openSettingsModal, closeModal, user } = this.props;

    return (
      <div class="Modal--PaymentpagesSuccess">
        <ModalHeader
          title={
            <>
              <div class="gradient-header">
                <div class="pattern-1" />
                <div class="pattern-2" />
              </div>
              <div class="text-center">
                {isEditExistingId ? 'Page updated successfully' : 'Page created successfully'}
              </div>
            </>
          }
          onCloseClick={() => {
            this.props.trackerFn('Close');
            return handleClose();
          }}
        />

        <div class="modal-body" style={{ paddingTop: 0 }}>
          <div class="share-section">
            {/* Clipboard and slug section */}
            <div>
              <CustomClipboard
                value={url}
                onCopy={() => {
                  analyticsTrack({
                    objectName: 'copy hyperlink',
                    actionName: 'button',
                    screen: 'create payment page',
                    properties: {
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                  const ele = document.getElementsByName('short_url');
                  ele[0] && ele[0].focus();
                  this.props.trackerFn('Click Copy URL');
                }}
              >
                <div class="Input-copierText">
                  <Input
                    name="short_url"
                    value={url}
                    readOnly={true}
                    class="Input--inline is-focused"
                  />
                </div>
                <Button.Primary>Copy URL</Button.Primary>
              </CustomClipboard>
              <Button onClick={openSettingsModal}>Customise URL</Button>
            </div>

            <div class="social-section">
              {/* Social share options */}
              <SocialShareOptions
                msgInPost={this.props.title}
                linkInPost={this.props.url}
                trackerFn={this.props.trackerFn}
              />

              {/* Collapsible phone and email fields */}
              <Collapsible
                title={() => <span className="text-primary">Share via SMS, Email</span>}
                childrenPosition="bottom"
                class="CollapsibleFields"
              >
                <Form onSubmit={this.onSubmit}>
                  <Input
                    name="contact"
                    type="tel"
                    placeholder="Mobile"
                    addonBefore={<i class="i i-phone" />}
                    validator={(val) => {
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
                    validator={(val) => {
                      if (!isEmail(val)) {
                        return 'Invalid email';
                      }
                    }}
                  />
                  <div
                    style={{
                      textAlign: 'center',
                      marginBottom: 8,
                      overflow: 'auto',
                    }}
                  >
                    <Button.Transparent
                      style={{ float: 'right' }}
                      type="submit"
                      class="Button--Link"
                    >
                      <b>
                        Send Link <i class="i i-arrow-forward" />
                      </b>
                    </Button.Transparent>
                  </div>
                </Form>
              </Collapsible>
            </div>
          </div>

          <ProductCard
            imgSrc="https://cdn.razorpay.com/static/assets/notifs/payment-button.svg"
            title="Introducing Subscription Buttons"
            description="Start accepting subscriptions from your consumers, right from your website or blog!"
            primaryLink={
              user.isAllowedEdit('subscription_buttons') && '/app/subscription_buttons/new'
            }
            secondaryLink="https://razorpay.com/docs/payment-button/subscription-buttons/"
            source="payment-pages"
            trackerFn={window.rzpQ.subscriptionButtons}
          />

          {/* Footer */}
          <footer>
            <Link class="Button" to="/paymentpages" onClick={closeModal}>
              Back to Dashboard
            </Link>

            <Button.Primary onClick={this.openEmbedButtonView} style={{ float: 'right' }}>
              Get Hyperlink Button
            </Button.Primary>
          </footer>
        </div>
      </div>
    );
  }
}
