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
  SUBSCRIPTION_OFFERS_PAYMENT_METHODS,
  SUBSCRIPTION_OFFERS_PAYMENT_METHODS_OPTIONS,
  PaymentIssuersOptions,
  SUBSCRIPTION_OFFERS_PAYMENT_DC_ISSUERS_OPTIONS,
  SUBSCRIPTION_OFFERS_PAYMENT_NETWORKS_OPTIONS,
  CREDIT_DEBIT_CARDS_OPTIONS,
  CARD_TYPES,
} from 'merchant/views/Offers/constants';
export default class ApplicableOn extends React.Component {
  get currentSelectedPaymentMethod() {
    const { payment_method } = this.props.values;

    return {
      isCard: payment_method === SUBSCRIPTION_OFFERS_PAYMENT_METHODS.Card,
      isUPI: payment_method === SUBSCRIPTION_OFFERS_PAYMENT_METHODS.UPI,
    };
  }
  handleFormChange = (name, value) => {
    this.props.setFieldTouched(name);
    this.props.setFieldValue(name, value);
  };
  render() {
    const { isFormLocked, values, errors, touched } = this.props;
    const { payment_method_type } = values;
    const { isCard } = this.currentSelectedPaymentMethod;

    const isDebitCard = payment_method_type === CARD_TYPES.DEBIT;
    const BankOptions = isDebitCard
      ? SUBSCRIPTION_OFFERS_PAYMENT_DC_ISSUERS_OPTIONS
      : PaymentIssuersOptions;

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
              {Object.values(SUBSCRIPTION_OFFERS_PAYMENT_METHODS_OPTIONS).map((type) => (
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

        {isCard && (
          <React.Fragment>
            <Dropdown isDisabled={isFormLocked} marginBottom="spacing.7">
              <SelectInput
                label="Card Type"
                name="payment_method_type"
                labelPosition="left"
                validationState="none"
                value={values.payment_method_type}
                onChange={({ name, values }) => {
                  this.handleFormChange(name, values[0]);
                }}
              />
              <DropdownOverlay>
                <ActionList>
                  {Object.values(CREDIT_DEBIT_CARDS_OPTIONS).map((type) => (
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
                placeholder="--Select Bank--"
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
                  {Object.values(BankOptions).map((type) => (
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
                  {Object.values(SUBSCRIPTION_OFFERS_PAYMENT_NETWORKS_OPTIONS).map((type) => (
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
              validationState={
                touched.max_payment_count && errors?.max_payment_count ? 'error' : 'none'
              }
              errorText={errors?.max_payment_count}
              isDisabled={isFormLocked}
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
      </React.Fragment>
    );
  }
}
