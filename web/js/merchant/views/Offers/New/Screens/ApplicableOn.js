import React from 'react';
import {
  TextInput,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import { validatePaymentMethod, validateMaxPaymentCount } from 'merchant/views/Offers/New/helpers';
import {
  PAYMENT_METHODS,
  PaymentMethodsOptions,
  PaymentIssuersOptions,
  PaymentNetworksOptions,
  WalletIssuersOptions,
  CardLessEmiIssuersOptions,
  CREDIT_DEBIT_CARDS_OPTIONS,
  EMI_CARDS_OPTIONS,
  EMI_DEBIT_CARD_BANK_OPTIONS,
} from 'merchant/views/Offers/constants';
export default class ApplicableOn extends React.Component {
  state = { selectedPaymentMethodType: '' };

  get currentSelectedPaymentMethod() {
    const { payment_method } = this.props.values;
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
  handleFormChange = (name, value) => {
    this.props.setFieldTouched(name);
    this.props.setFieldValue(name, value);
  };
  onMethodTypeChange = (event) => {
    const { name, values } = event;
    this.setState({
      selectedPaymentMethodType: values && values.length > 0 ? values[0] : undefined,
    });
    this.handleFormChange(name, values[0]);
  };

  render() {
    const { selectedPaymentMethodType } = this.state;
    const { isFormLocked, values, errors, touched } = this.props;

    const { issuer, payment_network } = values;
    const { isEMI, isWallet, isCard, isNetBanking, isCardLessEmi } =
      this.currentSelectedPaymentMethod;

    const PaymentMethodTypeOptions = isEMI ? EMI_CARDS_OPTIONS : CREDIT_DEBIT_CARDS_OPTIONS;
    let bankOptions = PaymentIssuersOptions;
    if (isEMI && selectedPaymentMethodType === 'debit') {
      bankOptions = EMI_DEBIT_CARD_BANK_OPTIONS;
    }

    const isAmex = payment_network === 'AMEX';

    errors.payment_method = validatePaymentMethod(values.payment_method);
    errors.max_payment_count = validateMaxPaymentCount(values.max_payment_count);

    return (
      <React.Fragment>
        <Dropdown isDisabled={isFormLocked} marginBottom="spacing.7">
          <SelectInput
            isRequired
            necessityIndicator="required"
            label="Payment Method"
            placeholder="--Select Payment Method--"
            name="payment_method"
            labelPosition="left"
            value={values.payment_method}
            onChange={({ name, values }) => {
              this.handleFormChange(name, values[0]);
            }}
            validationState={touched.payment_method && errors?.payment_method ? 'error' : 'none'}
            errorText={errors?.payment_method}
          />
          <DropdownOverlay>
            <ActionList>
              {Object.values(PaymentMethodsOptions).map((type) => (
                <ActionListItem
                  key={type.name}
                  title={type.label}
                  value={type.name}
                  testID={`option-${type.name}`}
                />
              ))}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>

        {isWallet && (
          <Dropdown isDisabled={isFormLocked} marginTop="spacing.7">
            <SelectInput
              label="Issuer"
              placeholder="--Select Issuers--"
              name="issuer"
              labelPosition="left"
              validationState="none"
              value={values.issuer}
              onChange={({ name, values }) => {
                this.handleFormChange(name, values[0]);
              }}
            />
            <DropdownOverlay>
              <ActionList>
                {Object.values(WalletIssuersOptions).map((type) => (
                  <ActionListItem
                    key={type.name}
                    title={type.label}
                    value={type.name}
                    testID={`option-${type.name}`}
                  />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        )}

        {isCardLessEmi && (
          <Dropdown isDisabled={isFormLocked} marginTop="spacing.7">
            <SelectInput
              label="Issuer"
              placeholder="--Select Issuers--"
              name="issuer"
              labelPosition="left"
              defaultValue={issuer}
              validationState="none"
              value={values.issuer}
              onChange={({ name, values }) => {
                this.handleFormChange(name, values[0]);
              }}
            />
            <DropdownOverlay>
              <ActionList>
                {Object.values(CardLessEmiIssuersOptions).map((type) => (
                  <ActionListItem
                    key={type.name}
                    title={type.label}
                    value={type.name}
                    testID={`option-${type.name}`}
                  />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        )}

        {(isCard || isEMI) && (
          <React.Fragment>
            <Dropdown isDisabled={isFormLocked} marginBottom="spacing.7">
              <SelectInput
                label="Card Type"
                name="payment_method_type"
                labelPosition="left"
                onChange={(event) => this.onMethodTypeChange(event)}
                validationState="none"
                value={values.payment_method_type}
              />
              <DropdownOverlay>
                <ActionList>
                  {Object.values(PaymentMethodTypeOptions).map((type) => (
                    <ActionListItem
                      key={type.name}
                      title={type.label}
                      value={type.name}
                      testID={`option-${type.name}`}
                    />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>

            <Dropdown isDisabled={isFormLocked} marginBottom="spacing.7">
              <SelectInput
                label="Bank"
                placeholder="--Select Issuers--"
                name="issuer"
                labelPosition="left"
                validationState="none"
                value={values.issuer}
                onChange={({ name, values }) => {
                  this.handleFormChange(name, values[0]);
                }}
              />
              <DropdownOverlay>
                <ActionList>
                  {Object.values(bankOptions).map((type) => (
                    <ActionListItem
                      key={type.name}
                      title={type.label}
                      value={type.name}
                      testID={`option-${type.name}`}
                    />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>

            <Dropdown isDisabled={isFormLocked} marginBottom="spacing.7">
              <SelectInput
                label="Network"
                placeholder="--Select Network--"
                name="payment_network"
                labelPosition="left"
                validationState="none"
                value={values.payment_network}
                onChange={({ name, values }) => {
                  this.handleFormChange(name, values[0]);
                }}
              />
              <DropdownOverlay>
                <ActionList>
                  {Object.values(PaymentNetworksOptions).map((type) => (
                    <ActionListItem
                      key={type.name}
                      title={type.label}
                      value={type.name}
                      testID={`option-${type.name}`}
                    />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>

            <TextInput
              type="number"
              label="Max Usage Per Card"
              labelPosition="left"
              name="max_payment_count"
              placeholder="Max times a card can be used to avail this offer"
              helpText={isAmex && 'Max Usage on Amex will not work post tokenisation'}
              validationState={
                touched.max_payment_count && errors?.max_payment_count ? 'error' : 'none'
              }
              errorText={errors?.max_payment_count}
              isDisabled={isFormLocked || isAmex}
              marginBottom="spacing.7"
              value={values.max_payment_count}
              onChange={({ name, value }) => {
                this.handleFormChange(name, value);
              }}
            />

            <TextInput
              label="IINs"
              labelPosition="left"
              name="iins"
              placeholder="6 digit IINs for cards. Separated by comma if more than one"
              helpText={
                <p>
                  {'Note: Bin based offers on Amex saved card will not work post tokenisation.'}
                </p>
              }
              validationState="none"
              isDisabled={isFormLocked}
              marginBottom="spacing.7"
              value={values.iins}
              onChange={({ name, value }) => {
                this.handleFormChange(name, value);
              }}
            />
          </React.Fragment>
        )}

        {isNetBanking && (
          <Dropdown isDisabled={isFormLocked}>
            <SelectInput
              label="Issuer"
              placeholder="Payment Instrument Issuer/Bank Name"
              name="issuer"
              labelPosition="left"
              value={values.issuer}
              validationState="none"
              onChange={({ name, values }) => {
                this.handleFormChange(name, values[0]);
              }}
            />
            <DropdownOverlay>
              <ActionList>
                {Object.values(PaymentIssuersOptions).map((type) => (
                  <ActionListItem
                    key={type.name}
                    title={type.label}
                    value={type.name}
                    testID={`option-${type.name}`}
                  />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        )}
      </React.Fragment>
    );
  }
}
