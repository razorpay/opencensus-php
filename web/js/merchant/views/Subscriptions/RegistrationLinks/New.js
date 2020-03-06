import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { rupeesToPaise, titleCase } from 'common/utils/rzp-utils';
import fetchPaymentMethods from 'merchant/utils/fetchPaymentMethods';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { luminateRow } from 'merchant/reducers/app';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  saveInvoice,
  updatePLInReduxList,
} from 'merchant/reducers/invoices/list';
import { createRegistrationLink } from 'merchant/reducers/registration_link';

import Form from 'common/new-ui/Form';
import Spinner from 'common/ui/Spinner';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import { Modal, ModalContent } from 'common/new-ui/Modal';

import CustomerDetailsForm, {
  validatePhone,
  validateEmail,
} from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/CustomerDetails';
import PaymentDetailsForm, {
  checkIfAmount,
} from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/PaymentDetails';
import TokenDetailsForm from 'merchant/views/Subscriptions/RegistrationLinks/components/RegistrationLinksForm/TokenDetails';
import {
  trackClickPaymentMethod,
  trackClickNext,
  trackSkipBankDetails,
  trackSubmitCreateForm,
  trackCloseCreateForm,
} from './ga';

const CustomerDetailsMandatoryFields = [
  'description',
  {
    name: 'customerContact',
    validator: validatePhone,
  },
  {
    name: 'customerEmail',
    validator: validateEmail,
  },
];

const EmandateMandatoryFields = [
  'bankAccountIFSC',
  'bankName',
  'beneficiaryName',
  'bankAccountNumber',
];

const NACHMandatoryFields = [
  'accountType',
  'bankAccountIFSC',
  'beneficiaryName',
  'bankAccountNumber',
];

const PAYMENT_METHODS = {
  NACH: 'nach',
  EMANDATE: 'emandate',
  CARD: 'card',
};

const CardMandatoryFields = [{ name: 'amount', validator: checkIfAmount }];

let DEFAULT_MAX_AMOUNT = 99999;
let DEFAULT_FIRST_CHARGE = 0;

@withRouter
@connect(state => ({ user: state.session.user }), {
  openModal,
  closeModal,
  saveInvoice,
  updatePLInReduxList,
  showNotification,
  luminateRow,
  createRegistrationLink,
})
export default class CreateNewRegistrationLinkContainer extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      currentTab: 0,
      avlblMethods: [],
      emandateBanks: [],
      formFields: {
        hasNoExpiry: true,
        tokenHasNoExpiry: '1',
        customerName: '',
        customerEmail: '',
        customerContact: '',
        configSmsNotify: '',
        configEmailNotify: '',
        mandateMethod: '',
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
      },
      validTabs: [false, false, false],
    };
  }

  get isEmandatePayment() {
    return this.state.formFields.mandateMethod === 'emandate';
  }

  get isCardPayment() {
    return this.state.formFields.mandateMethod === 'card';
  }

  get isNACHPayment() {
    const isNACH = this.state.formFields.mandateMethod === 'nach';
    if (isNACH) {
      DEFAULT_MAX_AMOUNT = 100000;

      return isNACH;
    }

    DEFAULT_MAX_AMOUNT = 99999;
    return isNACH;
  }

  get currentSelectedMethod() {
    return {
      emandate: this.isEmandatePayment,
      card: this.isCardPayment,
      nach: this.isNACHPayment,
    };
  }

  get Tabs() {
    return getTabs(this.isEmandatePayment || this.isNACHPayment);
  }

  componentWillMount() {
    this.fetchDataForRegistrationLinks();
  }

  componentDidMount() {
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'registration_link');
      window.hj('tagRecording', ['registration_link_start']);
    }
  }

  setFormFields = (key, value) => {
    this.setState(currentState => ({
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

  handleDateChange = fieldName => date => {
    this.setFormFields(fieldName, Number(date.endOf('day').format('X')));
  };

  handleNotesChange = notes => {
    this.setFormFields('notes', notes);
  };

  changeTab = step => () => {
    const currentTab = this.state.currentTab + step;

    const validTabs = [...this.state.validTabs];
    validTabs[this.state.currentTab] = true;

    this.setState({ currentTab, validTabs }, () => {
      if (currentTab == 1) {
        trackClickNext('Customer details');
      }

      if (currentTab == 2) {
        trackClickNext('Payment details');
      }
    });
  };

  handleTabChange = ({ target }) => {
    const currentTab = Number(target.dataset.index);

    this.setState({ currentTab });
  };

  handleDisableTabCondition = tabIndex => {
    return tabIndex !== 0 && !this.state.validTabs[tabIndex - 1];
  };

  fetchDataForRegistrationLinks = () => {
    fetchPaymentMethods().then(methods => {
      if (methods && methods.recurring) {
        let emandateBanks = [];

        const avlblMethods = Object.keys(methods.recurring)
          .filter(methodName => methods.recurring[methodName])
          .map(method => ({
            label: titleCase(method),
            value: method,
          }));

        if (methods.recurring.emandate) {
          const emandates = methods.recurring.emandate || {};

          emandateBanks = Object.entries(emandates).map(([code, bank]) => ({
            label: bank.name,
            authTypes: bank.auth_types,
            name: code,
          }));
        }

        const mandateMethod =
          avlblMethods.length < 2 ? avlblMethods[0].value : '';

        this.setState({
          loading: false,
          emandateBanks,
          avlblMethods,
          mandateMethod,
        });
      }
    });
  };

  allMandatoryFieldsPresent = () => {
    let isAllFieldsPresent = true;

    this.Tabs.forEach((tab, idx) => {
      if (!this.isFormValid(idx)) {
        isAllFieldsPresent = false;

        return false;
      }
    });

    return isAllFieldsPresent;
  };

  prepareDataForRequest = () => {
    const data = { ...this.state.formFields },
      notes = data.notes.reduce(
        (otherNotes, { key, value }) => ({ ...otherNotes, [key]: value }),
        {}
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
        expire_at: !Number(data.tokenHasNoExpiry)
          ? data.mandateExpireAt
          : undefined,
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
          payload.subscription_registration.nach.form_reference1 =
            data.formReference1;
        }

        if (data.formReference2) {
          payload.subscription_registration.nach.form_reference2 =
            data.formReference2;
        }
      }

      payload.subscription_registration.auth_type = 'physical';
    }

    if (this.isEmandatePayment || this.isNACHPayment) {
      if (data.firstPaymentAmount) {
        payload.subscription_registration.first_payment_amount = rupeesToPaise(
          data.firstPaymentAmount
        );
      }

      let max_amount = rupeesToPaise(DEFAULT_MAX_AMOUNT);

      if (data.mandateMaxAmount) {
        max_amount = rupeesToPaise(data.mandateMaxAmount);
      }

      payload.subscription_registration.max_amount = max_amount;
    }

    return payload;
  };

  onCreate = () => {
    const payload = this.prepareDataForRequest();

    trackSubmitCreateForm(this.state.formFields.mandateMethod);

    return this.props
      .createRegistrationLink(payload)
      .then(response => {
        this.props.showNotification({
          type: 'success',
          message: 'Registration Link Successfully created',
          duration: 1000,
        });

        return response;
      })
      .then(response => {
        const entityId = response.id;

        if (this.props.onClose) {
          this.props.luminateRow(entityId);

          this.onClose();
        } else {
          const redirectUrl = '/registration_links/' + entityId;

          this.props.history.push(redirectUrl);
        }
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  isFormValid = (currentTab = this.state.currentTab) => {
    switch (currentTab) {
      case 0: {
        return CustomerDetailsMandatoryFields.every(type => {
          let value = this.state.formFields[type];

          if (type instanceof Object) {
            value = this.state.formFields[type.name];

            return value && !type.validator(value);
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
        }

        if (this.isCardPayment) {
          mandatoryFields = CardMandatoryFields;
        }

        if (this.isNACHPayment) {
          mandatoryFields = NACHMandatoryFields;
        }

        if (!mandatoryFields.length) {
          return isValid;
        }

        isValid = mandatoryFields.every(type => {
          let value =
            this.state.formFields[type] && this.state.formFields[type].length;

          if (type instanceof Object) {
            value = this.state.formFields[type.name];

            return value && !type.validator(value);
          }

          return value;
        });

        return isValid;
      }

      case 2: {
        return true;
      }
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
          />
        );
      }

      case 1: {
        return (
          <PaymentDetailsForm
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
            isEmandatePayment={this.isEmandatePayment}
            handleNotesChange={this.handleNotesChange}
            trackClickPaymentMethod={trackClickPaymentMethod}
            trackSkipBankDetails={trackSkipBankDetails}
            formReference1={formFields.formReference1}
            formReference2={formFields.formReference2}
            currentSelectedMethod={this.currentSelectedMethod}
          />
        );
      }

      case 2: {
        return (
          <TokenDetailsForm
            isFirstAmountHidden={this.props.user.isFirstAmountHidden}
            amount={formFields.amount}
            mandateExpireAt={formFields.mandateExpireAt}
            tokenHasNoExpiry={formFields.tokenHasNoExpiry}
            mandateMaxAmount={formFields.mandateMaxAmount}
            defaultMandateMaxAmount={DEFAULT_MAX_AMOUNT}
            defaultFirstChargeAmount={DEFAULT_FIRST_CHARGE}
            firstPaymentAmount={formFields.firstPaymentAmount}
            handleDateChange={this.handleDateChange}
          />
        );
      }
    }
  }

  renderWizard = () => {
    const { currentTab, validTabs, loading } = this.state,
      tabs = this.Tabs,
      isLastTab = currentTab === tabs.length - 1;

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
