import { connect } from 'react-redux';
import { withRouter, Link } from 'react-router-dom';

import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import CustomClipboard from 'common/ui/Clipboard/Custom';

import { setIsPaymentButtonCodeUsed } from '../../utils';
import track from '../Details/track';

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

    const paymentBtnCode = `<form><script src="https://cdn.razorpay.com/static/widget/payment-button.js" data-payment_button_id="${
      paymentButton.id
    }"> </script> </form>`;

    return paymentBtnCode;
  }

  render() {
    const content = (
      <div className="Form-container">
        <div className="Form-title">
          Button Created Successfully
          <div className="Form-description">Your payment button is ready for integration</div>
        </div>

        <div className="Form">
          <div className="Form-buttonIntegration">
            <div className="Form-buttonIntegration-title">
              <b>Integrate On your Website</b>
            </div>

            {/* Integrate via copy-code */}
            <div className="Form-buttonIntegration-code">
              <div>
                <span className="help-text">Copy & paste this HTML in your code.</span>

                <CustomClipboard value={this.codeToCopy}>
                  <Button.Primary onClick={this.onClickCopy}>
                    <i className="i i-copy m-r" />
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

              <div className="help-text">
                How do I use this code? See our documentation{' '}
                <a href="" target="_blank">
                  here <i className="i i-external-link m-l" />
                </a>
              </div>
            </div>

            {/* Other integration methods */}
            <div className="Form-buttonIntegration-others">
              <b>Plan on using this on platforms like Wix, Weebly?</b>

              {/*
              <div className="help-text">
                Check out our integration guide for{' '}
                <a
                  href="https://betasite.razorpay.com/docs/pb-index-true/payment-button/supported-platforms#wordpress"
                  target="_blank"
                >
                  Wordpress <i className="i i-external-link m-l" />
                </a>
              </div>
*/}

              <div class="help-text">
                Integration guide for{' '}
                <a
                  href="https://betasite.razorpay.com/docs/pb-index-true/payment-button/supported-platforms/"
                  target="_blank"
                >
                  Platforms<i class="i i-external-link m-l" />
                </a>
              </div>
            </div>
          </div>

          <div className="Form-buttonActions">
            <div className="Form-buttonActions-title">
              <b>After a Successful Payment</b>
            </div>

            {/* Payment Receipt Action Modal */}
            <div className="Form-buttonActions-receipt">
              <div>
                <i className="i i-document m-r" /> <b>Send Payment Receipts</b>
                <Button class="Button--primary--invert" onClick={this.props.openPageReceiptModal}>
                  <b>CONFIGURE</b>
                </Button>
              </div>
              <div className="help-text">
                Send automated receipts for transactions on your Payment Button.
              </div>
            </div>

            {/* Redirect Url Action Modal */}
            <div className="Form-buttonActions-postPayment">
              <div>
                <i className="i i-checked-document m-r" /> <b>Redirect URL and Custom Message</b>
                <Button class="Button--primary--invert" onClick={this.props.openSettingsModal}>
                  <b>CONFIGURE</b>
                </Button>
              </div>

              <div className="help-text">
                Show a custom message and(or) redirect customers after payments.
              </div>
            </div>

            <div className="help-text help-text-settings">
              <i className="i i-info-outline m-r" /> These settings can also be configured from the{' '}
              <a onClick={this.onClickButtonSettings}>details page</a> of this button.
            </div>
          </div>

          <div className="Form-controls">
            <Link to="/paymentbuttons" class="Button Button--primary">
              Back To Dashboard
            </Link>
          </div>
        </div>
      </div>
    );

    return <div class="PaymentButton-Create-Form PaymentButton-Create-SuccessView">{content}</div>;
  }
}
