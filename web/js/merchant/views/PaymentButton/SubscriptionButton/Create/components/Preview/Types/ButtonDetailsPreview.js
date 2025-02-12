import React from 'react';
import { connect } from 'react-redux';

import { classList } from 'common/utils/rzp-utils';

import { buttonThemes } from '../../../constants/buttonThemes';
import { maxLengthForButtonLabel } from '../../Form/ButtonDetails';

const rzpLogoWhite = (
  <svg width="18" height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path
      d="M7.077 6.476l-.988 3.569 5.65-3.589-3.695 13.54 3.752.004 5.457-20L7.077 6.476z"
      fill="#fff"
    />
    <path d="M1.455 14.308L0 20h7.202L10.149 8.42l-8.694 5.887z" fill="#fff" />
  </svg>
);

let isFontLoadedForButton = false;

class ButtonDetailsPreview extends React.Component {
  constructor() {
    super();

    this.loadFontForButton();
  }

  loadFontForButton() {
    // This check is due to multiple instances of ButtonDetailsPreview
    if (isFontLoadedForButton) {
      return;
    }

    isFontLoadedForButton = true;

    const head = document.head || document.getElementsByTagName('head')[0];
    const style = document.createElement('style');

    const css = `@import url('https://fonts.googleapis.com/css2?family=Muli:wght@400;600;700;800&display=swap');`;

    head.appendChild(style);

    style.type = 'text/css';
    if (style.styleSheet) {
      style.styleSheet.cssText = css;
    } else {
      style.appendChild(document.createTextNode(css));
    }
  }

  get brandColor() {
    return this.props.config.config.brand_color;
  }

  render() {
    const { subscriptionButtonEntity, showOneTimePayments } = this.props;
    const buttonText = subscriptionButtonEntity.settings.payment_button_text;
    const buttonTheme = subscriptionButtonEntity.settings.payment_button_theme;

    const isRazorpayTheme = buttonThemes.BRAND_COLOR.value !== buttonTheme;
    let isLightTheme = true; // Default false bcoz meanwhile the colorJS script is loading, light theme enables dark color text which works well with all contrasts.

    if (buttonThemes.RZP_DARK_STANDARD.value === buttonTheme) {
      isLightTheme = false;
    } else if (buttonThemes.BRAND_COLOR.value === buttonTheme) {
      isLightTheme = !this.props.config.isBrandColorDark;
    }

    let displayButtonText = '';

    if (buttonText) {
      if (showOneTimePayments) {
        const regEx = new RegExp('subscribe', 'ig');

        displayButtonText = buttonText.replace(regEx, 'Pay');
      } else {
        displayButtonText = buttonText;
      }

      displayButtonText = displayButtonText.substring(0, maxLengthForButtonLabel);
    }

    return (
      <div className="ButtonDetailsPreview ButtonDetailsPreview--subscriptionButton">
        <div
          className={classList(
            'PaymentButton-Button',
            isRazorpayTheme && 'PaymentButton-Button--rzpTheme',
            `PaymentButton-Button--${isLightTheme ? 'light' : 'dark'}`,
            isRazorpayTheme && `PaymentButton-Button--${buttonTheme}`,
          )}
          style={{
            background: !isRazorpayTheme ? this.brandColor : '',
          }}
        >
          {rzpLogoWhite}

          <div className="PaymentButton-Button-contents">
            <span className="PaymentButton-Button-text">{displayButtonText}</span>
          </div>
        </div>
        <div className="PaymentButton-Button-rzpBranding">
          <span className="powered-by-razorpay">
            <span>Powered by</span> <img src="/img/logo_black.png" />
          </span>
        </div>
      </div>
    );
  }
}

export default connect((state) => ({
  config: state.config,
}))(ButtonDetailsPreview);
