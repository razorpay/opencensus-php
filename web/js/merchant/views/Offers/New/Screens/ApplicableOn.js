import React from 'react';
import {
  TextInput,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  Checkbox,
  CheckboxGroup,
  RadioGroup,
  Radio,
  Divider,
} from '@razorpay/blade/components';

import { withSplitzService } from 'common/splitz';
import {
  validatePaymentMethod,
  validateMaxPaymentCount,
  validateUPIAppsList,
  isGranularPSPOfferEnabled,
  validatePayerAccountTypes,
  validateDiscountType,
} from 'merchant/views/Offers/New/helpers';
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
  OFFER_TYPES_OPTIONS,
  OFFER_TYPES,
  PAYER_ACCOUNT_TYPES_OPTIONS,
  UPI_APP_PROVIDERS,
  DISPLAY_TEXT,
  PAYER_ACCOUNT_TYPES_DISPLAY,
} from 'merchant/views/Offers/constants';
import { isGranularOfferExperimentEnabled } from 'merchant/views/Offers/utils';

import UPISelector, { UPI_APPS_SELECT_OPTIONS } from '../components/UPISelector';

class ApplicableOn extends React.Component {
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

  onPATChange = (e) => {
    const { name, values } = e;
    const { payerAccountTypes } = this.props.values;
    const isAllPayerAccountTypesChecked = values.includes(PAYER_ACCOUNT_TYPES_OPTIONS.ALL);
    let patValues = values;
    if (isAllPayerAccountTypesChecked) {
      patValues = PAYER_ACCOUNT_TYPES_OPTIONS.ALL;
    } else if (payerAccountTypes === PAYER_ACCOUNT_TYPES_OPTIONS.ALL) {
      patValues = [];
    }
    this.handleFormChange(name, patValues);
  };

  onMethodTypeChange = (event) => {
    const { name, values } = event;
    this.setState({
      selectedPaymentMethodType: values && values.length > 0 ? values[0] : undefined,
    });
    this.handleFormChange(name, values[0]);
  };

  resetGranularOffers = () => {
    this.props.setFieldValue('upiApps', UPI_APP_PROVIDERS.ALL);
    this.props.setFieldValue('upiAppsList', []);
    this.props.setFieldValue('payerAccountTypes', []);
    this.props.setFieldTouched('upiAppsList', false);
    this.props.setFieldTouched('payerAccountTypes', false);
  };

  onOfferTypeChange = ({ name, values }) => {
    const { type: previousType } = this.props.values;
    const newOfferType = values[0];
    if (previousType == newOfferType) return;

    if (newOfferType === OFFER_TYPES.Cashback) {
      this.resetGranularOffers();
    }
    this.handleFormChange(name, newOfferType);
  };

  onPaymentMethodChange = ({ name, values }) => {
    const { payment_method: previous_payment_method } = this.props.values;

    const newPaymentMethod = values[0];
    if (newPaymentMethod === previous_payment_method) return;

    if (newPaymentMethod === PAYMENT_METHODS.UPI) {
      this.resetGranularOffers();
    }
    this.handleFormChange(name, newPaymentMethod);
  };

  render() {
    const { splitz } = this.props;
    const { selectedPaymentMethodType } = this.state;
    const { isFormLocked, values, errors, touched, hideType } = this.props;

    const {
      issuer,
      payment_network,
      payment_method,
      type,
      payerAccountTypes,
      upiApps,
      upiAppsList,
    } = values;
    const { isEMI, isWallet, isCard, isNetBanking, isCardLessEmi, isUPI } =
      this.currentSelectedPaymentMethod;

    const PaymentMethodTypeOptions = isEMI ? EMI_CARDS_OPTIONS : CREDIT_DEBIT_CARDS_OPTIONS;
    let bankOptions = PaymentIssuersOptions;
    if (isEMI && selectedPaymentMethodType === 'debit') {
      bankOptions = EMI_DEBIT_CARD_BANK_OPTIONS;
    }

    const isAmex = payment_network === 'AMEX';
    const ALL_PAT_SELECTED = payerAccountTypes === PAYER_ACCOUNT_TYPES_OPTIONS.ALL;
    let valuesPAT = payerAccountTypes;
    if (isUPI && ALL_PAT_SELECTED) {
      valuesPAT = PAYER_ACCOUNT_TYPES_DISPLAY.map(({ name }) => name);
    }
    const isGranularOffer = isGranularPSPOfferEnabled(payment_method, type);

    errors.payment_method = validatePaymentMethod(payment_method);
    errors.max_payment_count = validateMaxPaymentCount(values.max_payment_count);
    errors.upiAppsList =
      isGranularOfferExperimentEnabled(splitz) &&
      isGranularOffer &&
      validateUPIAppsList(upiApps, upiAppsList);
    errors.payerAccountTypes =
      isGranularOfferExperimentEnabled(splitz) &&
      isGranularOffer &&
      validatePayerAccountTypes(values.payerAccountTypes);

    let offerTypeDescription;
    if (type === OFFER_TYPES.Cashback) {
      offerTypeDescription = DISPLAY_TEXT.APPLICABLE_ON.TYPE.HELP_TEXT;
    }

    hideType
      ? Object.fromEntries(Object.entries(errors).filter(([key]) => key !== 'type'))
      : (errors.type = validateDiscountType(values.type));
    return (
      <React.Fragment>
        {!hideType && (
          <Dropdown isDisabled={isFormLocked} marginBottom="spacing.7">
            <SelectInput
              label="Offer Type"
              placeholder="--Please select--"
              name="type"
              labelPosition="left"
              necessityIndicator="required"
              isRequired
              helpText={offerTypeDescription}
              value={type}
              onChange={this.onOfferTypeChange}
              validationState={touched?.type && errors?.type ? 'error' : 'none'}
              errorText={errors?.type}
            />
            <DropdownOverlay>
              <ActionList>
                {Object.values(OFFER_TYPES_OPTIONS).map((type) => (
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
        <Dropdown isDisabled={isFormLocked} marginBottom="spacing.7">
          <SelectInput
            isRequired
            necessityIndicator="required"
            label="Payment Method"
            placeholder="--Select Payment Method--"
            name="payment_method"
            labelPosition="left"
            value={values.payment_method}
            onChange={this.onPaymentMethodChange}
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

        {isGranularOfferExperimentEnabled(splitz) && isGranularOffer ? (
          <React.Fragment>
            <Divider margin="spacing.6" />
            <RadioGroup
              name="upiApps"
              value={upiApps}
              label="Applicable On"
              isRequired={true}
              marginTop="spacing.4"
              labelPosition="left"
              necessityIndicator="required"
              onChange={({ name, value }) => this.handleFormChange(name, value)}
              testID="upi-apps"
            >
              <Radio key="ALL" value={UPI_APPS_SELECT_OPTIONS[0].name}>
                {UPI_APPS_SELECT_OPTIONS[0].label}
              </Radio>
              <Radio key="selected" value="selected">
                Selected UPI Apps
              </Radio>
            </RadioGroup>

            {upiApps !== UPI_APP_PROVIDERS.ALL && (
              <UPISelector
                onChangeHandler={this.handleFormChange}
                selectedValues={upiAppsList}
                errorText={errors?.upiAppsList && touched?.upiAppsList ? errors.upiAppsList : ''}
              />
            )}
            <Divider margin="spacing.6" />

            <CheckboxGroup
              name="payerAccountTypes"
              onChange={this.onPATChange}
              value={valuesPAT}
              label="Payer Account Types"
              labelPosition="left"
              necessityIndicator="required"
              isRequired
              marginTop="margin.4"
              validationState={
                touched?.payerAccountTypes && errors?.payerAccountTypes ? 'error' : 'none'
              }
              errorText={errors?.payerAccountTypes}
              testID="payer-account-types"
            >
              {PAYER_ACCOUNT_TYPES_DISPLAY.map((app) => (
                <Checkbox
                  key={app.name}
                  value={app.name}
                  isDisabled={ALL_PAT_SELECTED && app.name !== PAYER_ACCOUNT_TYPES_OPTIONS.ALL}
                >
                  {app.label}
                </Checkbox>
              ))}
            </CheckboxGroup>
          </React.Fragment>
        ) : null}
      </React.Fragment>
    );
  }
}

export default withSplitzService(ApplicableOn);
