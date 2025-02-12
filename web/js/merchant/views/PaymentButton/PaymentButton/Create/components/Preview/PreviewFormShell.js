import React from 'react';
import { connect } from 'react-redux';

import { getCurrency } from 'common/ui/Amount';
import { getCurrencyConfig } from 'common/utils/rzp-utils';
import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';

const blackColor = '#263a4a';
const whiteColor = '#fff';

class PreviewFormShell extends React.Component {
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
      <div className="PaymentButton-PreviewFormShell">
        <div className="PreviewFormShell-header">
          <div
            className="Preview-topbar"
            style={{
              color: this.state.textColor,
              backgroundColor: brandColor,
            }}
          >
            <div className="Preview-topbar-title">
              <span>{this.title}</span>
            </div>

            <div className="Preview-controls">
              <div className="Preview-progressDots">
                {Array.from({ length: this.totalDots }).map((_, index) => (
                  <span
                    key={index}
                    style={{
                      borderColor: brandColor,
                      background: brandColor,
                    }}
                    className={`${index === this.activeDotIndex ? 'active' : ''} ${
                      index <= this.activeDotIndex ? 'marked' : ''
                    }`}
                  />
                ))}
              </div>
            </div>
          </div>
          <div
            className="Preview-topbar-border"
            style={{
              backgroundColor: brandColor,
            }}
          >
            <div />
          </div>
          <div
            className="PreviewFormShell-header-details"
            style={{
              color: this.state.textColor,
              backgroundColor: brandColor,
            }}
          >
            <i
              className="i i-arrow-back"
              style={{ color: this.state.textColor, marginTop: '4px' }}
            />
            {brandLogoUrl && <img src={brandLogoUrl} width="35px" height="35px" />}
            <div>
              <div className="header-details-merchant">{merchantBillingLabel}</div>
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
              <div className="Field-dummy-btn">{buttonTitle}</div>
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
