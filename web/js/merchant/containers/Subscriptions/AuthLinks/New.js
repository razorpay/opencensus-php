import { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { isEmail, isAmount, isPhone } from 'rzp/utils/validators';
import { merchantFetch } from 'merchant/utils/ajax';
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

import { rupeesToPaise } from 'rzp/utils/rzp-utils';

const methods = [
  { label: 'Card', value: 'card' },
  { label: 'Emandate', value: 'emandate' },
];

const accountTypes = [
  'Select Account Type...',
  { label: 'Savings', name: 'savings' },
  { label: 'Current', name: 'current' },
];

const authTypes = [
  'Select Auth Type...',
  { label: 'Netbanking', name: 'netbanking' },
  { label: 'Aadhar', name: 'aadhar' },
];

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
  'mandateAuthType',
  'mandateBankAccountType',
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
  };

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
      [fieldName]: date.format('X'),
    });
  };

  handleNotesChange = notes => {
    this.setState({ notes });
  };

  handleAuthTypeChange = event => {
    let mandateBankAccountType = '';
    if (event.target.value === 'netbanking') {
      mandateBankAccountType = 'savings';
    }
    this.setState({ mandateBankAccountType });
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
      expire_by: !data.hasNoExpiry && data.expireAt,
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
        auth_type:
          data.mandateMethod === 'emandate' && !data.skipBankDetails
            ? data.mandateAuthType
            : undefined,
        expire_at: data.mandateExpireAt,
        bank_account:
          data.mandateMethod === 'emandate' && !data.skipBankDetails
            ? {
                bank_name: data.mandateBankName,
                ifsc_code: data.mandateBankAccountIFSC,
                account_number: data.mandateBankAccountNumber,
                beneficiary_name: data.mandateBeneficiaryName,
                account_type: data.mandateBankName
                  ? data.mandateBankAccountType
                  : undefined,
              }
            : undefined,
      },
    };

    return this.props
      .createAuthLink(payload)
      .then(response => {
        if (response) {
          this.props.showNotification({
            type: 'success',
            message: 'Auth Link Successfully created',
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
    const method = this.state.mandateMethod;
    const skipBankDetails = !!Number(this.state.skipBankDetails);

    return (
      <div class="PaymentLinks--Create Wizard">
        <main class="form-container">
          <main-title>Create Auth Link</main-title>

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
                description="On this date link is expired"
                disabled={!!Number(this.state.hasNoExpiry)}
                onChange={this.handleDateChange('expireAt')}
                description="Expiry of Authentication Link"
              />
            </Input.Group>

            <Input.Radio
              required
              label="Payment Method"
              name="mandateMethod"
              options={methods}
              class="Input--vTop"
              description="Method to be used for Auth Link"
            />

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
                    <Input
                      name="mandateBankName"
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

                <Input.Group
                  label="Authentication"
                  class="InputGroup--inline"
                  disabled={skipBankDetails}
                >
                  <div class="Input-content">
                    <Input.Select
                      name="mandateAuthType"
                      options={authTypes}
                      size="half_big"
                      description="Preferred Authentication Method"
                      onChange={this.handleAuthTypeChange}
                    />

                    <Input.Select
                      name="mandateBankAccountType"
                      options={accountTypes}
                      size="half_big"
                      description="Type of Bank Account"
                      value={this.state.mandateBankAccountType}
                      disabled={
                        !skipBankDetails &&
                        this.state.mandateAuthType !== 'aadhar'
                      }
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
                    allowToday
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
                  name="mandateMaxAmount"
                  placeholder="100000"
                  label="Token Max Amount"
                  addonBefore="₹"
                  size="half_big"
                  validator={checkIfAmount}
                  description="Max Amount for Mandate"
                />
              </Fragment>
            )}

            {method === 'card' && (
              <Input
                name="amount"
                label="Amount"
                type="tel"
                placeholder="0.00"
                addonBefore="₹"
                description="Amount of Auth Link Payment"
                required
                validator={checkIfAmount}
              />
            )}

            <Input.PairList
              name="notes"
              label="Internal Notest"
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
            Create Auth Link
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

function checkIfAmount(value) {
  return !isAmount(Number(value)) && 'Invalid Amount';
}
