import React from 'react';
import Input from 'common/new-ui/Input';
import {
  PAYMENT_METHODS,
  PaymentMethodsOptions,
  PaymentIssuersOptions,
  PaymentNetworksOptions,
  WalletIssuersOptions,
  CREDIT_DEBIT_CARDS_OPTIONS,
  EMI_CARDS_OPTIONS,
  EMI_DEBIT_CARD_BANK_OPTIONS,
} from 'merchant/views/Offers/constants';
import { validatePaymentMethod, validateMaxPaymentCount } from 'merchant/views/Offers/New/helpers';

export default class ApplicableOn extends React.Component {
  state = { selectedPaymentMethodType: '' };

  get currentSelectedPaymentMethod() {
    const { payment_method } = this.props.formData;
    const { Card, NetBanking, Wallet, UPI, EMI, PayLater, CardLessEmi } = PAYMENT_METHODS;
    return {
      isCard: payment_method === Card,
      isNetBanking: payment_method === NetBanking,
      isWallet: payment_method === Wallet,
      isUPI: payment_method === UPI,
      isEMI: payment_method === EMI,
      isPayLater: payment_method === PayLater,
      isCardLessEmi: payment_method === CardLessEmi,
    };
  }

  onMethodTypeChange = (event) => {
    const { value } = event.target;
    this.setState({ selectedPaymentMethodType: value });
  };

  render() {
    const { selectedPaymentMethodType } = this.state;
    const { formData, isFormLocked } = this.props;
    const {
      payment_method,
      issuer,
      payment_method_type,
      payment_network,
      max_payment_count,
      iins,
    } = formData;
    const { isEMI, isWallet, isCard, isNetBanking } = this.currentSelectedPaymentMethod;

    const PaymentMethodTypeOptions = isEMI ? EMI_CARDS_OPTIONS : CREDIT_DEBIT_CARDS_OPTIONS;

    let bankOptions = PaymentIssuersOptions;
    if (isEMI && selectedPaymentMethodType === 'debit') {
      bankOptions = EMI_DEBIT_CARD_BANK_OPTIONS;
    }

    return (
      <React.Fragment>
        <Input.Select
          required
          name="payment_method"
          label="Payment Method"
          options={PaymentMethodsOptions}
          placeholder="Select Payment Method"
          defaultValue={payment_method}
          validator={validatePaymentMethod}
          disabled={isFormLocked}
        />

        {isWallet && (
          <Input.Select
            name="issuer"
            label="Issuer"
            defaultValue={issuer}
            placeholder="Select Bank"
            options={WalletIssuersOptions}
            disabled={isFormLocked}
          />
        )}

        {(isCard || isEMI) && (
          <React.Fragment>
            <Input.Select
              name="payment_method_type"
              label="Card Type"
              defaultValue={payment_method_type}
              options={PaymentMethodTypeOptions}
              disabled={isFormLocked}
              onChange={this.onMethodTypeChange}
            />

            <Input.Select
              name="issuer"
              label="Bank"
              placeholder="Select Bank"
              defaultValue={issuer}
              disabled={isFormLocked}
              options={bankOptions}
            />

            <Input.Select
              label="Network"
              name="payment_network"
              placeholder="Select network"
              defaultValue={payment_network}
              disabled={isFormLocked}
              options={PaymentNetworksOptions}
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

        {isNetBanking && (
          <Input.Select
            label="Issuer"
            name="issuer"
            defaultValue={issuer}
            placeholder="Payment Instrument Issuer/Bank Name"
            options={PaymentIssuersOptions}
            disabled={isFormLocked}
          />
        )}
      </React.Fragment>
    );
  }
}
