import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { isEmail, isAmount, isPhone } from 'rzp/utils/validators';
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
import Input from 'component/Input';
import Button, { AsyncBtn } from 'component/Button';
import { Modal, ModalContent } from 'component/Modal';

import { AmountTooltip } from 'rzp/ui/Amount';

const mandatoryFields = [
  'description',
  'mandateMethod',
  'customerContact',
  'customerEmail',
];

const mandatoryBankFields = [
  'mandateBankName',
  'mandateBankAccountIFSC',
  'mandateBeneficiaryName',
  'mandateBankAccountNumber',
];

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
  state = {
    mandateMethod: '',
    hasNoExpiry: '1',
    tokenHasNoExpiry: '1',
    avlblMethods: [],
    loading: true,
    emandateBanks: [],
  };

  componentWillMount() {
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
  }

  allMandatoryFieldsPresent = () => {
    const { mandateMethod } = this.state;
    let actualMandatoryFields = [...mandatoryFields];

    if (mandateMethod === 'emandate' && !Number(this.state.skipBankDetails)) {
      actualMandatoryFields = [
        ...actualMandatoryFields,
        ...mandatoryBankFields,
      ];
    }

    const mandatoryFieldsPresent = actualMandatoryFields.every(
      field => !!this.state[field]
    );
    if (mandatoryFieldsPresent && mandateMethod === 'card') {
      return !!this.state.amount;
    }
    return mandatoryFieldsPresent;
  };

  handleChange = ({ target }) => {
    const value = target.value;
    const name = target.name || target.getAttribute('data-name');

    this.setState({ [name]: value });
  };

  handleDateChange = fieldName => date => {
    this.setState({
      [fieldName]: Number(date.endOf('day').format('X')),
    });
  };

  handleNotesChange = notes => {
    this.setState({ notes });
  };

  onCreate = () => {
    const data = { ...this.state };
    const notes =
      data.notes &&
      data.notes.reduce(
        (otherNotes, { key, value }) => ({ ...otherNotes, [key]: value }),
        {}
      );

    const payload = {
      type: 'link',
      description: data.description,
      receipt: data.receipt,
      expire_by: !Number(data.hasNoExpiry) ? data.expireAt : undefined,
      currency: data.currency,
      amount:
        data.mandateMethod === 'emandate' ? 0 : rupeesToPaise(data.amount),
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
          data.mandateMethod === 'emandate' && !!data.mandateMaxAmount
            ? rupeesToPaise(data.mandateMaxAmount)
            : undefined,
        auth_type: !data.skipBankDetails ? 'netbanking' : undefined, //hardcoded after aadhaar was disabled temporarily
        expire_at: !Number(data.tokenHasNoExpiry)
          ? data.mandateExpireAt
          : undefined,
        bank_account:
          data.mandateMethod === 'emandate' && !data.skipBankDetails
            ? {
                bank_name: data.mandateBankName,
                ifsc_code: data.mandateBankAccountIFSC,
                account_number: data.mandateBankAccountNumber,
                beneficiary_name: data.mandateBeneficiaryName,
                account_type: 'savings', // hardcoded after aadhaar was disabled temporarily
              }
            : undefined,
      },
    };

    if (data.mandateMethod === 'emandate') {
      payload.subscription_registration.first_payment_amount =
        rupeesToPaise(data.first_payment_amount) || 0;
    }

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

  renderForm = ({ isModalView }) => {
    return (
      <div class="PaymentLinks--Create Wizard">
        <main class="form-container">
          <main-title>Create Registration Link</main-title>

          <Form
            class="PaymentLinks--Create--Form"
            layout="tabular"
            onChange={this.handleChange}
            onSubmit={this.onCreate}
          />
        </main>
        <footer>
          {isModalView && <Button onClick={this.props.onClose}>Cancel</Button>}

          <AsyncBtn.Primary
            pendingState="Creating..."
            type="submit"
            onClick={this.onCreate}
            disabled={!this.allMandatoryFieldsPresent()}
          >
            Create Registration Link
          </AsyncBtn.Primary>
        </footer>
      </div>
    );
  };

  render() {
    const isModalView = this.props.onClose;

    return isModalView ? (
      <Modal class="PaymentLinks animate-down" onClose={this.props.onClose}>
        <ModalContent>{this.renderForm({ isModalView })}</ModalContent>
      </Modal>
    ) : (
      <div class="StandAloneContainer">{this.renderForm({ isModalView })}</div>
    );
  }
}

function PaymentMethod({ loading, avlblMethods }) {
  if (loading)
    return (
      <PaymentMethodPlaceHolder
        content={<span class="text-muted">Fetching Methods...</span>}
      />
    );
  return avlblMethods.length > 1 ? (
    <Input.Radio
      required
      label="Payment Method"
      name="mandateMethod"
      options={avlblMethods}
      class="Input--vTop"
      description="Method to be used for Registration Link"
    />
  ) : (
    <PaymentMethodPlaceHolder content={avlblMethods[0].label} />
  );
}

function PaymentMethodPlaceHolder({ content }) {
  return (
    <div class="Input Input--vTop">
      <div class="Input-label">Payment Method</div>
      <div class="Input-content">{content}</div>
    </div>
  );
}
