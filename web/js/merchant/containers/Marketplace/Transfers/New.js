import AsyncButton from 'react-async-button';
import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form';
import moment from 'moment';
import { TypeAhead } from 'react-power-select';
import { withRouter } from 'react-router-dom';

import { showWhenUtil } from 'merchant/components/ShowWhen';

import { prefixEntityValue } from 'common/data';

import Alert from 'rzp/ui/Forms/Alert';
import DatePickerField from 'rzp/ui/Forms/DatePickerField';
import InputGroupField from 'rzp/ui/Forms/InputField/InputGroupField';
import { required } from 'rzp/utils/validators';
import { showNotification } from 'rzp/modules/notifications';
import { titleCase, rupeesToPaise } from 'rzp/utils/rzp-utils';

import { fetchAccounts } from 'merchant/modules/marketplace/accounts';
import FormItem from 'merchant/components/FormItem';
import NotesFieldArray from 'merchant/components/NotesFieldArray';
import { createTransfer } from 'merchant/modules/payments/details';
import { isHoliday, nextWorkingDay } from 'rzp/utils/bankHolidays';
import RadioButton from 'rzp/ui/Forms/RadioButton';

let Label = ({ text, htmlFor, required }) => {
  var classes = typeof required !== 'undefined' ? 'label-required' : '';

  return (
    <div class="pair-label">
      <label for={htmlFor} class={classes}>
        {text}
      </label>
    </div>
  );
};

const selector = formValueSelector('createPaymentTransfer');

@connect(
  state => {
    return {
      accounts: state.accounts,
      onHold: selector(state, 'onHold'),
      holdUntil: selector(state, 'holdUntil'),
      notes: selector(state, 'notes'),
      amount: selector(state, 'amount'),
      accountId: state.accountId,
    };
  },
  {
    createTransfer,
    showNotification,
    fetchAccounts,
  }
)
@reduxForm({
  form: 'createPaymentTransfer',
  initialValues: {
    onHold: 'false',
    notes: [],
    account_id: null,
    holdUntil: null,
  },
})
@withRouter
export default class TransferNew extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {
    selectedAccount: null,
    accountId: null,
  };

  componentWillMount() {
    if (this.props.plan) {
      this.props.initialize(this.props.plan);
    }

    this.props.fetchAccounts({});
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.onHold !== 'on_hold_until' && nextProps.holdUntil) {
      this.props.change('holdUntil', null);
    }
  }

  showTransferCreationError(errors) {
    return this.props.showNotification({
      type: 'error',
      message: errors,
      closeTimeout: 5000,
    });
  }

  save = props => {
    if (!this.state.selectedAccount) {
      return this.props.showNotification({
        type: 'error',
        message: 'Please select an Account',
      });
    }

    const { onHold, holdUntil, notes, amount } = props,
      accountId = this.state.selectedAccount.id;

    if (onHold === 'on_hold_until' && !holdUntil) {
      return this.props.showNotification({
        type: 'error',
        message: 'Please select a date',
      });
    }

    let transformedNotes = notes;
    const linked_account_notes = [];

    if (notes && notes.length > 0) {
      transformedNotes = notes.reduce((result, current) => {
        result[current.key] = current.value;
        if (this.isLADashboardEnabled && current.also_linked_account) {
          linked_account_notes.push(current.key);
        }
        return result;
      }, {});
    }

    if (this.isLADashboardEnabled) {
      transformedNotes.linked_account_notes = linked_account_notes;
    }

    let holdData = {};

    if (this.props.onHold !== 'false') {
      holdData.on_hold = 1;

      if (this.props.onHold === 'on_hold_until') {
        holdData.on_hold_until =
          moment(this.props.holdUntil * 1000)
            .startOf('day')
            .toDate() / 1000;
        holdData.on_hold_until = holdData.on_hold_until - 600;
      }
    } else {
      holdData.on_hold = 0;
    }

    return createTransfer({
      id: this.props.paymentId,
      transfers: [
        {
          account: prefixEntityValue('account', accountId),
          amount: rupeesToPaise(amount),
          notes: transformedNotes,
          currency: 'INR',
          ...holdData,
        },
      ],
    }).then(
      data => {
        this.props.showNotification({
          type: 'success',
          message: 'Transfer created Successfully',
        });

        this.setState({
          selectedAccount: null,
          accountId: null,
        });

        this.props.reset();

        if (typeof this.props.onCreate === 'function') {
          this.props.onCreate();
        }
      },
      ({ errors }) => this.showTransferCreationError(errors)
    );
  };

  handleSelect = ({ option }) => {
    this.typeAheadSkin.classList.remove('hide');

    if (option) {
      this.props.change('account_id', option.id);
    } else {
      this.props.untouch('account_id');
    }

    // For display purpose only in TypeAhead
    this.setState({ selectedAccount: option });
  };

  get isLADashboardEnabled() {
    return showWhenUtil({ featureEnabled: 'enable_la_dashboard' });
  }

  render() {
    const { handleSubmit, invalid, plan, accounts } = this.props;

    const nextWorkingDate = nextWorkingDay(
      moment()
        .startOf('day')
        .toDate(),
      3
    );

    return (
      <div class="content-wrapper content-sm txn-details">
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            {this.props.onClose && (
              <button
                type="button"
                class="close close-secondary"
                onClick={this.props.onClose}
              >
                <i class="i i-arrow-back" />
                <i class="i i-close" />
              </button>
            )}
            <i class="i i-plan text-main icon--formal" />{' '}
            <strong>Create New Transfer</strong>
          </div>

          <div class="SliderPanel__Body">
            <form
              class="panel-body"
              name="createPaymentTransfer"
              onSubmit={handleSubmit(this.save)}
            >
              <FormItem
                label={() => <Label text="Account" required />}
                field={() => (
                  <div class="custom-select" style={{ position: 'relative' }}>
                    <TypeAhead
                      options={accounts.accounts}
                      disabled={accounts.loading}
                      class="ps-in-modal"
                      searchIndices={['id', 'name', 'email']}
                      placeholder={`${
                        accounts.loading
                          ? 'Loading...'
                          : 'Account ID, Account Name, Email Address'
                      }`}
                      showClear={true}
                      selected={this.state.selectedAccount}
                      selectedOptionLabelPath="name"
                      optionComponent={({ option }) => {
                        return (
                          <div class="custom-powerselect-options">
                            <div>
                              <b>{titleCase(option.name)}</b> ({option.id})
                            </div>
                            {option.email}
                          </div>
                        );
                      }}
                      beforeOptionsComponent={() => (
                        <div class="heading">Recent</div>
                      )}
                      onClick={this.handleClick}
                      onChange={this.handleSelect}
                    />
                    <div
                      class="typeAheadSkin"
                      ref={c => (this.typeAheadSkin = c)}
                    >
                      {this.state.selectedAccount ? (
                        <div>
                          <b class="option-title">
                            {this.state.selectedAccount.name}
                          </b>
                          <span> - {this.state.selectedAccount.id} </span>
                        </div>
                      ) : null}
                    </div>
                  </div>
                )}
              />

              <FormItem
                label={_ => <Label text="Billing Amount" required />}
                field={_ => (
                  <div>
                    <Field
                      name="amount"
                      component={InputGroupField}
                      prefix="INR"
                      class="form-control"
                      validate={required('Transfer amount is required')}
                      placeholder="000.00"
                      type="text"
                    />
                    <span class="help-block label--secondary">
                      <i class="i i-info-outline" />
                      <b>Transfer amount</b> can not exceed payment amount.
                    </span>
                  </div>
                )}
              />

              <FormItem
                label={_ => <Label text="Settlement schedule" />}
                field={_ => (
                  <div>
                    <Field
                      component={RadioButton}
                      name="onHold"
                      htmlValue="false"
                      checked={this.props.onHold === 'false'}
                      label={_ => (
                        <div>
                          <span>Settle Now</span>
                          <div className="text-fade">
                            This transfer will be settled in next available
                            settlement slot.
                          </div>
                        </div>
                      )}
                    />
                    <Field
                      component={RadioButton}
                      name="onHold"
                      htmlValue="on_hold_until"
                      checked={this.props.onHold === 'on_hold_until'}
                      label={_ => (
                        <div>
                          <span>Schedule settlement on</span>
                        </div>
                      )}
                    />
                    <div className="transfers-onhold-datepicker">
                      <Field
                        component={DatePickerField}
                        name="holdUntil"
                        required
                        placeholder="Select Date"
                        disabled={this.props.onHold !== 'on_hold_until'}
                        isDayBlocked={date => {
                          date = date
                            .clone()
                            .startOf('day')
                            .toDate();

                          return date < nextWorkingDate || isHoliday(date);
                        }}
                      />
                    </div>
                    <Field
                      component={RadioButton}
                      name="onHold"
                      htmlValue="on_hold"
                      checked={this.props.onHold === 'on_hold'}
                      label={_ => (
                        <div>
                          <span>Put on hold</span>
                          <div className="text-fade">
                            The settlement will be on hold till specified
                            otherwise.
                          </div>
                        </div>
                      )}
                    />
                  </div>
                )}
              />

              <FormItem
                label={_ => <Label text="Internal Notes" />}
                field={_ => (
                  <FieldArray
                    name="notes"
                    component={NotesFieldArray}
                    showLinkedAccountOpt={this.isLADashboardEnabled}
                  />
                )}
              />

              <Alert type="error" message={this.state.errors} />
              <div class="btn-toolbar text-center">
                {typeof this.props.onClose === 'function' && (
                  <button
                    type="button"
                    class="btn btn-default btn-half"
                    onClick={() => {
                      this.context
                        .confirm({
                          header: 'Do you want to close this panel?',
                          message: 'Changes that you made may not be saved',
                          affirmativeLabel: 'Leave',
                          abortLabel: 'Stay',
                          action: () => this.props.onClose(),
                        })
                        .catch(() => {});
                    }}
                  >
                    Cancel
                  </button>
                )}
                <AsyncButton
                  type="submit"
                  class="btn btn-primary btn-half"
                  text="Create Transfer"
                  pendingText="Creating..."
                  onClick={handleSubmit(this.save)}
                />
              </div>
            </form>
          </div>
        </div>
      </div>
    );
  }
}

TransferNew.defaultProps = {
  onSave: () => {},
};
