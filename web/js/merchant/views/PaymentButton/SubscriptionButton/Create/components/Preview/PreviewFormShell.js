import React from 'react';
import { connect } from 'react-redux';

import { getCurrency } from 'common/ui/Amount';

const blackColor = '#263a4a';
const whiteColor = '#fff';

@connect((state) => ({
  config: state.config,
  user: state.session.user,
}))
export default class PreviewFormShell extends React.Component {
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
      <div class="PaymentButton-PreviewFormShell">
        <div class="PreviewFormShell-header">
          <div class="Preview-topbar">
            <div class="Preview-topbar-title">
              <span>{shellTitle}</span>
            </div>

            <div class="Preview-controls">
              <div class="Preview-progressDots">
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

              <span class="Preview-cross">
                <span>×</span>
              </span>
            </div>
          </div>

          <div>
            <div class="PreviewFormShell-header-details">
              {brandLogoUrl && <img src={brandLogoUrl} width="30px" height="30px" />}
              <div>
                <div class="header-details-merchant">{merchantBillingLabel}</div>
              </div>
            </div>
          </div>
        </div>
        <div class="PreviewFormShell-checkout-form">
          <div class="Body">{children}</div>

          {buttonTitle && (
            <div class="Footer">
              <div class="Footer--Amount">
                {this.currencySymbol} {this.displayAmountToPayByCustomer}
              </div>
              <div
                class="Field-dummy-btn"
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
