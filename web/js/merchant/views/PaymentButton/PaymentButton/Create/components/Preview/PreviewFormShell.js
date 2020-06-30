import { connect } from 'react-redux';
import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';
import { getCurrency } from 'common/ui/Amount';

@connect(state => ({
  config: state.config,
  user: state.session.user,
}))
export default class PreviewFormShell extends React.Component {
  state = {
    textColor: null,
  };

  totalDots = this.isQuickPayTemplate ? 2 : 3;

  get textColor() {
    const _textColor = this.props.config.isBrandColorDark
      ? '#fff'
      : 'rgba(0, 0, 0, 0.85)';

    return _textColor;
  }

  get isQuickPayTemplate() {
    const { paymentButtonEntity } = this.props;

    const templateType =
      paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.quickPay.key;
  }

  get isDonationsTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType =
      paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.donation.key;
  }

  get currencySymbol() {
    const currency = this.props.paymentButtonEntity.currency;
    const _currencySymbol = getCurrency(currency).symbol;

    return _currencySymbol;
  }

  get displayAmountToPayByCustomer() {
    let _amountToPayByCustomer = 0;

    if (this.isQuickPayTemplate) {
      const { amountFields } = this.props;
      const amountItem = amountFields[0];

      if (amountItem) {
        _amountToPayByCustomer = amountItem.item.amount;
      }
    }

    const displayAmount = Number(_amountToPayByCustomer).toFixed(2);

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
              <span>{this.title}</span>
            </div>

            <div class="Preview-controls">
              <div class="Preview-progressDots">
                {Array.apply(null, { length: this.totalDots }).map(
                  (_, index) => (
                    <span
                      key={index}
                      style={{
                        borderColor: this.textColor,
                        backgroundColor:
                          index <= this.activeDotIndex ? this.textColor : null,
                      }}
                    />
                  )
                )}
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
