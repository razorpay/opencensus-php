import { Component } from 'react';
import { connect } from 'react-redux';
import { TypeAhead } from 'react-power-select';
import AsyncButton from 'react-async-button';
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form';

import { findBy } from 'common/utils/rzp-utils';

import ModalHeader from 'common/ui/ModalHeader';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import QuickAddComponent from 'common/ui/Select/QuickAdd';

import Input, { Label, Description } from 'common/new-ui/Input';

import { closeModal } from 'merchant_common/reducers/modals';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { luminateRow } from 'merchant/reducers/app';
import { fetchCustomersForAutocomplete } from 'merchant/reducers/customers';
import { saveVirtualAccount } from 'merchant/reducers/virtualaccounts';

import CustomerCreation from 'merchant/containers/Customers/New';

import NotesFieldArray from 'merchant/components/NotesFieldArray';

@connect(
  state => {
    const customers = state.customers.items;

    return {
      customers,
      notes: selector(state, 'notes'),
      close_by: selector(state, 'close_by'),
      descriptor: selector(state, 'descriptor'),
      customersLoading: state.customers.loading,
      customer: findBy(customers, 'id', selector(state, 'customer_id')),
      initialValues: {
        receivers: {
          types: ['bank_account'],
          notes: [],
        },
      },
      ...state.config.config,
    };
  },
  {
    closeModal,
    luminateRow,
    showNotification,
    saveVirtualAccount,
    fetchCustomersForAutocomplete,
    ...ModalActions,
  }
)
@reduxForm({
  form: 'createVirtualAccount',
})
export default class CreateVirtualAccount extends Component {
  state = {
    internals: {
      __no_expiry: true,
    },
  };

  componentWillMount() {
    this.props.fetchCustomersForAutocomplete();
  }

  componentDidMount() {
    this.props.onMount && this.props.onMount();
  }

  componentWillUnmount() {
    this.props.onUnmount && this.props.onUnmount();
  }

  componentWillReceiveProps(nextProps) {
    // Prepoluate field (Just to display in customer selection. Actual value is props.customer_id, and it's already init through redux-form)
    if (!this.state.customerId && this.props.customer !== nextProps.customer) {
      this.setState({
        customerId: nextProps.customer,
      });
    }
  }

  save = ({ numeric, descriptor, notes, receivers, ...props }) => {
    let transformedNotes = notes;

    if (transformedNotes && transformedNotes.length > 0) {
      transformedNotes = transformedNotes.reduce((result, current) => {
        result[current.key] = current.value;
        return result;
      }, {});
    }

    return this.props
      .saveVirtualAccount({
        ...props,
        notes: transformedNotes,
        receivers: {
          ...receivers,
          bank_account: descriptor
            ? {
                descriptor,
              }
            : undefined,
        },
      })
      .then(virtualAccount => {
        this.props.onCreateVA && this.props.onCreateVA(props);
        this.props.luminateRow(virtualAccount.id);
        this.setState({ virtualAccount });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  selectCustomerAndCloseModal = customer => {
    this.props.change('customer_id', customer.id);
    this.props.closeModal();
    setTimeout(this.props.showCreateVAModal, 500); // To open create virtual account modal automatically with pre-selected customer name
  };

  quickCreateCustomer = ({ searchTerm = '' }) => {
    this.props.openModal({
      size: 'small',
      component: (
        <CustomerCreation
          saveLabel="Create and add this customer"
          onSave={this.selectCustomerAndCloseModal}
          customer={{
            name: searchTerm,
          }}
        />
      ),
    });
  };

  handleSelect = ({ option }) => {
    // For setting in redux-form
    if (option) {
      this.props.change('customer_id', option.id);
    } else {
      this.props.untouch('createVirtualAccount', 'customer_id');
    }

    // For display purpose only in TypeAhead
    this.setState({ customerId: option });
  };

  handleDateChange = selectedDate => {
    const fieldName = 'close_by';

    selectedDate.startOf('day');

    const current = this.props[fieldName]
      ? moment(this.props[fieldName], 'X')
      : 0;

    const time = current
      ? Number(current.format('X')) - Number(current.startOf('day').format('X'))
      : 0;

    const value = Number(selectedDate.format('X')) + time;

    this.props.change(fieldName, value);
  };

  handleTimeChange = selectedDate => {
    let fieldName = 'close_by_time';

    const time =
      Number(selectedDate.format('X')) -
      Number(selectedDate.startOf('day').format('X'));
    fieldName = fieldName.replace('_time', '');

    let current = this.props[fieldName];

    // adding time to current day
    current = Number(
      moment(current, 'X')
        .startOf('day')
        .format('X')
    );

    this.props.change(fieldName, current + time);
  };

  handleNoExpiry = () => {
    this.setState({
      internals: {
        __no_expiry: !this.state.internals.__no_expiry,
      },
    });
  };

  render() {
    const {
      untouch,
      close_by,
      handle = '',
      handleSubmit,
      customers = [],
      descriptor = '',
      customersLoading,
      onCopy = () => {},
    } = this.props;

    const { virtualAccount, internals } = this.state;

    let descriptorLimit;

    if (handle) {
      if (handle.length === 3) {
        descriptorLimit = 10;
      } else if (handle.length === 4) {
        descriptorLimit = 9;
      }
    }

    const dateInMoment = undefined;

    return (
      <div>
        <ModalHeader
          size="small"
          title={
            virtualAccount
              ? 'Virtual Account Created'
              : 'Create Virtual Account'
          }
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          {virtualAccount ? (
            <VirtualAccountDetails
              virtualAccount={virtualAccount}
              onCopy={onCopy}
            />
          ) : (
            <form onSubmit={handleSubmit(this.save)}>
              <div class="form-group">
                <label>Customer (Optional)</label>
                <TypeAhead
                  options={customers}
                  disabled={customersLoading}
                  class="ps-in-modal"
                  searchIndices={['id', 'name', 'email', 'contact']}
                  placeholder={`${
                    customersLoading ? 'Loading...' : 'Select a customer'
                  }`}
                  showClear={true}
                  selected={this.state.customerId}
                  selectedOptionLabelPath="selectedDisplayName"
                  optionComponent={({ option }) => {
                    return (
                      <div class="custom-powerselect-options">
                        {option.name && <b>{option.name} : </b>}
                        {option.email || option.contact}
                      </div>
                    );
                  }}
                  onChange={this.handleSelect}
                  afterOptionsComponent={select => (
                    <QuickAddComponent
                      {...select}
                      onClick={this.quickCreateCustomer}
                    />
                  )}
                />
              </div>

              <div class="form-group">
                <label>Account Description (Optional)</label>
                <Field
                  name="description"
                  class="form-control"
                  component="input"
                  required={true}
                />
                <small class="help-block">
                  Account description is only displayed on the dashboard and is
                  not shared with the customer.
                </small>
              </div>

              {!!handle && (
                <div class="form-group">
                  <label>Descriptor (Optional)</label>
                  <Field
                    name="descriptor"
                    component="input"
                    class="form-control"
                    placeholder={`Accepts alphanumberic, upto ${descriptorLimit} chars`}
                    normalize={value => value.toUpperCase()}
                    onChange={event => {
                      let value = event.target.value;
                      let regex = new RegExp(
                        `^[a-z0-9]{0,${descriptorLimit}}$`,
                        'i'
                      );

                      if (regex.test(value)) {
                        this.props.change('descriptor', value);
                      } else {
                        event.preventDefault();
                      }
                    }}
                  />
                  <small class="help-block">
                    Descriptor will be a part of the account number generated.
                  </small>
                </div>
              )}

              <div class="form-group">
                <label>Close By (Optional)</label>

                <Input.Check
                  class="Input--vTop"
                  fieldLabel="Disable Auto Close"
                  data-name="__no_expiry"
                  checked={internals.__no_expiry}
                  onChange={this.handleNoExpiry}
                />

                <Input.Group class="InputGroup--inline InputGroup--near m-b">
                  <div class="Input-content">
                    <Input.ToCalendar
                      readOnly
                      allowToday
                      size="half"
                      name="close_by"
                      disablePastDates
                      placement="topLeft"
                      placeholder="DD-MM-YYYY"
                      defaultValue={dateInMoment}
                      onChange={this.handleDateChange}
                      disabled={internals.__no_expiry}
                      addonAfter={<i class="i i-date-range" />}
                    />
                    {close_by && (
                      <Input.TimePicker
                        readOnly
                        size="half"
                        name="close_by_time"
                        placeholder="HH:MM A"
                        defaultValue={dateInMoment}
                        disabled={internals.__no_expiry}
                        onChange={this.handleTimeChange}
                        addonAfter={<i class="i i-time" />}
                      />
                    )}
                  </div>
                </Input.Group>

                <small class="help-block">
                  If specified, Virtual Account will auto close at the specified
                  time.
                </small>
              </div>

              <br />

              <div class="form-group">
                <label class="notes-label">Internal Notes</label>
                <FieldArray
                  name="notes"
                  component={NotesFieldArray}
                  showLinkedAccountOpt={false}
                />
              </div>

              <div class="Modal__actions clearfix">
                <AsyncButton
                  className="btn btn-primary btn-block"
                  text="Create Virtual Account"
                  pendingText="Creating..."
                  onClick={handleSubmit(this.save)}
                />
              </div>
            </form>
          )}
        </div>
      </div>
    );
  }
}

const VirtualAccountDetails = ({ virtualAccount, onCopy }) => {
  let bankAccount = virtualAccount.receivers[0];
  return (
    <div>
      <p class="text-muted">
        Share the following information with the customer to accept payments
      </p>

      <div class="form-group">
        <div class="text-muted">Account Number</div>
        <div>
          <b>{bankAccount.account_number}</b>
        </div>
      </div>

      <div class="form-group">
        <div class="text-muted">Beneficiary Name</div>
        <div>
          <b>{virtualAccount.name}</b>
        </div>
      </div>

      <div class="form-group">
        <div class="text-muted">IFSC Code</div>
        <div>
          <b>{bankAccount.ifsc}</b>
        </div>
      </div>

      {virtualAccount.close_by && (
        <div class="form-group">
          <div class="text-muted">Close By</div>
          <div>
            <b>
              {moment(virtualAccount.close_by * 1000).format(
                'DD MMM YYYY, hh:mm:ss a'
              )}
            </b>
          </div>
        </div>
      )}

      <CustomClipboard
        value={`Account Number: ${
          bankAccount.account_number
        }\nBeneficiary Name: ${virtualAccount.name}\nIFSC: ${bankAccount.ifsc}`}
        onCopy={() => {
          onCopy(virtualAccount);
        }}
      >
        <button type="button" class="btn btn-primary btn-block">
          Copy details to Clipboard
        </button>
      </CustomClipboard>
    </div>
  );
};

const selector = formValueSelector('createVirtualAccount');
