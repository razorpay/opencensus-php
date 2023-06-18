import React from 'react';
import { connect } from 'react-redux';

import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';
import { getCurrency } from 'common/ui/Amount';
import { getCurrencyConfig } from 'common/utils/rzp-utils';

const blackColor = '#263a4a';
const whiteColor = '#fff';

@connect((state) => ({
  config: state.config,
  user: state.session.user,
}))
export default class PreviewFormShell extends React.Component {
  totalDots = this.isQuickPayTemplate ? 2 : 3;

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

  get isQuickPayTemplate() {
    const { paymentButtonEntity } = this.props;

    const templateType = paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.quickPay.key;
  }

  get isDonationsTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType = paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.donation.key;
  }

  get currencySymbol() {
    const currency = this.props.paymentButtonEntity.currency;
    const _currencySymbol = getCurrency(currency).symbol;

    return _currencySymbol;
  }

  get displayAmountToPayByCustomer() {
    let _amountToPayByCustomer = 0;
    const { decimals } = getCurrencyConfig(this.props.paymentButtonEntity.currency);

    if (this.isQuickPayTemplate) {
      const { amountFields } = this.props;
      const amountItem = amountFields[0];

      if (amountItem) {
        _amountToPayByCustomer = amountItem.item.amount;
      }
    }

    const displayAmount = Number(_amountToPayByCustomer).toFixed(decimals);

    return displayAmount;
  }

  get title() {
    const { type } = this.props;
    let _title;

    if (type === 'amount-details') {
      if (this.isDonationsTemplate) {
        _title = 'DONATION AMOUNT';
      } else {
        _title = 'AMOUNT DETAILS';
      }
    } else if (type === 'customer-details') {
      if (this.isDonationsTemplate) {
        _title = 'DONOR DETAILS';
      } else {
        _title = 'CUSTOMER DETAILS';
      }
    }

    return _title;
  }

  get activeDotIndex() {
    const { type } = this.props;
    let _activeDotIndex;

    if (type === 'amount-details') {
      _activeDotIndex = 0;
    } else if (type === 'customer-details') {
      if (this.totalDots === 2) {
        _activeDotIndex = 0;
      } else {
        _activeDotIndex = 1;
      }
    }

    return _activeDotIndex;
  }

  render() {
    const { children, buttonTitle, config, user } = this.props;

    const merchantBillingLabel = user.billing_label;
    const brandColor = config.config.brand_color;
    const brandLogoUrl = config.config.logo_url;

    return (
      <div class="PaymentButton-PreviewFormShell">
        <div class="PreviewFormShell-header">
          <div class="Preview-topbar">
            <div class="Preview-topbar-title">
              <span>{this.title}</span>
            </div>

            <div class="Preview-controls">
              <div class="Preview-progressDots">
                {Array.from({ length: this.totalDots }).map((_, index) => (
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

          <div class="PreviewFormShell-header-details">
            {brandLogoUrl && <img src={brandLogoUrl} width="30px" height="30px" />}

            <div>
              <div class="header-details-merchant">{merchantBillingLabel}</div>
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
