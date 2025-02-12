import React from 'react';
import { connect } from 'react-redux';

import { classList } from 'common/utils/rzp-utils';
import { maxLengthForButtonLabel } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Form/ButtonDetails';
import {
  getButtonThemes,
  buttonThemesList,
} from 'merchant/views/PaymentButton/PaymentButton/Create/constants/buttonThemes';

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
    this.buttonThemes = getButtonThemes();
  }

  loadFontForButton() {
    // This check is due to multiple instances of ButtonDetailsPreview
    if (isFontLoadedForButton) {
      return;
    }

    isFontLoadedForButton = true;

    const head = document.head || document.getElementsByTagName('head')[0];
    const style = document.createElement('style');

    const css = `@import url('https://fonts.googleapis.com/css2?family=Muli:wght@700;800&display=swap');`;

    head.appendChild(style);

    style.type = 'text/css';
    if (style.styleSheet) {
      style.styleSheet.cssText = css;
    } else {
      style.appendChild(document.createTextNode(css));
    }
  }

  get brandColor() {
    if (this.isCustomTheme) {
      const { paymentButtonEntity } = this.props;
      const buttonTheme = paymentButtonEntity.settings.payment_button_theme;
      return buttonTheme;
    } else {
      return this.props.config.config.brand_color;
    }
  }

  get isColorDark() {
    const { paymentButtonEntity } = this.props;
    const buttonTheme = paymentButtonEntity.settings.payment_button_theme;

    let _isColorDark;

    if (this.isCustomTheme) {
      // Custom color code (hex)
      _isColorDark = window.colorLib ? window.colorLib.isDark(buttonTheme) : false;
    } else {
      _isColorDark = this.props.config.isBrandColorDark;
    }

    return _isColorDark;
  }

  get isCustomTheme() {
    const { paymentButtonEntity } = this.props;
    const buttonTheme = paymentButtonEntity.settings.payment_button_theme;

    const isPredefinedTheme = buttonThemesList.find((theme) => theme.value === buttonTheme);

    return !isPredefinedTheme;
  }

  get isRazorpayTheme() {
    const { paymentButtonEntity } = this.props;

    const buttonTheme = paymentButtonEntity.settings.payment_button_theme;

    const razorpayButtonThemes = [
      this.buttonThemes.BTN_DARK_STANDARD,
      this.buttonThemes.BTN_OUTLINE_STANDARD,
      this.buttonThemes.BTN_LIGHT_STANDARD,
    ];

    if (razorpayButtonThemes.find((theme) => theme.value === buttonTheme)) {
      return true;
    }

    return false;
  }

  get brandingText() {
    const { user, org } = this.props;
    const businessName = org.business_name;
    if (user.isOrgRZP || user.isOrgCurlec) {
      return `Secured by ${businessName}`;
    }
    const customBrand = (
      <>
        Secured by{' '}
        <img className="secured-by-logo" src={org.payment_btn_logo_url} alt="brand" height="10px" />
      </>
    );
    return customBrand;
  }

  render() {
    const { paymentButtonEntity, user } = this.props;
    const buttonText = paymentButtonEntity.settings.payment_button_text;
    const buttonTheme = paymentButtonEntity.settings.payment_button_theme;

    let isLightTheme = true; // Default false bcoz meanwhile the colorJS script is loading, light theme enables dark color text which works well with all contrasts.

    if (this.buttonThemes.BTN_DARK_STANDARD.value === buttonTheme) {
      isLightTheme = false;
    } else if (!this.isRazorpayTheme) {
      isLightTheme = !this.isColorDark;
    }

    return (
      <div className="ButtonDetailsPreview">
        <div
          className={classList(
            'PaymentButton-Button',
            this.isRazorpayTheme && 'PaymentButton-Button--rzpTheme',
            `PaymentButton-Button--${isLightTheme ? 'light' : 'dark'}`,
            this.isRazorpayTheme && `PaymentButton-Button--${buttonTheme}`,
            !user.isOrgRZP && 'PaymentButton-Button--noLogo',
          )}
          style={{
            background: !this.isRazorpayTheme ? this.brandColor : '',
          }}
        >
          {(user.isOrgRZP || user.isOrgCurlec) && rzpLogoWhite}

          <div className="PaymentButton-Button-contents">
            <span className="PaymentButton-Button-text">
              {buttonText ? buttonText.substring(0, maxLengthForButtonLabel) : ''}
            </span>
            <div className="PaymentButton-Button-rzpBranding">{this.brandingText}</div>
          </div>
        </div>
      </div>
    );
  }
}

export default connect((state) => ({
  user: state.session.user,
  org: state.session.org,
  config: state.config,
}))(ButtonDetailsPreview);
