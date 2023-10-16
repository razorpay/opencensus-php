import React from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';

import { withI18Service } from 'common/i18';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { DocLink } from 'merchant/components/DocsLink';

import { setIsPaymentButtonCodeUsed } from 'merchant/views/PaymentButton/utils';
import track from 'merchant/views/PaymentButton/PaymentButton/Details/track/index';

import WordpressImage from 'assets/payment_button/success-screen/plugins/wordpress.svg';
import ElementorImage from 'assets/payment_button/success-screen/plugins/elementor.svg';
import SiteOriginImage from 'assets/payment_button/success-screen/plugins/siteorigin.jpeg';
import VisualComposerImage from 'assets/payment_button/success-screen/plugins/visual-composer.jpeg';
import DrupalImage from 'assets/payment_button/success-screen/plugins/drupal.svg';
import GoDaddyImage from 'assets/payment_button/success-screen/integrations/goDaddy.svg';
import WeeblyImage from 'assets/payment_button/success-screen/integrations/weebly.svg';
import WixImage from 'assets/payment_button/success-screen/integrations/wix.svg';
import GoogleSitesImage from 'assets/payment_button/success-screen/integrations/googleSites.svg';
import BloggerImage from 'assets/payment_button/success-screen/integrations/blogger.svg';
import ShowWhen from 'merchant/components/ShowWhen';

const pluginsList = [
  {
    title: 'Wordpress Plugin',
    handleClick: track.pluginClick.bind(null, 'wordpress'),
    docLink: 'https://razorpay.com/docs/payments/payment-button/supported-platforms/wordpress/',
    docLink2: 'https://wordpress.org/plugins/razorpay-payment-button/',
    icon: WordpressImage,
  },
  {
    title: 'Elementor Plugin',
    handleClick: track.pluginClick.bind(null, 'elementor'),
    docLink:
      'https://razorpay.com/docs/payments/payment-button/supported-platforms/wordpress/elementor/',
    docLink2: 'https://wordpress.org/plugins/razorpay-payment-button-elementor/',
    icon: ElementorImage,
  },
  {
    title: 'SiteOrigin Plugin',
    handleClick: track.pluginClick.bind(null, 'siteorigin'),
    docLink:
      'https://razorpay.com/docs/payments/payment-button/supported-platforms/wordpress/site-origin/',
    docLink2: 'https://wordpress.org/plugins/razorpay-payment-button-for-siteorigin/',
    icon: SiteOriginImage,
  },
  {
    title: 'Visual Composer Plugin',
    handleClick: track.pluginClick.bind(null, 'visualcomposer'),
    docLink:
      'https://razorpay.com/docs/payments/payment-button/supported-platforms/wordpress/visual-composer/',
    docLink2: 'https://wordpress.org/plugins/razorpay-payment-button-for-visual-composer/',
    icon: VisualComposerImage,
  },
  {
    title: 'Drupal Plugin',
    handleClick: track.pluginClick.bind(null, 'drupal'),
    docLink: 'https://razorpay.com/docs/payments/payment-button/supported-platforms/#drupal-plugin',
    docLink2: 'https://www.drupal.org/project/payment_button_drupal_plugin',
    icon: DrupalImage,
  },
];

const integrationsList = [
  {
    title: 'Go Daddy',
    handleClick: track.pluginClick.bind(null, 'godaddy'),
    docLink: '#godaddy',
    icon: GoDaddyImage,
  },
  {
    title: 'Weebly',
    handleClick: track.pluginClick.bind(null, 'weebly'),
    docLink: '#weebly',
    icon: WeeblyImage,
  },
  {
    title: 'Wix',
    handleClick: track.pluginClick.bind(null, 'wix'),
    docLink: '#wix',
    icon: WixImage,
  },
  {
    title: 'Google Sites',
    handleClick: track.pluginClick.bind(null, 'google_sites'),
    docLink: '#google-sites',
    icon: GoogleSitesImage,
  },
  {
    title: 'Blogger',
    handleClick: track.pluginClick.bind(null, 'blogger'),
    docLink: '#blogger',
    icon: BloggerImage,
  },
];
@withI18Service
@connect((state) => ({
  user: state.session.user,
  mode: state.session.mode,
}))
class SuccessModal extends React.Component {
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
    const { isPBDirectPluginLinks } = this.props.user;
    const { isConfigTagEnabled } = this.props.i18;

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
                <ShowWhen
                  additionalCondition={() =>
                    !isConfigTagEnabled('payment_buttons.other_integration_methods')
                  }
                >
                  <div class="integration-section">
                    <b>No-Code Plugins to add this button on your website directly:</b>
                    <div class="plugins">
                      {pluginsList.map((plugin, i) => (
                        <div key={`plugin-${i}`}>
                          <img
                            src={plugin.icon}
                            alt={`${plugin.title} Logo`}
                            height="19"
                            width="19"
                          />
                          <DocLink
                            href={plugin[isPBDirectPluginLinks ? 'docLink2' : 'docLink']}
                            target="_blank"
                            rel="noopener noreferrer"
                            onClick={plugin.handleClick.bind(null, isPBDirectPluginLinks)}
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
                            src={integration.icon}
                            alt={`${integration.title} Logo`}
                            height="19"
                            width="19"
                          />
                          <DocLink
                            href={`https://razorpay.com/docs/payment-button/supported-platforms/${integration.docLink}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            onClick={integration.handleClick}
                          >
                            {integration.title}
                          </DocLink>
                        </div>
                      ))}
                    </div>
                  </div>
                </ShowWhen>
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

export default withRouter(SuccessModal);
