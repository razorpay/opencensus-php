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
  ActionListItemIcon,
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
  PAYMENT_METHODS_OPTIONS,
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
  ALL_PRE_PAID_PAYMENT_METHODS,
  PAYMENT_TYPE_BUSINESS_KEYS_VS_METHODS,
  PAYMENT_METHOD_VS_ICON,
  PAYMENT_METHOD_OPTION_VS_TITLE,
  PAYMENT_METHODS_OPTIONS_WITHOUT_ALL,
} from 'merchant/views/Offers/constants';
import {
  isGranularOfferExperimentEnabled,
  getIsMultiPaymentMethodExperimentEnabled,
  getIs10DigitBinExperimentEnabled,
} from 'merchant/views/Offers/utils';

import UPISelector, { UPI_APPS_SELECT_OPTIONS } from '../components/UPISelector';

class ApplicableOn extends React.Component {
  state = { selectedPaymentMethodType: '' };

  get currentSelectedPaymentMethod() {
    const { selectedInstruments } = this.props.values;

    const isMultiplePaymentMethodSelected =
      Array.isArray(selectedInstruments) && selectedInstruments.length > 1;

    const selectedPaymentMethodsSet = new Set(selectedInstruments || []);

    return Object.keys(PAYMENT_TYPE_BUSINESS_KEYS_VS_METHODS).reduce(
      (acc, key) => ({
        ...acc,
        [key]: isMultiplePaymentMethodSelected
          ? false
          : selectedPaymentMethodsSet.has(PAYMENT_TYPE_BUSINESS_KEYS_VS_METHODS[key]),
      }),
      {},
    );
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
    const previousSelectedPaymentMethods = this.props.values.selectedInstruments || [];

    if (previousSelectedPaymentMethods === values) return;

    if (values.length === 1 && values[0] === PAYMENT_METHODS.UPI) {
      this.resetGranularOffers();
    }

    if (!getIsMultiPaymentMethodExperimentEnabled(this.props.splitz)) {
      this.handleFormChange(name, values);
      return;
    }

    let newPaymentMethodValues = [];

    const isAllPrepaidPaymentMethodSelected = values.includes(ALL_PRE_PAID_PAYMENT_METHODS);

    const isValueAdded = values.length > previousSelectedPaymentMethods.length;

    const getPaymentMethodWithAllAtLast = () => {
      return [
        ...PAYMENT_METHODS_OPTIONS.filter(
          (method) => method.name !== ALL_PRE_PAID_PAYMENT_METHODS,
        ).map((method) => method.name),
        ALL_PRE_PAID_PAYMENT_METHODS,
      ];
    };

    if (isAllPrepaidPaymentMethodSelected) {
      newPaymentMethodValues = isValueAdded
        ? getPaymentMethodWithAllAtLast()
        : values.filter((value) => value !== ALL_PRE_PAID_PAYMENT_METHODS);
    } else if (isValueAdded && PAYMENT_METHODS_OPTIONS.length - 1 === values.length) {
      newPaymentMethodValues = getPaymentMethodWithAllAtLast();
    } else if (values.length !== PAYMENT_METHODS_OPTIONS.length - 1) {
      newPaymentMethodValues = values;
    }

    this.handleFormChange(name, newPaymentMethodValues);
  };

  render() {
    const { splitz } = this.props;
    const isMultiPaymentOfferExperimentEnabled = getIsMultiPaymentMethodExperimentEnabled(splitz);
    const { selectedPaymentMethodType } = this.state;
    const { isFormLocked, values, errors, touched, hideType } = this.props;

    const {
      issuer,
      payment_network,
      selectedInstruments = [],
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
    const is10DigitBinExperimentEnabled = getIs10DigitBinExperimentEnabled(splitz);
    const isGranularOffer =
      selectedInstruments.length === 1 && isGranularPSPOfferEnabled(selectedInstruments[0], type);

    errors.selectedInstruments = validatePaymentMethod(selectedInstruments);
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

    const selectedInstrumentsValue = isMultiPaymentOfferExperimentEnabled
      ? selectedInstruments
      : selectedInstruments[0];

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
        <Dropdown
          isDisabled={isFormLocked}
          marginBottom="spacing.7"
          selectionType={isMultiPaymentOfferExperimentEnabled ? 'multiple' : 'single'}
        >
          <SelectInput
            isRequired
            necessityIndicator="required"
            label="Payment Method"
            placeholder="--Select Payment Method--"
            name="selectedInstruments"
            labelPosition="left"
            value={selectedInstrumentsValue}
            helpText={
              isMultiPaymentOfferExperimentEnabled &&
              selectedInstruments.length > 1 &&
              `Note: Applicable across all ${values.selectedInstruments
                .filter((method) => method !== ALL_PRE_PAID_PAYMENT_METHODS)
                .map((method) => PAYMENT_METHOD_OPTION_VS_TITLE[method])
                .join(', ')}`
            }
            onChange={this.onPaymentMethodChange}
            validationState={
              touched.selectedInstruments && errors?.selectedInstruments ? 'error' : 'none'
            }
            errorText={errors?.selectedInstruments}
          />
          <DropdownOverlay>
            <ActionList>
              {Object.values(
                isMultiPaymentOfferExperimentEnabled
                  ? PAYMENT_METHODS_OPTIONS
                  : PAYMENT_METHODS_OPTIONS_WITHOUT_ALL,
              ).map((type) => (
                <ActionListItem
                  key={type.name}
                  title={type.label}
                  value={type.name}
                  testID={`option-${type.name}`}
                  trailing={
                    isMultiPaymentOfferExperimentEnabled ? (
                      <ActionListItemIcon icon={PAYMENT_METHOD_VS_ICON[type.name]} />
                    ) : undefined
                  }
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
              placeholder={
                is10DigitBinExperimentEnabled
                  ? '6-9 digit IINs for cards'
                  : '6 digit IINs for cards. Separated by comma if more than one'
              }
              helpText={
                <p>
                  {is10DigitBinExperimentEnabled
                    ? 'Note: Offers will not apply after tokenization for Amex cards or for cards with 8+ digit BINs if the network BIN is less than 8 digits'
                    : 'Note: Bin based offers on Amex saved card will not work post tokenisation.'}
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
