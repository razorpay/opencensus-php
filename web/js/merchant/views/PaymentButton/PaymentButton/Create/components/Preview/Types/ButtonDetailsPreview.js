import { connect } from 'react-redux';
import { classList } from 'common/utils/rzp-utils';
import { maxLengthForButtonLabel } from '../../Form/ButtonDetails';
import { buttonThemes } from '../../../constants/buttonThemes';

const rzpLogoWhite = (
  <svg
    width="18"
    height="20"
    viewBox="0 0 18 20"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <path
      d="M7.077 6.476l-.988 3.569 5.65-3.589-3.695 13.54 3.752.004 5.457-20L7.077 6.476z"
      fill="#fff"
    />
    <path d="M1.455 14.308L0 20h7.202L10.149 8.42l-8.694 5.887z" fill="#fff" />
  </svg>
);

let isFontLoadedForButton = false;

@connect(state => ({
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

    const head = document.head || document.getElementsByTagName('head')[0],
      style = document.createElement('style');

    var css = `@import url('https://fonts.googleapis.com/css2?family=Muli:wght@700;800&display=swap');`;

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
    const { paymentButtonEntity } = this.props;
    const buttonText = paymentButtonEntity.settings.payment_button_text,
      buttonTheme = paymentButtonEntity.settings.payment_button_theme;

    const isRazorpayTheme = buttonThemes.BRAND_COLOR.value !== buttonTheme;
    let isLightTheme = true; // Default false bcoz meanwhile the colorJS script is loading, light theme enables dark color text which works well with all contrasts.

    if (buttonThemes.RZP_DARK_STANDARD.value === buttonTheme) {
      isLightTheme = false;
    } else if (buttonThemes.BRAND_COLOR.value === buttonTheme) {
      isLightTheme = !this.props.config.isBrandColorDark;
    }

    return (
      <div class="ButtonDetailsPreview">
        <div
          class={classList(
            'PaymentButton-Button',
            isRazorpayTheme && 'PaymentButton-Button--rzpTheme',
            `PaymentButton-Button--${isLightTheme ? 'light' : 'dark'}`,
            isRazorpayTheme && `PaymentButton-Button--${buttonTheme}`
          )}
          style={{
            background: !isRazorpayTheme ? this.brandColor : '',
          }}
        >
          {rzpLogoWhite}

          <div class="PaymentButton-Button-contents">
            <span class="PaymentButton-Button-text">
              {buttonText
                ? buttonText.substring(0, maxLengthForButtonLabel)
                : ''}
            </span>
            <div class="PaymentButton-Button-rzpBranding">
              Secured by Razorpay
            </div>
          </div>
        </div>
      </div>
    );
  }
}
