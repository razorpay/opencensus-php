import React from 'react';
import { connect } from 'react-redux';
import { withRouter, Link } from 'react-router-dom';

import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { DocLink } from 'merchant/components/DocsLink';

import { setIsPaymentButtonCodeUsed } from '../../utils';
import track from '../Details/track';

const pluginsList = [
  {
    title: 'Wordpress Plugin',
    handleClick: track.pluginClick.bind(null, 'wordpress'),
    docLink: 'wordpress',
    icon: 'wordpress.svg',
  },
  {
    title: 'Elementor Plugin',
    handleClick: track.pluginClick.bind(null, 'elementor'),
    docLink: 'wordpress/elementor',
    icon: 'elementor.svg',
  },
];

const integrationsList = [
  {
    title: 'Go Daddy',
    handleClick: track.pluginClick.bind(null, 'godaddy'),
    docLink: '#godaddy',
    icon: 'goDaddy.svg',
  },
  {
    title: 'Weebly',
    handleClick: track.pluginClick.bind(null, 'weebly'),
    docLink: '#weebly',
    icon: 'weebly.svg',
  },
  {
    title: 'Wix',
    handleClick: track.pluginClick.bind(null, 'wix'),
    docLink: '#wix',
    icon: 'wix.svg',
  },
  {
    title: 'Google Sites',
    handleClick: track.pluginClick.bind(null, 'google_sites'),
    docLink: '#google-sites',
    icon: 'googleSites.svg',
  },
  {
    title: 'Blogger',
    handleClick: track.pluginClick.bind(null, 'blogger'),
    docLink: '#blogger',
    icon: 'blogger.svg',
  },
];
@withRouter
@connect((state) => ({
  user: state.session.user,
  mode: state.session.mode,
}))
export default class SuccessModal extends React.Component {
  onClickCopy = () => {
    setIsPaymentButtonCodeUsed({
      mid: this.props.user.current,
      mode: this.props.mode,
    });

    track.copyCode();
  };

  onClickTextArea = () => {
    if (!this.textarea) {
      return;
    }

    this.textarea.select();
    if (document.execCommand) {
      document.execCommand('copy');
    }

    track.copyCode();
  };

  onClickButtonSettings = () => {
    const { paymentButton } = this.props;

    this.props.history.push(`/paymentbuttons/${paymentButton.id}/payments`);

    this.props.updateHighlightButtonSettings(paymentButton.id);

    if (this.props.onClickButtonSettings) {
      this.props.onClickButtonSettings();
    }
  };

  setRef = (textarea) => (this.textarea = textarea);

  get codeToCopy() {
    const { paymentButton } = this.props;

    const paymentBtnCode = `<form><script src="https://checkout.razorpay.com/v1/payment-button.js" data-payment_button_id="${paymentButton.id}" async> </script> </form>`;

    return paymentBtnCode;
  }

  render() {
    return (
      <div class="PaymentButton-Create-Form PaymentButton-Create-SuccessView-V2">
        <div class="Form-container">
          <div class="Form-title">
            Button Created Successfully
            <div class="Form-description">Your payment button is ready for integration</div>
          </div>

          <div class="Form">
            <div class="Form-buttonIntegration">
              <div class="Form-buttonIntegration-title">
                <b>Integrate on your Website</b>
              </div>

              <div class="Form-buttonIntegration-body">
                {/* Integrate via copy-code */}
                <div
                  class="code-section"
                  style={{
                    backgroundImage:
                      'url("/dist/css/assets/payment_button/success-screen/code-background.svg")',
                  }}
                >
                  <div class="section-title">
                    <i class="i i-code-white m-r" /> HTML Code
                  </div>
                  <div class="test-code-section">
                    <span class="help-text">Copy & paste this HTML in your code</span>

                    <CustomClipboard value={this.codeToCopy}>
                      <Button.Primary onClick={this.onClickCopy}>
                        <i class="i i-copy m-r" />
                        <b>COPY CODE</b>
                      </Button.Primary>
                    </CustomClipboard>

                    <Input.Textarea
                      class="Input--vTop Input--codeCopy"
                      value={this.codeToCopy.trim()}
                      readOnly
                      setRef={this.setRef}
                      onClick={this.onClickTextArea}
                    />
                  </div>

                  <div class="test-code-text">
                    Test this button before integration{' '}
                    <a
                      href="https://cdn.razorpay.com/static/widget/test-payment-button.html"
                      target="_blank"
                      class="Button Button--primary--invert try-now-btn"
                      onClick={track.testButton}
                      rel="noreferrer noopener"
                    >
                      <i class="i i-play-arrow m-r" /> TEST
                    </a>
                  </div>
                </div>

                {/* Other integration methods */}
                <div class="integration-section">
                  <b>No-Code Plugins to add this button on your website directly:</b>
                  <div class="plugins">
                    {pluginsList.map((plugin, i) => (
                      <div key={`plugin-${i}`}>
                        <img
                          src={`/dist/css/assets/payment_button/success-screen/plugins/${plugin.icon}`}
                          alt={`${plugin.title} Logo`}
                        />
                        <DocLink
                          href={`https://razorpay.com/docs/payment-button/supported-platforms/${plugin.docLink}`}
                          target="_blank"
                          rel="noreferrer"
                          onClick={plugin.handleClick}
                        >
                          {plugin.title}
                        </DocLink>
                      </div>
                    ))}
                  </div>
                  <b>Integration guide for this code on other platforms:</b>
                  <div class="integrations">
                    {integrationsList.map((integration, i) => (
                      <div key={`integration-${i}`}>
                        <img
                          src={`/dist/css/assets/payment_button/success-screen/integrations/${integration.icon}`}
                          alt={`${integration.title} Logo`}
                        />
                        <DocLink
                          href={`https://razorpay.com/docs/payment-button/supported-platforms/${integration.docLink}`}
                          target="_blank"
                          rel="noreferrer"
                          onClick={integration.handleClick}
                        >
                          {integration.title}
                        </DocLink>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            <div class="Form-buttonActions">
              <div class="Form-buttonActions-title">
                <b>What should your customer see after a successful payment?</b>
              </div>

              <div class="Form-buttonActions-body">
                {/* Payment Receipt Action Modal */}
                <div class="receipt-settings">
                  <div>
                    <i class="i i-document m-r" /> <span>Automated Payment Receipt</span>
                    <Button
                      class="Button--primary--invert"
                      onClick={this.props.openPageReceiptModal}
                    >
                      <b>CONFIGURE</b>
                    </Button>
                  </div>
                </div>

                {/* Redirect Url Action Modal */}
                <div class="postPayment-settings">
                  <div>
                    <i class="i i-checked-document m-r" />{' '}
                    <span>Custom Message and Redirect to a URL</span>
                    <Button class="Button--primary--invert" onClick={this.props.openSettingsModal}>
                      <b>CONFIGURE</b>
                    </Button>
                  </div>
                </div>
              </div>

              <div class="help-text help-text-settings">
                <i class="i i-info-outline m-r" /> These settings may be configured later from
                dashboard
              </div>
            </div>

            <div class="Form-controls">
              <Link to="/paymentbuttons" class="Button Button--primary">
                Back To Dashboard
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
