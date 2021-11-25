import React from 'react';
import { connect } from 'react-redux';
import { classList } from 'common/utils/rzp-utils';
import { maxLengthForButtonLabel } from '../../Form/ButtonDetails';
import { buttonThemes, buttonThemesList } from '../../../constants/buttonThemes';

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

@connect((state) => ({
  user: state.session.user,
  org: state.session.org,
  config: state.config,
}))
export default class ButtonDetailsPreview extends React.Component {
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
      buttonThemes.RZP_DARK_STANDARD,
      buttonThemes.RZP_OUTLINE_STANDARD,
      buttonThemes.RZP_LIGHT_STANDARD,
    ];

    if (razorpayButtonThemes.find((theme) => theme.value === buttonTheme)) {
      return true;
    }

    return false;
  }

  render() {
    const { paymentButtonEntity, user, org } = this.props;
    const buttonText = paymentButtonEntity.settings.payment_button_text;
    const buttonTheme = paymentButtonEntity.settings.payment_button_theme;

    let isLightTheme = true; // Default false bcoz meanwhile the colorJS script is loading, light theme enables dark color text which works well with all contrasts.

    if (buttonThemes.RZP_DARK_STANDARD.value === buttonTheme) {
      isLightTheme = false;
    } else if (!this.isRazorpayTheme) {
      isLightTheme = !this.isColorDark;
    }

    return (
      <div class="ButtonDetailsPreview">
        <div
          class={classList(
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
          {user.isOrgRZP && rzpLogoWhite}

          <div class="PaymentButton-Button-contents">
            <span class="PaymentButton-Button-text">
              {buttonText ? buttonText.substring(0, maxLengthForButtonLabel) : ''}
            </span>
            <div class="PaymentButton-Button-rzpBranding">
              {user.isOrgRZP ? (
                'Secured by Razorpay'
              ) : (
                <React.Fragment>
                  Secured by{' '}
                  <img
                    class="secured-by-logo"
                    src={org.payment_btn_logo_url}
                    alt="brand"
                    height="10px"
                  />
                </React.Fragment>
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }
}
