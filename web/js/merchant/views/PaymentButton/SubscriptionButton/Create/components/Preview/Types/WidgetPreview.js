import React from 'react';
import { getCurrency } from 'common/ui/Amount';
import ButtonDetailsPreview from './ButtonDetailsPreview';

import { filterSubscriptionPaymentItems } from 'merchant/reducers/subscriptionButtons/create';
import {
  classList,
  i18CurrencyConversionFromCommonUnitToMinorUnit,
  getFormattedAmount,
} from 'common/utils/rzp-utils';
import { getPeriodLabel } from 'merchant/views/PaymentButton/SubscriptionButton/Create/constants/billingCycle';

export default class WidgetPreview extends React.Component {
  getPaymentField(field) {
    const { subscriptionButtonEntity, showOneTimePayments } = this.props;
    const currency = subscriptionButtonEntity.currency;
    const currencySymbol = getCurrency(currency).symbol;

    const amount = field.plan_id
      ? field.item.amount
      : i18CurrencyConversionFromCommonUnitToMinorUnit(field.item.amount, currency);

    return (
      <label class="item-label">
        <div class="item">
          <div class="item-title">{field.item.name}</div>
          {field.item.description && <div class="item-description">{field.item.description}</div>}

          <div class="item-details">
            {amount ? (
              <span className="amount">
                <b>
                  {currencySymbol} {getFormattedAmount(amount, currency)}
                </b>
              </span>
            ) : null}

            {!showOneTimePayments && (
              <div class="item-details-description">
                Frequency:{' '}
                {getPeriodLabel(
                  field.product_config.plan_details.period,
                  field.product_config.plan_details.interval,
                )}
              </div>
            )}
          </div>
        </div>
      </label>
    );
  }

  render() {
    const { paymentFields, showOneTimePayments } = this.props;

    const currentTabFields = filterSubscriptionPaymentItems(paymentFields, showOneTimePayments);
    const otherTabFields = filterSubscriptionPaymentItems(paymentFields, !showOneTimePayments);

    return (
      <div class="WidgetPreview">
        {!!currentTabFields.length && !!otherTabFields.length && (
          <div class="billing-cycle-type-options">
            <label>
              <div class={classList('option', !showOneTimePayments && 'highlight')}>
                Subscription
              </div>
            </label>
            <label>
              <div class={classList('option', showOneTimePayments && 'highlight')}>OneTime</div>
            </label>
          </div>
        )}

        <div>
          {currentTabFields && currentTabFields.length ? (
            currentTabFields.map((field, index) => (
              <React.Fragment key={index}>{this.getPaymentField(field)}</React.Fragment>
            ))
          ) : (
            <label class="item-label item-label--empty">
              <div class="item">
                <div class="item-details">
                  {showOneTimePayments
                    ? 'Add one-time items to see their preview'
                    : 'Add plans to see their preview'}
                </div>
              </div>
            </label>
          )}
        </div>

        <ButtonDetailsPreview {...this.props} />
      </div>
    );
  }
}
