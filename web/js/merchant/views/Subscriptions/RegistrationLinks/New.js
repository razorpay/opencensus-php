import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
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
import { isEmail, isPhone, isAmount, validateBeneficiaryName } from 'common/utils/validators';
import PaymentDetailsForm from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/PaymentDetails';
import TokenDetailsForm from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/TokenDetails';
import {
  trackClickPaymentMethod,
  trackClickNext,
  trackSkipBankDetails,
  trackSubmitCreateForm,
  trackCloseCreateForm,
} from './ga';
import analytics from '../analytics';

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

let DEFAULT_MAX_AMOUNT = 99999;
const DEFAULT_FIRST_CHARGE = 0;
const CARD_MAX_AMOUNT = 5000;
const GATEWAY_MAX_LIMIT = 200000;

const CardMandatoryFields = [
  {
    name: 'amount',
    validator: (value) => {
      return isAmount(value) && value <= GATEWAY_MAX_LIMIT && value >= 1;
    },
  },
];

const UPIMandatoryFields = [
  {
    name: 'amount',
    validator: (value) => {
      return isAmount(value) && value <= GATEWAY_MAX_LIMIT && value >= 1;
    },
  },
];

@withRouter
@connect((state) => ({ user: state.session.user }), {
  openModal,
  closeModal,
  saveInvoice,
  showNotification,
  luminateRow,
  createRegistrationLink,
})
@RTracking(() => window.rzpQ.component('CreateNewRegistrationLinkContainer'))
export default class CreateNewRegistrationLinkContainer extends React.Component {
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
        frequency: 'monthly',
      },
      validTabs: [false, false, false],
    };
  }

  get isEmandatePayment() {
    const isEmandate = this.state.formFields.mandateMethod === 'emandate';
    if (isEmandate) {
      DEFAULT_MAX_AMOUNT = 99999;
    }
    return isEmandate;
  }

  get isCardPayment() {
    return this.state.formFields.mandateMethod === 'card';
  }

  get isUPIPayment() {
    const isUPI = this.state.formFields.mandateMethod === 'upi';

    if (isUPI) {
      DEFAULT_MAX_AMOUNT = 200000;
    }

    return isUPI && this.props.user.isUPICAWEnabled;
  }

  get isNACHPayment() {
    const isNACH = this.state.formFields.mandateMethod === 'nach';
    if (isNACH) {
      DEFAULT_MAX_AMOUNT = 10000000;

      return isNACH;
    }

    DEFAULT_MAX_AMOUNT = 99999;
    return isNACH;
  }

  get Tabs() {
    return getTabs(
      this.isEmandatePayment || this.isNACHPayment || this.isUPIPayment || this.isCardPayment,
    );
  }

  componentWillMount() {
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

  handleChange = ({ target }) => {
    let value = target.value;

    if (target.type === 'checkbox') {
      value = target.checked;
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
        return { currentTab, validTabs };
      },
      () => {
        if (this.state.currentTab == 1) {
          trackClickNext('Customer details');
        }
        if (this.state.currentTab == 2) {
          trackClickNext('Payment details');
        }
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
        let emandateBanks = [];

        const avlblMethods = Object.keys(methods.recurring).filter((methodName) => {
          if (methodName === 'upi') {
            return methods.recurring[methodName] && this.props.user.isUPICAWEnabled;
          }

          return methods.recurring[methodName];
        });

        if (methods.recurring.emandate) {
          const emandates = methods.recurring.emandate || {};

          emandateBanks = Object.entries(emandates).map(([code, bank]) => ({
            label: bank.name,
            authTypes: bank.auth_types,
            name: code,
          }));
        }

        this.setState({
          loading: false,
          emandateBanks,
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
    const data = { ...this.state.formFields };
    const notes = data.notes.reduce(
      (otherNotes, { key, value }) => ({ ...otherNotes, [key]: value }),
      {},
    );

    const payload = {
      type: 'link',
      description: data.description,
      receipt: data.receipt,
      expire_by: !Number(data.hasNoExpiry) ? data.expireAt : undefined,
      currency: data.currency,
      amount: !!data.amount ? rupeesToPaise(data.amount) : 0,
      sms_notify: data.configSmsNotify,
      email_notify: data.configEmailNotify,
      notes: notes || undefined,
      customer: {
        name: data.customerName,
        contact: data.customerContact,
        email: data.customerEmail,
      },
      subscription_registration: {
        method: data.mandateMethod,
        expire_at: !Number(data.tokenHasNoExpiry) ? data.mandateExpireAt : undefined,
        bank_account: undefined,
      },
    };

    const bankAccountDetails = {
      ifsc_code: data.bankAccountIFSC,
      account_number: data.bankAccountNumber,
      beneficiary_name: data.beneficiaryName,
      account_type: data.accountType,
    };

    if (this.isEmandatePayment && !data.skipBankDetails) {
      bankAccountDetails.bank_name = data.bankName;

      payload.subscription_registration.bank_account = bankAccountDetails;
    }

    if (this.isNACHPayment) {
      payload.subscription_registration.bank_account = bankAccountDetails;

      if (data.formReference1 || data.formReference2) {
        payload.subscription_registration.nach = {};

        if (data.formReference1) {
          payload.subscription_registration.nach.form_reference1 = data.formReference1;
        }

        if (data.formReference2) {
          payload.subscription_registration.nach.form_reference2 = data.formReference2;
        }
      }

      payload.subscription_registration.auth_type = 'physical';
    }

    if (this.isEmandatePayment || this.isNACHPayment) {
      if (data.firstPaymentAmount) {
        payload.subscription_registration.first_payment_amount = rupeesToPaise(
          data.firstPaymentAmount,
        );
      }

      let max_amount = rupeesToPaise(DEFAULT_MAX_AMOUNT);

      if (data.mandateMaxAmount) {
        max_amount = rupeesToPaise(data.mandateMaxAmount);
      }

      payload.subscription_registration.max_amount = max_amount;
    }

    if (this.isUPIPayment) {
      // Frequency now support 'monthly' and 'as_presented'
      payload.subscription_registration.frequency = data.frequency;

      let max_amount = rupeesToPaise(DEFAULT_MAX_AMOUNT);

      if (data.mandateMaxAmount) {
        max_amount = rupeesToPaise(data.mandateMaxAmount);
      }
      payload.subscription_registration.max_amount = max_amount;
      payload.subscription_registration.bank_account = bankAccountDetails;
    }

    if (this.isCardPayment) {
      payload.subscription_registration.frequency = 'as_presented';
      const max_amount = rupeesToPaise(data.mandateMaxAmount || CARD_MAX_AMOUNT);
      payload.subscription_registration.max_amount = max_amount;
    }

    return payload;
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
        let isValid = false;

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
        } else if (this.isNACHPayment) {
          mandatoryFields = NACHMandatoryFields;
        }

        if (!mandatoryFields.length) {
          return isValid;
        }

        isValid = mandatoryFields.every((type) => {
          let value = this.state.formFields[type] && this.state.formFields[type].length;

          if (type instanceof Object) {
            value = this.state.formFields[type.name];

            return value && type.validator(value);
          }

          return value;
        });

        return isValid;
      }

      case 2: {
        if (this.isUPIPayment) {
          const fields = this.state.formFields;
          const maxAmount = rupeesToPaise(fields.mandateMaxAmount);
          const amount = rupeesToPaise(fields.amount);
          if (maxAmount > 20000000 || maxAmount < amount) {
            return false;
          }
        }
        if (this.isCardPayment) {
          const maxAmount = this.state.formFields.mandateMaxAmount;
          if (maxAmount > CARD_MAX_AMOUNT) {
            return false;
          }
        }
        return true;
      }

      default:
        return true;
    }
  };

  renderForm() {
    const { formFields } = this.state;

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
            handleTPV={this.handleTPV}
            notes={this.state.formFields.notes}
            isEsignEnabled={this.props.user.isEsignEnabled}
          />
        );
      }

      case 2: {
        return (
          <TokenDetailsForm
            isNACHPayment={this.isNACHPayment}
            isUPIPayment={this.isUPIPayment}
            isCardPayment={this.isCardPayment}
            isFirstAmountHidden={this.props.user.isFirstAmountHidden}
            amount={formFields.amount}
            frequency={formFields.frequency}
            mandateExpireAt={formFields.mandateExpireAt}
            tokenHasNoExpiry={formFields.tokenHasNoExpiry}
            mandateMaxAmount={formFields.mandateMaxAmount}
            defaultMandateMaxAmount={DEFAULT_MAX_AMOUNT}
            defaultFirstChargeAmount={DEFAULT_FIRST_CHARGE}
            firstPaymentAmount={formFields.firstPaymentAmount}
            handleDateChange={this.handleDateChange}
            onBlurElement={this.onBlurElement}
          />
        );
      }
      default: {
        return null;
      }
    }
  }

  renderWizard = () => {
    const { currentTab, validTabs, loading } = this.state;
    const tabs = this.Tabs;
    const isLastTab = currentTab === tabs.length - 1;

    return (
      <div class="ModalSingleForm RegistrationLinks--New Wizard">
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
          <React.Fragment>
            <main class="form-container">
              <main-title>Create Registration Link</main-title>

              <Form
                class="PaymentLinks--Create--Form"
                layout="tabular"
                onChange={this.handleChange}
                onSubmit={this.onCreate}
              >
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
          </React.Fragment>
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
        <Modal class="NewRegistrationLink animate-down" onClose={this.onClose}>
          <ModalContent>{this.renderWizard()}</ModalContent>
        </Modal>
      );
    }

    return <div class="StandAloneContainer">{this.renderWizard()}</div>;
  }
}

function getTabs(showTokenDetails) {
  const tabs = ['Customer Details', 'Payment Details'];

  if (showTokenDetails) {
    tabs.push('Token Details');
  }

  return tabs;
}
