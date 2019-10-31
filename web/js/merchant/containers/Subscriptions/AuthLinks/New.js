import { Component, Fragment } from 'react';
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
import { createAuthLink } from 'merchant/modules/auth_link';

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
  createAuthLink,
})
export default class CreateNewAuthLinkContainer extends Component {
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
      .createAuthLink(payload)
      .then(response => {
        if (response) {
          this.props.showNotification({
            type: 'success',
            message: 'Authorization Link Successfully created',
          });

          const entityId = response.id;

          if (this.props.onClose) {
            this.props.luminateRow(entityId);

            this.props.onClose(``);
          } else {
            const redirectUrl = '/authlinks/' + entityId;

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
    const { mandateMethod: method, avlblMethods, loading } = this.state;
    const skipBankDetails = !!Number(this.state.skipBankDetails);

    return (
      <div class="PaymentLinks--Create Wizard">
        <main class="form-container">
          <main-title>Create Authorization Link</main-title>

          <Form
            class="PaymentLinks--Create--Form"
            layout="tabular"
            onChange={this.handleChange}
            onSubmit={this.onCreate}
          >
            <Input.Textarea
              name="description"
              label="Description"
              description="Payment / Authentication Description"
              required
            />

            <Input
              name="customerName"
              label="Customer Name"
              description="Name of Customer"
            />

            <Input.Group
              class="InputGroup--inline"
              label="Customer Contact"
              required
            >
              <div class="Input-content">
                <Input
                  name="customerContact"
                  placeholder="Mobile"
                  type="tel"
                  size="half_big"
                  required
                  validator={val => !isPhone(val) && 'Invalid Phone'}
                  description="Phone number of Customer"
                />
                <Input
                  name="customerEmail"
                  placeholder="Email"
                  type="email"
                  size="half_big"
                  required
                  validator={val => !isEmail(val) && 'Invalid Email'}
                  description="Email of Customer"
                />
              </div>
            </Input.Group>

            <Input.Group
              class="InputGroup--inline InputGroup--vTop"
              label="Notify"
            >
              <div class="Input-content">
                <Input.Check name="configSmsNotify" fieldLabel="Via SMS" />
                <Input.Check name="configEmailNotify" fieldLabel="Via Email" />
              </div>
            </Input.Group>

            <Input
              name="receipt"
              size="half_big"
              label="Receipt No."
              description="Receipt for Customer"
            />

            <Input.Group label="Expiry" class="InputGroup--vTop">
              <Input.Check
                fieldLabel="No Expiry"
                data-name="hasNoExpiry"
                defaultValue="1"
              />

              <Input.ToCalendar
                name="expireAt"
                placeholder="DD-MM-YYYY"
                allowToday
                disablePastDates
                placement="topLeft"
                size="half_big"
                addonAfter={<i class="i i-date-range" />}
                disabled={!!Number(this.state.hasNoExpiry)}
                onChange={this.handleDateChange('expireAt')}
                description="Expiry of Authentication Link"
              />
            </Input.Group>

            <PaymentMethod loading={loading} avlblMethods={avlblMethods} />

            {method === 'emandate' && (
              <Fragment>
                <Input.Check
                  data-name="skipBankDetails"
                  fieldLabel="Skip Bank Details"
                />

                <Input.Group
                  label="Bank Details"
                  class="InputGroup--inline"
                  disabled={skipBankDetails}
                >
                  <div class="Input-content">
                    <Input.Select
                      name="mandateBankName"
                      options={['Select Bank', ...this.state.emandateBanks]}
                      size="half_big"
                      placeholder="Bank Name"
                      description="Preferred bank for authentication"
                    />

                    <Input
                      name="mandateBankAccountIFSC"
                      size="half_big"
                      placeholder="IFSC"
                      description="IFSC on the Bank Account"
                    />
                  </div>
                </Input.Group>

                <Input.Group
                  label="Account Details"
                  class="InputGroup--inline"
                  disabled={skipBankDetails}
                >
                  <div class="Input-content">
                    <Input
                      placeholder="Beneficiary Name"
                      name="mandateBeneficiaryName"
                      description="Customer/Beneficiary Name on the Account"
                      size="half_big"
                    />

                    <Input
                      placeholder="Account Number"
                      name="mandateBankAccountNumber"
                      description="Bank Account Number"
                      size="half_big"
                    />
                  </div>
                </Input.Group>

                <Input.Group label="Token Expiry" class="InputGroup--vTop">
                  <Input.Check
                    fieldLabel="Until cancelled"
                    data-name="tokenHasNoExpiry"
                    defaultValue="1"
                  />

                  <Input.ToCalendar
                    name="mandateExpireAt"
                    placeholder="Expiry (DD-MM-YYYY)"
                    disablePastDates
                    placement="topLeft"
                    size="half_big"
                    addonAfter={<i class="i i-date-range" />}
                    description="Expiry of Token"
                    onChange={this.handleDateChange('mandateExpireAt')}
                    disabled={!!Number(this.state.tokenHasNoExpiry)}
                  />
                </Input.Group>

                <Input
                  name="first_payment_amount"
                  type="number"
                  placeholder="0"
                  size="half_big"
                  label="Amount"
                  class="Input--Amount"
                  description="Amount of First Charge"
                  validator={value => {
                    return checkIfAmountForFirstCharge(
                      Number(this.state.mandateMaxAmount) || 99999,
                      value
                    );
                  }}
                  addonBefore={
                    <AmountTooltip
                      currency={'INR'}
                      parentQuerySelector=".Modal"
                    />
                  }
                />

                <Input
                  name="mandateMaxAmount"
                  placeholder="99999"
                  label="Token Max Amount"
                  addonBefore={
                    <AmountTooltip
                      currency={'INR'}
                      parentQuerySelector=".Modal"
                    />
                  }
                  size="half_big"
                  validator={checkIfAmount}
                  description="Max Amount for Mandate"
                  class="Input--Amount"
                />
              </Fragment>
            )}

            {method === 'card' && (
              <Input
                required
                name="amount"
                type="tel"
                placeholder="0.00"
                description="Amount of Authorization Link Payment"
                validator={checkIfAmount}
                size="half_big"
                label="Amount"
                class="Input--Amount"
              />
            )}

            <Input.PairList
              name="notes"
              label="Internal Notes"
              class="Input--vTop"
              onChange={this.handleNotesChange}
            />
          </Form>
        </main>
        <footer>
          {isModalView && <Button onClick={this.props.onClose}>Cancel</Button>}

          <AsyncBtn.Primary
            pendingState="Creating..."
            type="submit"
            onClick={this.onCreate}
            disabled={!this.allMandatoryFieldsPresent()}
          >
            Create Authorization Link
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
      description="Method to be used for Authorization Link"
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

// utils required
function checkIfAmount(value) {
  return !isAmount(Number(value)) && 'Invalid Amount';
}

const checkIfAmountForFirstCharge = (maxAmount, value) => {
  const amount = Number(value);

  if (amount === 0) {
    return null;
  }

  if (amount > maxAmount) {
    return 'Amount is should be less than or equal Token Max Amount';
  }

  return !isAmount(value) && 'Invalid Amount';
};
