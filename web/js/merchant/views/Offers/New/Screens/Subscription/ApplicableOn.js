import React from 'react';
import Input from 'common/new-ui/Input';
import {
  SUBSCRIPTION_OFFERS_PAYMENT_METHODS,
  SUBSCRIPTION_OFFERS_PAYMENT_METHODS_OPTIONS,
  PaymentIssuersOptions,
  SUBSCRIPTION_OFFERS_PAYMENT_DC_ISSUERS_OPTIONS,
  SUBSCRIPTION_OFFERS_PAYMENT_NETWORKS_OPTIONS,
  CREDIT_DEBIT_CARDS_OPTIONS,
  CARD_TYPES,
} from 'merchant/views/Offers/constants';
import { validatePaymentMethod, validateMaxPaymentCount } from 'merchant/views/Offers/New/helpers';

export default class ApplicableOn extends React.Component {
  get currentSelectedPaymentMethod() {
    const { payment_method } = this.props.formData;

    return {
      isCard: payment_method === SUBSCRIPTION_OFFERS_PAYMENT_METHODS.Card,
      isUPI: payment_method === SUBSCRIPTION_OFFERS_PAYMENT_METHODS.UPI,
      // isEmandate: payment_method === SUBSCRIPTION_OFFERS_PAYMENT_METHODS.Emandate,
    };
  }

  render() {
    const { formData, isFormLocked } = this.props;
    const {
      payment_method_type,
      payment_method,
      issuer,
      payment_network,
      max_payment_count,
      iins,
    } = formData;
    const { isCard } = this.currentSelectedPaymentMethod;

    const isDebitCard = payment_method_type === CARD_TYPES.DEBIT;

    return (
      <React.Fragment>
        <Input.Select
          required
          name="payment_method"
          label="Payment Method"
          options={SUBSCRIPTION_OFFERS_PAYMENT_METHODS_OPTIONS}
          placeholder="Select Payment Method"
          defaultValue={payment_method}
          validator={validatePaymentMethod}
          disabled={isFormLocked}
        />

        {isCard && (
          <React.Fragment>
            <Input.Select
              name="payment_method_type"
              label="Card Type"
              defaultValue={payment_method_type}
              options={CREDIT_DEBIT_CARDS_OPTIONS}
              disabled={isFormLocked}
            />

            <Input.Select
              name="issuer"
              label="Bank"
              placeholder="Select Bank"
              defaultValue={issuer}
              disabled={isFormLocked}
              options={
                isDebitCard ? SUBSCRIPTION_OFFERS_PAYMENT_DC_ISSUERS_OPTIONS : PaymentIssuersOptions
              }
            />

            <Input.Select
              label="Network"
              name="payment_network"
              placeholder="Select network"
              defaultValue={payment_network}
              disabled={isFormLocked}
              options={SUBSCRIPTION_OFFERS_PAYMENT_NETWORKS_OPTIONS}
            />

            <Input
              type="number"
              label="Max Usage Per Card"
              name="max_payment_count"
              defaultValue={max_payment_count}
              placeholder="Max times a card can be used to avail this offer"
              validator={validateMaxPaymentCount}
              disabled={isFormLocked}
            />

            <Input
              name="iins"
              label="IINs"
              defaultValue={iins}
              placeholder="6 digit IINs for cards. Separated by comma if more than one"
              description={iins && iins.join(', ')}
              disabled={isFormLocked}
            />
          </React.Fragment>
        )}
      </React.Fragment>
    );
  }
}
