import React from 'react';
import { connect } from 'react-redux';

import { getCurrency } from 'common/ui/Amount';

const blackColor = '#263a4a';
const whiteColor = '#fff';

class PreviewFormShell extends React.Component {
  state = {
    textColor: whiteColor,
  };

  static getDerivedStateFromProps(props, state) {
    if (props.config?.isBrandColorDark !== state.config?.isBrandColorDark) {
      return {
        textColor: props.config?.isBrandColorDark ? whiteColor : blackColor,
      };
    }
    return null;
  }

  get currencySymbol() {
    const currency = this.props.subscriptionButtonEntity.currency;
    const _currencySymbol = getCurrency(currency).symbol;

    return _currencySymbol;
  }

  get displayAmountToPayByCustomer() {
    const _amountToPayByCustomer = 0;
    const displayAmount = Number(_amountToPayByCustomer).toFixed(2);

    return displayAmount;
  }

  get activeDotIndex() {
    const _activeDotIndex = 0;

    return _activeDotIndex;
  }

  render() {
    const { children, buttonTitle, shellTitle, totalDots, config, user } = this.props;

    const merchantBillingLabel = user.billing_label;
    const brandColor = config.config.brand_color;
    const brandLogoUrl = config.config.logo_url;

    return (
      <div className="PaymentButton-PreviewFormShell">
        <div className="PreviewFormShell-header">
          <div className="Preview-topbar">
            <div className="Preview-topbar-title">
              <span>{shellTitle}</span>
            </div>

            <div className="Preview-controls">
              <div className="Preview-progressDots">
                {Array.from({ length: totalDots }).map((_, index) => (
                  <span
                    key={index}
                    style={{
                      borderColor: brandColor,
                      backgroundColor: index <= this.activeDotIndex ? brandColor : whiteColor,
                    }}
                  />
                ))}
              </div>

              <span className="Preview-cross">
                <span>×</span>
              </span>
            </div>
          </div>

          <div>
            <div className="PreviewFormShell-header-details">
              {brandLogoUrl && <img src={brandLogoUrl} width="30px" height="30px" />}
              <div>
                <div className="header-details-merchant">{merchantBillingLabel}</div>
              </div>
            </div>
          </div>
        </div>
        <div className="PreviewFormShell-checkout-form">
          <div className="Body">{children}</div>

          {buttonTitle && (
            <div className="Footer">
              <div className="Footer--Amount">
                {this.currencySymbol} {this.displayAmountToPayByCustomer}
              </div>
              <div
                className="Field-dummy-btn"
                style={{
                  color: this.state.textColor,
                  backgroundColor: brandColor,
                }}
              >
                {buttonTitle}
              </div>
            </div>
          )}
        </div>
      </div>
    );
  }
}

export default connect((state) => ({
  config: state.config,
  user: state.session.user,
}))(PreviewFormShell);
