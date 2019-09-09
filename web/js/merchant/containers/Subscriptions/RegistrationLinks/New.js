import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { rupeesToPaise } from 'rzp/utils/rzp-utils';
import { titleCase } from 'common/util';
import fetchPaymentMethods from 'merchant/utils/fetchPaymentMethods';

import { closeModal } from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';
import { showNotification } from 'rzp/modules/notifications';
import {
  saveInvoice,
  updatePLInReduxList,
} from 'merchant/modules/invoices/list';
import { createRegistrationLink } from 'merchant/modules/registration_link';

import Form from 'component/Form';
import Spinner from 'rzp/ui/Spinner';
import Button, { AsyncBtn } from 'component/Button';
import { ModalAsideNav } from 'component/Wizard';
import { Modal, ModalContent } from 'component/Modal';

import CustomerDetailsForm, {
  validatePhone,
  validateEmail,
} from 'merchant/components/Subscriptions/RegistrationLinksForm/CustomerDetails';
import PaymentDetailsForm, {
  checkIfAmount,
} from 'merchant/components/Subscriptions/RegistrationLinksForm/PaymentDetails';
import TokenDetailsForm from 'merchant/components/Subscriptions/RegistrationLinksForm/TokenDetails';

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

const NACHMandatoryFields = [...EmandateMandatoryFields, 'accountType'];

const CardMandatoryFields = [{ name: 'amount', validator: checkIfAmount }];

@withRouter
@connect(null, {
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
        hasNoExpiry: '1',
        tokenHasNoExpiry: '1',
        mandateMethod: '',
        customerName: '',
        customerEmail: '',
        customerContact: '',
        configSmsNotify: '',
        configEmailNotify: '',
        isNachFormAval: '0',
        mandateMethod: '',
        bankName: '',
        skipBankDetails: '',
        beneficiaryName: '',
        bankAccountIFSC: '',
        bankAccountNumber: '',
        notes: [],
        skipBankDetails: false,
        accountType: '',
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
    return this.state.formFields.mandateMethod === 'nach';
  }

  get Tabs() {
    return getTabs(this.isEmandatePayment || this.isNACHPayment);
  }

  componentWillMount() {
    this.fetchDataForRegistrationLinks();
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

    this.setState({ currentTab, validTabs });
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

        const avlblMethods = Object.keys(methods.recurring).map(method => ({
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
      amount: this.isEmandatePayment ? 0 : rupeesToPaise(data.amount),
      sms_notify: data.configSmsNotify,
      email_notify: data.emailNotify,
      notes: notes || undefined,
      customer: {
        name: data.customerName,
        contact: data.customerContact,
        email: data.customerEmail,
      },
      subscription_registration: {
        method: data.mandateMethod,
        max_amount:
          this.isEmandatePayment && !!data.mandateMaxAmount
            ? rupeesToPaise(data.mandateMaxAmount)
            : undefined,
        auth_type: !data.skipBankDetails ? 'netbanking' : undefined, //hardcoded after aadhaar was disabled temporarily
        expire_at: !Number(data.tokenHasNoExpiry)
          ? data.mandateExpireAt
          : undefined,
        bank_account: undefined,
      },
    };

    if (
      (this.isEmandatePayment && !data.skipBankDetails) ||
      this.isNACHPayment
    ) {
      payload.subscription_registration.bank_account = {
        bank_name: data.bankName,
        ifsc_code: data.bankAccountIFSC,
        account_number: data.bankAccountNumber,
        beneficiary_name: data.beneficiaryName,
        account_type: data.accountType || 'savings', // hardcoded after aadhaar was disabled temporarily
      };
    }

    if (data.mandateMethod === 'emandate') {
      payload.subscription_registration.first_payment_amount =
        rupeesToPaise(data.firstPaymentAmount) || 0;
    }

    return payload;
  };

  onCreate = () => {
    const payload = this.prepareDataForRequest();

    return this.props
      .createRegistrationLink(payload)
      .then(response => {
        if (response) {
          this.props.showNotification({
            type: 'success',
            message: 'Registration Link Successfully created',
          });

          const entityId = response.id;

          if (this.props.onClose) {
            this.props.luminateRow(entityId);

            this.props.onClose(``);
          } else {
            const redirectUrl = '/registration_links/' + entityId;

            this.props.history.push(redirectUrl);
          }
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
            disabled={this.state.disabled}
            validateForm={this.validateForm}
            receipt={formFields.receipt}
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
            isNachFormAval={formFields.isNachFormAval}
            bankName={formFields.bankName}
            skipBankDetails={formFields.skipBankDetails}
            beneficiaryName={formFields.beneficiaryName}
            bankAccountIFSC={formFields.bankAccountIFSC}
            bankAccountNumber={formFields.bankAccountNumber}
            isCardPayment={this.isCardPayment}
            isNACHPayment={this.isNACHPayment}
            isEmandatePayment={this.isEmandatePayment}
            handleNotesChange={this.handleNotesChange}
          />
        );
      }

      case 2: {
        return (
          <TokenDetailsForm
            amount={formFields.amount}
            mandateExpireAt={formFields.mandateExpireAt}
            tokenHasNoExpiry={formFields.tokenHasNoExpiry}
            mandateMaxAmount={formFields.mandateMaxAmount}
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
      <div class="PaymentLinks--Create RegistrationLinks--New Wizard">
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
            <div className="page-center">
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

  render() {
    const isModalView = this.props.onClose;

    if (isModalView) {
      return (
        <Modal
          class="NewRegistrationLink animate-down"
          onClose={this.props.onClose}
        >
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
