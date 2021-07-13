import { connect } from 'react-redux';
import { withRouter, Link } from 'react-router-dom';

import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import { DocLink } from 'merchant/components/DocsLink'

import { setIsPaymentButtonCodeUsed } from '../../utils';
import track from '../Details/track';

const pluginsList = [
  {
    title: 'Wordpress Plugin',
    docLink: 'wordpress',
    icon: 'wordpress.svg',
  },
  {
    title: 'Elementor Plugin',
    docLink: 'wordpress/elementor',
    icon: 'elementor.svg',
  },
];

const integrationsList = [
  {
    title: 'Go Daddy',
    docLink: '#godaddy',
    icon: 'goDaddy.svg',
  },
  {
    title: 'Weebly',
    docLink: '#weebly',
    icon: 'weebly.svg',
  },
  {
    title: 'Wix',
    docLink: '#wix',
    icon: 'wix.svg',
  },
  {
    title: 'Google Sites',
    docLink: '#google-sites',
    icon: 'googleSites.svg',
  },
  {
    title: 'Blogger',
    docLink: '#blogger',
    icon: 'blogger.svg',
  },
]
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

    track.lj.trackCopyCode();
  };

  onClickTextArea = () => {
    if (!this.textarea) {
      return;
    }

    this.textarea.select();
    document.execCommand && document.execCommand('copy');

    track.lj.trackCopyCode();
  };

  onClickButtonSettings = () => {
    const { paymentButton } = this.props;

    this.props.history.push(`/paymentbuttons/${paymentButton.id}/payments`);

    this.props.updateHighlightButtonSettings(paymentButton.id);

    this.props.onClickButtonSettings && this.props.onClickButtonSettings();
  };

  setRef = (textarea) => (this.textarea = textarea);

  get codeToCopy() {
    const { paymentButton } = this.props;

    const paymentBtnCode = `<form><script src="https://checkout.razorpay.com/v1/payment-button.js" data-payment_button_id="${paymentButton.id}" async> </script> </form>`;

    return paymentBtnCode;
  }

  render() {
    const content = (
      <div class="Form-container">
        <div class="Form-title">
          Button Created Successfully
          <div class="Form-description">Your payment button is ready for integration</div>
        </div>

        <div class="Form">
          <div class="Form-buttonIntegration">
            <div class="Form-buttonIntegration-title">
              <b>Integrate On your Website</b>
            </div>

            {/* Integrate via copy-code */}
            <div class="Form-buttonIntegration-code">
              <div>
                <span class="help-text">Copy & paste this HTML in your code.</span>

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

              <div class="help-text">
                Want to try this code before integration?{' '}
                <a
                  href="https://cdn.razorpay.com/static/widget/test-payment-button.html"
                  target="_blank"
                  class="Button Button--primary--invert try-now-btn"
                >
                  <i class="i i-play-arrow" /> TRY CODE
                </a>
              </div>
            </div>

            {/* Other integration methods */}
            <div class="Form-buttonIntegration-others">
              <b>Plan on using this on platforms like Wordpress, Wix?</b>

              <div class="help-text">
                Check out our integration guide for{' '}
                <DocLink
                  href="https://razorpay.com/docs/payment-button/supported-platforms/wordpress"
                  target="_blank"
                >
                  Wordpress <i class="i i-external-link m-l" />
                </DocLink>
              </div>

              <div class="help-text">
                Integration guide for{' '}
                <DocLink
                  href="https://razorpay.com/docs/payment-button/supported-platforms"
                  target="_blank"
                >
                  Other Platforms
                  <i class="i i-external-link m-l" />
                </DocLink>
              </div>
            </div>
          </div>

          <div class="Form-buttonActions">
            <div class="Form-buttonActions-title">
              <b>After a Successful Payment</b>
            </div>

            {/* Payment Receipt Action Modal */}
            <div class="Form-buttonActions-receipt">
              <div>
                <i class="i i-document m-r" /> <b>Send Payment Receipts</b>
                <Button class="Button--primary--invert" onClick={this.props.openPageReceiptModal}>
                  <b>CONFIGURE</b>
                </Button>
              </div>
              <div class="help-text">
                Send automated receipts for transactions on your Payment Button.
              </div>
            </div>

            {/* Redirect Url Action Modal */}
            <div class="Form-buttonActions-postPayment">
              <div>
                <i class="i i-checked-document m-r" /> <b>Redirect URL and Custom Message</b>
                <Button class="Button--primary--invert" onClick={this.props.openSettingsModal}>
                  <b>CONFIGURE</b>
                </Button>
              </div>

              <div class="help-text">
                Show a custom message and(or) redirect customers after payments.
              </div>
            </div>

            <div class="help-text help-text-settings">
              <i class="i i-info-outline m-r" /> These settings can also be configured from the{' '}
              <a onClick={this.onClickButtonSettings}>details page</a> of this button.
            </div>
          </div>

          <div class="Form-controls">
            <Link to="/paymentbuttons" class="Button Button--primary">
              Back To Dashboard
            </Link>
          </div>
        </div>
      </div>
    );

    const contentV2 = (
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
              <div class="code-section" style={{ backgroundImage: 'url("/dist/css/assets/payment_button/success-screen/code-background.svg")'}}>
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
                  >
                    <i class="i i-play-arrow m-r" /> TEST
                </a>
                </div>
              </div>

              {/* Other integration methods */}
              <div class="integration-section">
                <b>No-Code Plugins to add this button on your website directly:</b>
                <div class="plugins">
                  {pluginsList.map(plugin => <div>
                      <img src={`/dist/css/assets/payment_button/success-screen/plugins/${plugin.icon}`} alt={`${plugin.title} Logo`} />
                      <DocLink href={`https://razorpay.com/docs/payment-button/supported-platforms/${plugin.docLink}`} target="_blank" rel="noreferrer">{plugin.title}</DocLink>
                    </div>
                  )}
                </div>
                <b>Integration guide for this code on other platforms:</b>
                <div class="integrations">
                  {integrationsList.map(integration => <div>
                      <img src={`/dist/css/assets/payment_button/success-screen/integrations/${integration.icon}`} alt={`${integration.title} Logo`} />
                      <DocLink href={`https://razorpay.com/docs/payment-button/supported-platforms/${integration.docLink}`} target="_blank" rel="noreferrer">{integration.title}</DocLink>
                    </div>
                  )}
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
                  <Button class="Button--primary--invert" onClick={this.props.openPageReceiptModal}>
                    <b>CONFIGURE</b>
                  </Button>
                </div>
              </div>

              {/* Redirect Url Action Modal */}
              <div class="postPayment-settings">
                <div>
                  <i class="i i-checked-document m-r" /> <span>Custom Message and Redirect to a URL</span>
                  <Button class="Button--primary--invert" onClick={this.props.openSettingsModal}>
                    <b>CONFIGURE</b>
                  </Button>
                </div>
              </div>
            </div>

            <div class="help-text help-text-settings">
              <i class="i i-info-outline m-r" /> These settings may be configured later from dashboard
            </div>
          </div>

          <div class="Form-controls">
            <Link to="/paymentbuttons" class="Button Button--primary">
              Back To Dashboard
            </Link>
          </div>
        </div>
      </div>
    );

    const wrapperClass = this.props.user.isPBNewSuccessScreenEnabled ? 'PaymentButton-Create-SuccessView-V2' : 'PaymentButton-Create-SuccessView';
    return (
      <div class={'PaymentButton-Create-Form ' + wrapperClass}>
        { this.props.user.isPBNewSuccessScreenEnabled ? contentV2 : content }
      </div>
    );
  }
}
