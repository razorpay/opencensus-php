import React from 'react';
import moment from 'moment';

import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import RTracking from 'react-tracking';

import { rupeesToPaise } from 'common/utils/rzp-utils';
import fetchPaymentMethods from 'merchant/utils/fetchPaymentMethods';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { luminateRow } from 'merchant/reducers/app';
import { showNotification } from 'merchant_common/reducers/notifications';
import { saveInvoice } from 'merchant/reducers/invoices/list';
import { createRegistrationLink } from 'merchant/reducers/registration_link';

import Form from 'common/new-ui/Form';
import Spinner from 'common/ui/Spinner';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import DocsLink from 'merchant/components/DocsLink';

import CustomerDetailsForm from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/CustomerDetails';
import { isEmail, isPhone, validateBeneficiaryName } from 'common/utils/validators';
import PaymentDetailsForm from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/PaymentDetails';
import TokenDetailsForm from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/TokenDetails';
import {
  trackClickPaymentMethod,
  trackClickNext,
  trackSkipBankDetails,
  trackSubmitCreateForm,
  trackCloseCreateForm,
} from 'merchant/views/Subscriptions/RegistrationLinks/ga';
import analytics from 'merchant/views/Subscriptions/analytics';
import { isMobileDevice } from 'merchant/components/Home/data';
import { isAmountLiesInRange, isMonthlyDebitPattern } from 'merchant/views/Subscriptions/utils';
import {
  DEBIT_TYPES,
  FREQUENCY,
  MAX_TOKEN_AMOUNT,
  GATEWAY_MAX_LIMIT,
  CARD_AFA_MAX_AMOUNT,
  CARD_MAX_AMOUNT_ALLOWED,
  topEmandateBankCodes,
  MAX_TOKEN_AMOUNT_NACH,
  DEFAULT_NACH_LIMIT,
  DEFAULT_UPI_LIMIT,
  DEFAULT_EMANDATE_LIMIT,
} from 'merchant/views/Subscriptions/constants';

const CustomerDetailsMandatoryFields = [
  'description',
  {
    name: 'customerContact',
    validator: isPhone,
  },
  {
    name: 'customerEmail',
    validator: isEmail,
  },
];

const EmandateMandatoryFields = [
  'bankAccountIFSC',
  'bankName',
  {
    name: 'beneficiaryName',
    validator: validateBeneficiaryName,
  },
  'bankAccountNumber',
];

const NACHMandatoryFields = [
  'accountType',
  'bankAccountIFSC',
  {
    name: 'beneficiaryName',
    validator: validateBeneficiaryName,
  },
  'bankAccountNumber',
];

const PAYMENT_METHODS = {
  NACH: 'nach',
  EMANDATE: 'emandate',
  CARD: 'card',
};

let DEFAULT_MAX_AMOUNT = DEFAULT_EMANDATE_LIMIT;
const DEFAULT_FIRST_CHARGE = 0; // in Paisa

// gatewayMaxLimitValidator fn restrics the max gateway amount to be not greater than GATEWAY_MAX_LIMIT.
const gatewayMaxLimitValidator = (value) => isAmountLiesInRange(value, GATEWAY_MAX_LIMIT);

const CardMandatoryFields = [
  {
    name: 'amount',
    validator: gatewayMaxLimitValidator,
  },
];

const UPIMandatoryFields = [
  {
    name: 'amount',
    validator: gatewayMaxLimitValidator,
  },
];

const UPITPVMandatoryFields = [
  {
    name: 'amount',
    validator: gatewayMaxLimitValidator,
  },
  'bankAccountIFSC',
  {
    name: 'beneficiaryName',
    validator: validateBeneficiaryName,
  },
  'bankAccountNumber',
];

/* getTokenDetailFields fn validates tokendetails tab only for emandate & Nach payment methods
   The fn also ensures firstPaymentAmount is always lesser than or equal to the mandateMaxAmount
*/
const getTokenDetailFields = (maxAmount, isNach = false) => [
  {
    name: 'mandateMaxAmount',
    isOptional: true,
    validator: (value) =>
      isAmountLiesInRange(value, isNach ? MAX_TOKEN_AMOUNT_NACH : MAX_TOKEN_AMOUNT),
  },
  {
    name: 'firstPaymentAmount',
    isOptional: true,
    validator: (value) => isAmountLiesInRange(value, maxAmount, DEFAULT_FIRST_CHARGE), // TODO: TO check the first charge amount
  },
];

// eslint-disable-next-line react/no-unsafe

@connect((state) => ({ user: state.session.user, org: state.session.org }), {
  openModal,
  closeModal,
  saveInvoice,
  showNotification,
  luminateRow,
  createRegistrationLink,
})
@RTracking(() => window.rzpQ.component('CreateNewRegistrationLinkContainer'))
class NewRegistrationLink extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      currentTab: 0,
      avlblMethods: [],
      emandateBanks: [],
      isTPVEnabled: false,
      formFields: {
        hasNoExpiry: true,
        tokenHasNoExpiry: '1',
        customerName: '',
        customerEmail: '',
        customerContact: '',
        configSmsNotify: '',
        configEmailNotify: '',
        mandateMethod: null,
        bankName: '',
        beneficiaryName: '',
        bankAccountIFSC: '',
        bankAccountNumber: '',
        notes: [],
        expireAt: undefined,
        mandateExpireAt: undefined,
        skipBankDetails: false,
        accountType: '',
        formReference1: '',
        formReference2: '',
        frequency: FREQUENCY.AS_PRESENTED,
        currency: props.user.merchant.currency,
        recurringType: DEBIT_TYPES.BEFORE,
        recurringValue: 31,
        isValidRecurringValue: false,
      },
      validTabs: [false, false, false],
    };
    this.isMobileDevice = isMobileDevice();
    this.countryCode = props.user.merchant.country_code;
  }

  get isEmandatePayment() {
    const isEmandate = this.state.formFields.mandateMethod === 'emandate';
    if (isEmandate) {
      DEFAULT_MAX_AMOUNT = DEFAULT_EMANDATE_LIMIT;
    }
    return isEmandate;
  }

  get isCardPayment() {
    return this.state.formFields.mandateMethod === 'card';
  }

  get isUPIPayment() {
    const isUPI = this.state.formFields.mandateMethod === 'upi';

    if (isUPI) {
      DEFAULT_MAX_AMOUNT = DEFAULT_UPI_LIMIT;
    }

    return isUPI && this.props.user.isUPICAWEnabled;
  }

  get isTPVEnabledMerchant() {
    return this?.props?.user?.isTPVEnabled;
  }

  get isNACHPayment() {
    const isNACH = this.state.formFields.mandateMethod === 'nach';
    if (isNACH) {
      DEFAULT_MAX_AMOUNT = DEFAULT_NACH_LIMIT;

      return isNACH;
    }

    DEFAULT_MAX_AMOUNT = DEFAULT_EMANDATE_LIMIT;
    return isNACH;
  }

  get Tabs() {
    return getTabs(
      this.isEmandatePayment || this.isNACHPayment || this.isUPIPayment || this.isCardPayment,
    );
  }

  UNSAFE_componentWillMount() {
    this.fetchDataForRegistrationLinks();
  }

  componentDidMount() {
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'registration_link');
      window.hj('tagRecording', ['registration_link_start']);
    }

    this.trackRegistrationLinkCreation('initiate');
  }

  trackRegistrationLinkCreation = (event, options) => {
    if (!event) return;

    analytics.track(`registrationlink.create.${event}`, options);
  };

  setFormFields = (key, value) => {
    this.setState((currentState) => ({
      formFields: {
        ...currentState.formFields,
        [key]: value,
      },
    }));
  };

  handleRecurringValueChange = ({ target }) => {
    const { frequency } = this.state.formFields;
    const value = +target.value;

    if (frequency === FREQUENCY.WEEKLY && (value < 1 || value > 7)) {
      this.setFormFields('isValidRecurringValue', true);
    } else if (frequency === FREQUENCY.FORTNIGHTLY && (value < 1 || value > 15)) {
      this.setFormFields('isValidRecurringValue', true);
    } else if (isMonthlyDebitPattern(frequency) && (value < 1 || value > 31)) {
      this.setFormFields('isValidRecurringValue', true);
    } else {
      this.setFormFields('isValidRecurringValue', false);
    }
    this.setFormFields('recurringValue', value);
  };

  setDefaultDebitPattern = (value) => {
    if (value === FREQUENCY.WEEKLY) {
      this.setFormFields('recurringValue', 7);
    } else if (value === FREQUENCY.FORTNIGHTLY) {
      this.setFormFields('recurringValue', 15);
    } else {
      this.setFormFields('recurringValue', 31);
    }
    this.setFormFields('isValidRecurringValue', false);
  };

  handleChange = ({ target }) => {
    let value = target.value;

    if (target.type === 'checkbox') {
      value = target.checked;
    }
    if (target.name === 'frequency') {
      this.setDefaultDebitPattern(value);
    }

    if (target.name === 'mandateMethod' && value === PAYMENT_METHODS.EMANDATE) {
      this.setFormFields('accountType', 'savings');
    }

    this.setFormFields(target.name, value);
  };

  handleTPV = () => {
    this.setState((preState) => ({
      isTPVEnabled: !preState.isTPVEnabled,
    }));
  };

  onBlurElement = (event, dataName) => {
    const eventName = event ? event.target.getAttribute('data-name') : dataName;

    if (eventName === 'method') {
      this.trackRegistrationLinkCreation(eventName, {
        method: event.target.value,
      });

      return;
    }

    this.trackRegistrationLinkCreation(eventName);
  };

  handleDateChange = (fieldName) => (date) => {
    this.setFormFields(fieldName, Number(date.endOf('day').format('X')));
  };

  handleNotesChange = (notes) => {
    this.setFormFields('notes', notes);
  };

  handlePaymentMethod = ({ option }) => {
    this.setFormFields('mandateMethod', option);

    analytics.track('registrationlink.create.method', { method_type: option });
    trackClickPaymentMethod(option);
  };

  changeTab = (step) => () => {
    this.setState(
      (prevState) => {
        const currentTab = prevState.currentTab + step;
        const validTabs = [...prevState.validTabs];
        validTabs[prevState.currentTab] = true;
        return {
          currentTab,
          validTabs,
          formFields: {
            ...prevState.formFields,
            frequency: 'as_presented',
          },
        };
      },
      () => {
        if (this.state.currentTab == 1) {
          trackClickNext('Customer details');
        }
        if (this.state.currentTab == 2) {
          trackClickNext('Payment details');
        }
        this.setDefaultDebitPattern(this.state.frequency);
      },
    );
  };

  handleTabChange = ({ target }) => {
    const currentTab = Number(target.dataset.index);

    this.setState({ currentTab });
  };

  handleDisableTabCondition = (tabIndex) => {
    return tabIndex !== 0 && !this.state.validTabs[tabIndex - 1];
  };

  fetchDataForRegistrationLinks = () => {
    fetchPaymentMethods().then((methods) => {
      if (methods && methods.recurring) {
        const topEmandateBanks = [];
        const otherEmandateBanks = [];

        const avlblMethods = Object.keys(methods.recurring).filter((methodName) => {
          if (methodName === 'upi') {
            return methods.recurring[methodName] && this.props.user.isUPICAWEnabled;
          }

          return methods.recurring[methodName];
        });

        if (methods.recurring.emandate) {
          const emandates = methods.recurring.emandate || {};

          Object.entries(emandates).forEach(([code, bank]) => {
            const bankObj = {
              label: bank.name,
              authTypes: bank.auth_types,
              name: code,
            };

            if (topEmandateBankCodes.indexOf(code) > -1) {
              topEmandateBanks.push(bankObj);
            } else {
              otherEmandateBanks.push(bankObj);
            }
          });
        }

        this.setState({
          loading: false,
          emandateBanks: [...topEmandateBanks, ...otherEmandateBanks],
          avlblMethods,
        });
      }
    });
  };

  allMandatoryFieldsPresent = () => {
    let isAllFieldsPresent = true;

    this.Tabs.forEach((tab, idx) => {
      if (!this.isFormValid(idx)) {
        isAllFieldsPresent = false;
      }
    });

    return isAllFieldsPresent;
  };

  prepareDataForRequest = () => {
    const {
      notes,
      description,
      receipt,
      hasNoExpiry,
      expireAt,
      currency,
      amount,
      configSmsNotify,
      configEmailNotify,
      customerName,
      customerContact,
      customerEmail,
      mandateMethod,
      tokenHasNoExpiry,
      mandateExpireAt,
      bankAccountIFSC,
      bankAccountNumber,
      beneficiaryName,
      accountType,
      skipBankDetails,
      bankName,
      formReference1,
      formReference2,
      firstPaymentAmount,
      recurringType,
      recurringValue,
      frequency,
      mandateMaxAmount,
    } = { ...this.state.formFields };
    const combinedNotes = notes.reduce(
      (otherNotes, { key, value }) => ({ ...otherNotes, [key]: value }),
      {},
    );
    const cardAfaMaxLimit = CARD_AFA_MAX_AMOUNT[this.countryCode];

    const payload = {
      currency,
      description,
      receipt,
      type: 'link',
      expire_by: !Number(hasNoExpiry) ? expireAt : undefined,
      amount: !!amount ? rupeesToPaise(amount) : 0,
      sms_notify: configSmsNotify,
      email_notify: configEmailNotify,
      notes: combinedNotes || undefined,
      customer: {
        name: customerName,
        contact: customerContact,
        email: customerEmail,
      },
      subscription_registration: {
        method: mandateMethod,
        expire_at: !Number(tokenHasNoExpiry) ? mandateExpireAt : undefined,
        bank_account: undefined,
      },
    };

    const bankAccountDetails = {
      ifsc_code: bankAccountIFSC,
      account_number: bankAccountNumber,
      beneficiary_name: beneficiaryName,
      account_type: accountType,
    };

    if (this.isEmandatePayment && !skipBankDetails) {
      bankAccountDetails.bank_name = bankName;

      payload.subscription_registration.bank_account = bankAccountDetails;
    }

    if (this.isNACHPayment) {
      payload.subscription_registration.bank_account = bankAccountDetails;

      if (formReference1 || formReference2) {
        payload.subscription_registration.nach = {};

        if (formReference1) {
          payload.subscription_registration.nach.form_reference1 = formReference1;
        }

        if (formReference2) {
          payload.subscription_registration.nach.form_reference2 = formReference2;
        }
      }

      payload.subscription_registration.auth_type = 'physical';
    }

    let maxAmount = rupeesToPaise(DEFAULT_MAX_AMOUNT);
    if (this.isEmandatePayment || this.isNACHPayment) {
      if (firstPaymentAmount) {
        payload.subscription_registration.first_payment_amount = rupeesToPaise(firstPaymentAmount);
      }

      if (mandateMaxAmount) {
        maxAmount = rupeesToPaise(mandateMaxAmount);
      }

      payload.subscription_registration.max_amount = maxAmount;
      payload.subscription_registration.expire_at =
        mandateExpireAt || moment(moment().add(30, 'y'), 'X').unix();
    }

    if (this.isUPIPayment) {
      if (mandateMaxAmount) {
        maxAmount = rupeesToPaise(mandateMaxAmount);
      }

      // For UPI TPV param name is different
      if (this.isTPVEnabledMerchant) {
        delete bankAccountDetails.account_type;
        bankAccountDetails.name = bankAccountDetails.beneficiary_name;
        delete bankAccountDetails.beneficiary_name;
      }
      /**
       * Adds subscription registration data
       */
      payload.subscription_registration = {
        ...payload.subscription_registration,
        frequency,
        bank_account: bankAccountDetails,
        max_amount: maxAmount,
      };
      // debit pattern value is not expected in case of as_presented and daily frequency
      if (![FREQUENCY.AS_PRESENTED, FREQUENCY.DAILY].includes(frequency)) {
        payload.subscription_registration = {
          ...payload.subscription_registration,
          recurring_type: recurringType,
          recurring_value: recurringValue,
        };
      }
    }

    if (this.isCardPayment) {
      payload.subscription_registration.frequency = frequency;
      const cardMaxAmount = rupeesToPaise(mandateMaxAmount || cardAfaMaxLimit);
      payload.subscription_registration.max_amount = cardMaxAmount;
    }

    return payload;
  };

  checkIfFormValid = (fields = []) => {
    let isValid = false;
    if (!fields.length) {
      return isValid;
    }

    isValid = fields.every((type) => {
      let value = this.state.formFields[type] && this.state.formFields[type].length;
      if (type instanceof Object) {
        const isFieldOptional = type.isOptional;
        value = this.state.formFields[type.name];

        // Return true if field is optional and value is undefined
        if (!value && isFieldOptional) return true;

        return value && type.validator(value);
      }

      return value;
    });

    return isValid;
  };

  onCreate = () => {
    const payload = this.prepareDataForRequest();

    trackSubmitCreateForm(this.state.formFields.mandateMethod);

    this.trackRegistrationLinkCreation('issue');

    return this.props
      .createRegistrationLink(payload)
      .then((response) => {
        this.props.showNotification({
          type: 'success',
          message: 'Registration Link Successfully created',
          duration: 1000,
        });

        return response;
      })
      .then((response) => {
        const entityId = response.id;

        if (this.props.onClose) {
          this.props.luminateRow(entityId);

          this.props.onClose();
        } else {
          const redirectUrl = `/registration_links/${entityId}`;

          this.props.history.push(redirectUrl);
        }

        this.trackRegistrationLinkCreation('success');
        return null;
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });

        this.trackRegistrationLinkCreation('fail');
        return null;
      });
  };

  isFormValid = (currentTab = this.state.currentTab) => {
    const maxCardAmountAllowed = CARD_MAX_AMOUNT_ALLOWED[this.countryCode];

    switch (currentTab) {
      case 0: {
        return CustomerDetailsMandatoryFields.every((type) => {
          let value = this.state.formFields[type];

          if (type instanceof Object) {
            value = this.state.formFields[type.name];

            return value && type.validator(value);
          }

          return value;
        });
      }

      case 1: {
        let mandatoryFields = [];

        if (this.isEmandatePayment) {
          if (this.state.formFields.skipBankDetails) {
            return true;
          }

          mandatoryFields = EmandateMandatoryFields;
        } else if (this.isCardPayment) {
          mandatoryFields = CardMandatoryFields;
        } else if (this.isUPIPayment) {
          mandatoryFields = UPIMandatoryFields;
          if (this.isTPVEnabledMerchant) {
            mandatoryFields = UPITPVMandatoryFields;
          }
        } else if (this.isNACHPayment) {
          mandatoryFields = NACHMandatoryFields;
        }

        return this.checkIfFormValid(mandatoryFields);
      }

      case 2: {
        let tokenDetailFields = [];
        const { formFields: fields = {} } = this.state;
        let maxAmount = +fields.mandateMaxAmount;
        const amount = +fields.amount;
        const frequency = fields.frequency;
        const recurringValue = +fields.recurringValue;
        const maxAmountInPaisa = rupeesToPaise(maxAmount);
        const isCardMultipleFrequencyEnabled = this.props.user?.isCardMultipleFrequencyEnabled;
        const isDebitPatternEnabled = this.props.user?.isDebitPatternEnabled;

        const isWeeklyFrequency = frequency === FREQUENCY.WEEKLY;
        const isFortnightlyFrequency = frequency === FREQUENCY.FORTNIGHTLY;
        const isMonthlyRangeFrequency = isMonthlyDebitPattern(frequency);
        const isInWeeklyRange = recurringValue >= 1 && recurringValue <= 7;
        const isInFortnightlyRange = recurringValue >= 1 && recurringValue <= 15;
        const isInMonthlyRange = recurringValue >= 1 && recurringValue <= 31;
        const isCorrectWeeklyFrequency = isWeeklyFrequency && !isInWeeklyRange;
        const isCorrectFortnightlyFrequency = isFortnightlyFrequency && !isInFortnightlyRange;
        const isCorrectMonthly = isMonthlyRangeFrequency && !isInMonthlyRange;
        const isBreachingGatewayLimit = maxAmount > GATEWAY_MAX_LIMIT;
        const isBreachingMaxAmountLimit = maxAmount < amount;
        if (this.isUPIPayment) {
          if (!maxAmount && isDebitPatternEnabled) {
            return false;
          }
          if (!maxAmount) {
            maxAmount = DEFAULT_UPI_LIMIT;
          }
          if (
            isBreachingGatewayLimit ||
            isBreachingMaxAmountLimit ||
            isCorrectWeeklyFrequency ||
            isCorrectFortnightlyFrequency ||
            isCorrectMonthly
          ) {
            return false;
          }
        }
        if (this.isCardPayment) {
          if ((!maxAmount && isCardMultipleFrequencyEnabled) || maxAmount > maxCardAmountAllowed) {
            return false;
          }
        }
        if (this.isEmandatePayment) {
          tokenDetailFields = getTokenDetailFields(maxAmountInPaisa);
          return this.checkIfFormValid(tokenDetailFields);
        }
        if (this.isNACHPayment) {
          tokenDetailFields = getTokenDetailFields(maxAmountInPaisa, true);
          return this.checkIfFormValid(tokenDetailFields);
        }
        return true;
      }

      default:
        return true;
    }
  };

  renderForm() {
    const { formFields } = this.state;
    const { user, org } = this.props;
    const currency = user.merchant.currency;

    switch (this.state.currentTab) {
      case 0: {
        return (
          <CustomerDetailsForm
            isCustomerNameRequired={this.isNACHPayment}
            disabled={this.state.disabled}
            validateForm={this.validateForm}
            receipt={formFields.receipt}
            expireAt={formFields.expireAt}
            description={formFields.description}
            hasNoExpiry={formFields.hasNoExpiry}
            customerName={formFields.customerName}
            customerEmail={formFields.customerEmail}
            customerContact={formFields.customerContact}
            configSmsNotify={formFields.configSmsNotify}
            configEmailNotify={formFields.configEmailNotify}
            handleDateChange={this.handleDateChange}
            onBlurElement={this.onBlurElement}
          />
        );
      }

      case 1: {
        return (
          <PaymentDetailsForm
            showNACHAccountTypes={this.props.user.isRecurringMoreAccountType}
            showAmountField={this.props.user.isEmandateNonzeroAmountEnabled}
            amount={formFields.amount}
            accountType={formFields.accountType}
            avlblMethods={this.state.avlblMethods}
            mandateMethod={formFields.mandateMethod}
            emandateBanks={this.state.emandateBanks}
            bankName={formFields.bankName}
            skipBankDetails={formFields.skipBankDetails}
            beneficiaryName={formFields.beneficiaryName}
            bankAccountIFSC={formFields.bankAccountIFSC}
            bankAccountNumber={formFields.bankAccountNumber}
            isCardPayment={this.isCardPayment}
            isNACHPayment={this.isNACHPayment}
            isUPIPayment={this.isUPIPayment}
            isEmandatePayment={this.isEmandatePayment}
            handleNotesChange={this.handleNotesChange}
            trackSkipBankDetails={trackSkipBankDetails}
            formReference1={formFields.formReference1}
            formReference2={formFields.formReference2}
            onBlurElement={this.onBlurElement}
            handlePaymentMethod={this.handlePaymentMethod}
            isTPVEnabled={this.state.isTPVEnabled}
            showTPV={this.props.user.isCAWTPVEnabled}
            isTPVEnabledMerchant={this.isTPVEnabledMerchant}
            handleTPV={this.handleTPV}
            notes={this.state.formFields.notes}
            isEsignEnabled={this.props.user.isEsignEnabled}
            currency={currency}
          />
        );
      }

      case 2: {
        return (
          <TokenDetailsForm
            isNACHPayment={this.isNACHPayment}
            isUPIPayment={this.isUPIPayment}
            isCardPayment={this.isCardPayment}
            isEmandatePayment={this.isEmandatePayment}
            isFirstAmountHidden={this.props.user.isFirstAmountHidden}
            amount={formFields.amount}
            frequency={formFields.frequency}
            mandateExpireAt={formFields.mandateExpireAt}
            tokenHasNoExpiry={formFields.tokenHasNoExpiry}
            mandateMaxAmount={formFields.mandateMaxAmount}
            recurringValue={formFields.recurringValue}
            recurringType={formFields.recurringType}
            handleRecurringValueChange={this.handleRecurringValueChange}
            isValidRecurringValue={formFields.isValidRecurringValue}
            defaultMandateMaxAmount={DEFAULT_MAX_AMOUNT}
            defaultFirstChargeAmount={DEFAULT_FIRST_CHARGE}
            firstPaymentAmount={formFields.firstPaymentAmount}
            handleDateChange={this.handleDateChange}
            onBlurElement={this.onBlurElement}
            user={user}
            org={org}
          />
        );
      }
      default: {
        return null;
      }
    }
  }

  renderWizard = (isStandAlone = false) => {
    const { currentTab, validTabs, loading } = this.state;
    const tabs = this.Tabs;
    const isLastTab = currentTab === tabs.length - 1;

    return (
      <div class="Links--Create RegistrationLinks--New Wizard">
        <ModalAsideNav
          title="Create Registration Links"
          tabs={tabs}
          activeTab={currentTab}
          tabsValidity={validTabs}
          tabClickHandler={this.handleTabChange}
          disableTabCondition={this.handleDisableTabCondition}
        />
        {loading ? (
          <main>
            <div class="page-center">
              <Spinner />
            </div>
          </main>
        ) : (
          <>
            <main class="form-container">
              {(!this.isMobileDevice || isStandAlone) && (
                <main-title>Create Registration Link</main-title>
              )}

              <Form layout="tabular" onChange={this.handleChange} onSubmit={this.onCreate}>
                {this.renderForm()}
              </Form>
            </main>
            <footer>
              {this.props.user.isCardRecurringPaymentsBlocked && (
                <div class="card-blocked-banner">
                  <i class="i i-info-circle" /> Cards issued by Indian banks are temporarily
                  disabled for new registration links.{' '}
                  <DocsLink
                    url="https://razorpay.com/docs/announcements/rbi-card-mandate-guidelines/recurring-payments"
                    title="Learn more"
                  />
                </div>
              )}
              {currentTab > 0 && (
                <Button onClick={this.changeTab(-1)} type="button">
                  Previous
                </Button>
              )}
              {!isLastTab ? (
                <Button.Primary
                  onClick={this.changeTab(1)}
                  type="button"
                  disabled={!this.isFormValid()}
                >
                  Next
                </Button.Primary>
              ) : (
                <AsyncBtn.Primary
                  pendingState="Creating..."
                  type="submit"
                  onClick={this.onCreate}
                  disabled={!this.allMandatoryFieldsPresent()}
                >
                  Create Registration Link
                </AsyncBtn.Primary>
              )}
            </footer>
          </>
        )}
      </div>
    );
  };

  onClose = () => {
    trackCloseCreateForm();

    this.trackRegistrationLinkCreation('cancel');

    this.props.onClose();
  };

  render() {
    const isModalView = this.props.onClose;

    if (isModalView) {
      return (
        <Modal class="NewRegistrationLink animate-down" onClose={this.onClose} fullWidth>
          <ModalContent header={this.isMobileDevice ? this.Tabs[this.state.currentTab] : null}>
            {this.renderWizard(false)}
          </ModalContent>
        </Modal>
      );
    }

    return <div class="StandAloneContainer">{this.renderWizard(true)}</div>;
  }
}

function getTabs(showTokenDetails) {
  const tabs = ['Customer Details', 'Payment Details'];

  if (showTokenDetails) {
    tabs.push('Token Details');
  }

  return tabs;
}

export default withRouter(NewRegistrationLink);
