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

@withRouter
@connect(null, {
  closeModal,
  saveInvoice,
  updatePLInReduxList,
  showNotification,
  luminateRow,
})
export default class CreateNewAuthLinkContainer extends Component {
  state = {
    mandateMethod: '',
    hasNoExpiry: '1',
    tokenHasNoExpiry: '1',
  };

  allMandatoryFieldsPresent = () => {
    const mandatoryFieldsPresent = mandatoryFields.every(
      field => !!this.state[field]
    );
    if (mandatoryFieldsPresent && this.state.mandateMethod === 'card') {
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

  onCreate = () => {
    const data = { ...this.state };
    const notes =
      data.notes &&
      data.notes.reduce(
        (otherNotes, { key, value }) => ({ ...otherNotes, [key]: value }),
        {}
      );

    const payload = {
      type: 'auth_link',
      description: data.description,
      receipt: data.receipt,
      // expire_by: data.expireAt,
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
      mandate: {
        method: data.mandateMethod,
        max_amount:
          data.mandateMethod === 'emandate'
            ? rupeesToPaise(data.mandateMaxAmount)
            : undefined,
        auth_type: data.mandateMethod === 'emandate' ? 'netbanking' : undefined,
        expire_at: data.mandateExpireAt,
        bank_account:
          data.mandateMethod === 'emandate'
            ? {
                bank_name: data.mandateBankName,
                ifsc_code: data.mandateBankAccountIFSC,
                account_number: data.mandateBankAccountNumber,
                beneficiary_name: data.mandateBeneficiaryName,
                account_type: data.mandateBankAccountType,
              }
            : undefined,
      },
    };

    return merchantFetch({
      url: 'invoices',
      method: 'post',
      data: payload,
    })
      .then(response => {
        if (response.data) {
          this.props.showNotification({
            type: 'success',
            message: 'Auth Link Successfully created',
          });

          const entityId = response.data.id;

          if (this.props.onClose) {
            this.props.updatePLInReduxList(response, true);
            this.props.luminateRow(entityId);

            this.props.onClose();
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
                <Input.Group label="Bank Details" class="InputGroup--inline">
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

                <Input label="Account Number" name="mandateBankAccountNumber" />

                <Input.Group label="Authentication" class="InputGroup--inline">
                  <div class="Input-content">
                    <Input.Select
                      name="mandateAuthType"
                      options={authTypes}
                      size="half_big"
                      description="Preferred Authentication Method"
                    />

                    <Input.Select
                      name="mandateBankAccountType"
                      options={accountTypes}
                      size="half_big"
                      disabled={this.state.mandateAuthType !== 'aadhar'}
                      description="Type of Bank Account"
                      value={
                        this.state.mandateAuthType === 'netbanking'
                          ? 'savings'
                          : this.state.mandateBankAccountType || ''
                      }
                    />
                  </div>
                </Input.Group>

                <Input.Group label="Token Expiry" class="InputGroup--vTop">
                  <Input.Check
                    fieldLabel="No Token Expiry"
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
