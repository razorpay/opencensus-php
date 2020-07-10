import { connect } from 'react-redux';
import { getCurrency } from 'common/ui/Amount';

@connect(state => ({
  config: state.config,
  user: state.session.user,
}))
export default class PreviewFormShell extends React.Component {
  state = {
    textColor: null,
  };

  get textColor() {
    const _textColor = this.props.config.isBrandColorDark
      ? '#fff'
      : 'rgba(0, 0, 0, 0.85)';

    return _textColor;
  }

  get currencySymbol() {
    const currency = this.props.subscriptionButtonEntity.currency;
    const _currencySymbol = getCurrency(currency).symbol;

    return _currencySymbol;
  }

  get displayAmountToPayByCustomer() {
    let _amountToPayByCustomer = 0;
    const displayAmount = Number(_amountToPayByCustomer).toFixed(2);

    return displayAmount;
  }

  get activeDotIndex() {
    let _activeDotIndex = 0;

    return _activeDotIndex;
  }

  render() {
    const {
      children,
      buttonTitle,
      shellTitle,
      activeDotIndex,
      totalDots,
      config,
      user,
    } = this.props;

    const merchantBillingLabel = user.billing_label,
      brandColor = config.config.brand_color,
      brandLogoUrl = config.config.logo_url;

    return (
      <div class="PaymentButton-PreviewFormShell">
        <div
          class="PreviewFormShell-header"
          style={{ backgroundColor: brandColor }}
        >
          <div class="Preview-topbar" style={{ color: this.textColor }}>
            <div class="Preview-topbar-title">
              <span>{shellTitle}</span>
            </div>

            <div class="Preview-controls">
              <div class="Preview-progressDots">
                {Array.apply(null, { length: totalDots }).map((_, index) => (
                  <span
                    key={index}
                    style={{
                      borderColor: this.textColor,
                      backgroundColor:
                        index <= activeDotIndex ? this.textColor : null,
                    }}
                  />
                ))}
              </div>

              <span class="Preview-cross">
                <span>×</span>
              </span>
            </div>
          </div>

          <div>
            {brandLogoUrl && (
              <div class="PreviewFormShell-header-logo">
                <img src={brandLogoUrl} width="100%" />
              </div>
            )}

            <div class="PreviewFormShell-header-details">
              {this.textColor && (
                <div
                  class="header-details-merchant"
                  style={{ color: this.textColor }}
                >
                  <div class="header-details-merchant-name">
                    {merchantBillingLabel}
                  </div>
                  <div class="header-details-amount">
                    {this.currencySymbol} {this.displayAmountToPayByCustomer}
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
        <div class="PreviewFormShell-checkout-form">
          {children}

          {buttonTitle && (
            <div
              class="Field-dummy-btn"
              style={{
                color: this.textColor,
                backgroundColor: brandColor,
              }}
            >
              {buttonTitle}
            </div>
          )}
        </div>
      </div>
    );
  }
}
