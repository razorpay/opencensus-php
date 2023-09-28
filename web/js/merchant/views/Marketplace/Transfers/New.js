import AsyncButton from 'react-async-button';
import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form';
import moment from 'moment';
import { TypeAhead } from 'react-power-select';
import { withRouter } from 'common/deprecated/withRouter';
import debounce from 'common/utils/debounce';
import { prefixEntityValue } from 'merchant_common/helpers/data';
import Alert from 'common/ui/Forms/Alert';
import InputGroupField from 'common/ui/Forms/InputField/InputGroupField';
import { required } from 'common/utils/validators';
import { showNotification } from 'merchant_common/reducers/notifications';
import { titleCase, rupeesToPaise } from 'common/utils/rzp-utils';
import { fetchAccountsApi, fetchAccounts } from 'merchant/reducers/marketplace/accounts';
import FormItem from 'merchant/components/FormItem';
import NotesFieldArray from 'merchant/components/NotesFieldArray';
import { createTransfer } from 'merchant/reducers/payments/details';
import { isHoliday, nextWorkingDay } from 'common/utils/bankHolidays';
import RadioButton from 'common/ui/Forms/RadioButton';
import DirectTransferBanner from './components/DirectTransferBanner';
import { compose, bindActionCreators } from 'redux';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const DatePickerField = lazy(() =>
  import(/* webpackChunkName: 'DatePickerField' */ 'common/ui/Forms/DatePickerField'),
);

// TODO: Use components/AccountSelector to for account search | selection input

// eslint-disable-next-line no-shadow
const Label = ({ text, htmlFor, required }) => {
  const classes = typeof required !== 'undefined' ? 'label-required' : '';

  return (
    <div class="pair-label">
      <label for={htmlFor} class={classes}>
        {text}
      </label>
    </div>
  );
};

const selector = formValueSelector('createPaymentTransfer');

class TransferNew extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {
    selectedAccount: null,
  };

  UNSAFE_componentWillMount() {
    if (this.props.plan) {
      this.props.initialize(this.props.plan);
    }

    this.props.fetchAccounts({});
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
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

  save = (props) => {
    if (!this.state.selectedAccount) {
      return this.props.showNotification({
        type: 'error',
        message: 'Please select an Account',
      });
    }

    const { onHold, holdUntil, notes, amount } = props;
    const accountId = this.state.selectedAccount.id;

    if (onHold === 'on_hold_until' && !holdUntil) {
      return this.props.showNotification({
        type: 'error',
        message: 'Please select a date',
      });
    }

    let transformedNotes = notes;
    const linked_account_notes = [];

    if (transformedNotes && transformedNotes.length > 0) {
      transformedNotes = transformedNotes.reduce((result, current) => {
        result[current.key] = current.value;
        if (current.also_linked_account) {
          linked_account_notes.push(current.key);
        }
        return result;
      }, {});
    }

    const holdData = {};

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
          linked_account_notes,
          currency: 'INR',
          ...holdData,
        },
      ],
    }).then(
      () => {
        this.props.showNotification({
          type: 'success',
          message: 'Transfer created Successfully',
        });

        this.setState({
          selectedAccount: null,
        });

        this.props.reset();

        if (typeof this.props.onCreate === 'function') {
          this.props.onCreate();
        }
      },
      ({ errors }) => this.showTransferCreationError(errors),
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

  searchInAccountList(val) {
    fetchAccountsApi(null, { q: val, search_hits: 1 })
      .then((resp) => {
        let accountsList = null;
        if (resp.data && resp.data.items && resp.data.items.length) {
          accountsList = resp.data.items;
        }

        this.setState({ accountsList });
      })
      .catch(() => {
        this.setState({ accountsList: null });
      });
  }

  debounce_searchInAccountList = debounce(this.searchInAccountList.bind(this), 50);

  handleKeyDown = (e) => {
    const target = e.target;

    setTimeout(() => {
      const val = target.value;

      if (val && val.length < 2) {
        this.setState({ accountsList: null });
        return;
      }

      this.debounce_searchInAccountList(val);
    }, 5);
  };

  render() {
    const { handleSubmit, accounts, isDirectTransferEnabled } = this.props;

    let accountsList;

    if (!accounts.loading) {
      accountsList = this.state.accountsList || accounts.accounts;
    }

    const nextWorkingDate = nextWorkingDay(moment().startOf('day').toDate(), 3);

    return (
      <div class="content-wrapper content-sm txn-details">
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            {this.props.onClose && (
              <button type="button" class="close close-secondary" onClick={this.props.onClose}>
                <i class="i i-arrow-back" />
                <i class="i i-close" />
              </button>
            )}
            <i class="i i-plan text-main icon--formal" /> <strong>Create New Transfer</strong>
          </div>

          <div class="SliderPanel__Body">
            <div class="panel-body">
              {isDirectTransferEnabled && <DirectTransferBanner />}

              <form
                class="panel-body create-payment-transfer"
                name="createPaymentTransfer"
                onSubmit={handleSubmit(this.save)}
              >
                <FormItem
                  label={() => <Label text="Account" required />}
                  field={() => (
                    <div class="custom-select auto-complete-search">
                      <TypeAhead
                        options={accountsList}
                        disabled={!accountsList}
                        class="ps-in-modal"
                        searchIndices={['id', 'name', 'email']}
                        placeholder={`${
                          !accountsList ? 'Loading...' : 'Account ID, Account Name, Email Address'
                        }`}
                        showClear={true}
                        selected={this.state.selectedAccount}
                        selectedOptionLabelPath="name"
                        optionComponent={({ option }) => {
                          return (
                            <div class="custom-powerselect-options">
                              <div>
                                <b>{titleCase(option.name)}</b> ({option.code || option.id})
                              </div>
                              {option.email}
                            </div>
                          );
                        }}
                        beforeOptionsComponent={() => <div class="heading">Recent</div>}
                        onChange={this.handleSelect}
                        onKeyDown={this.handleKeyDown}
                      />
                      <div class="typeAheadSkin" ref={(c) => (this.typeAheadSkin = c)}>
                        {this.state.selectedAccount ? (
                          <div>
                            <b class="option-title">{this.state.selectedAccount.name}</b>
                            <span> - {this.state.selectedAccount.id} </span>
                          </div>
                        ) : null}
                      </div>
                    </div>
                  )}
                />

                <FormItem
                  label={(_) => <Label text="Billing Amount" required />}
                  field={(_) => (
                    <div>
                      <Field
                        name="amount"
                        component={InputGroupField}
                        prefix="INR"
                        class="form-control"
                        validate={required('Transfer amount is required')}
                        placeholder="0.00"
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
                  label={(_) => <Label text="Settlement schedule" />}
                  field={(_) => (
                    <div>
                      <Field
                        component={RadioButton}
                        name="onHold"
                        htmlValue="false"
                        checked={this.props.onHold === 'false'}
                        label={() => (
                          <div>
                            <span>Settle Now</span>
                            <div class="text-fade">
                              This transfer will be settled in next available settlement slot.
                            </div>
                          </div>
                        )}
                      />
                      <Field
                        component={RadioButton}
                        name="onHold"
                        htmlValue="on_hold_until"
                        checked={this.props.onHold === 'on_hold_until'}
                        label={() => (
                          <div>
                            <span>Schedule settlement on</span>
                          </div>
                        )}
                      />
                      <div class="transfers-onhold-datepicker">
                        <SuspenseWithLoader>
                          <Field
                            component={DatePickerField}
                            name="holdUntil"
                            required
                            placeholder="Select Date"
                            disabled={this.props.onHold !== 'on_hold_until'}
                            isDayBlocked={(date) => {
                              date = date.clone().startOf('day').toDate();

                              return date < nextWorkingDate || isHoliday(date);
                            }}
                          />
                        </SuspenseWithLoader>
                      </div>
                      <Field
                        component={RadioButton}
                        name="onHold"
                        htmlValue="on_hold"
                        checked={this.props.onHold === 'on_hold'}
                        label={() => (
                          <div>
                            <span>Put on hold</span>
                            <div class="text-fade">
                              The settlement will be on hold till specified otherwise.
                            </div>
                          </div>
                        )}
                      />
                    </div>
                  )}
                />

                <FormItem
                  label={(_) => <Label text="Internal Notes" />}
                  field={(_) => (
                    <FieldArray
                      name="notes"
                      component={NotesFieldArray}
                      showLinkedAccountOpt={true}
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
      </div>
    );
  }
}

TransferNew.defaultProps = {
  onSave: () => {},
};

const mapStateToProps = (state) => {
  return {
    accounts: state.accounts,
    onHold: selector(state, 'onHold'),
    holdUntil: selector(state, 'holdUntil'),
    notes: selector(state, 'notes'),
    amount: selector(state, 'amount'),
    accountId: state.accountId,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ createTransfer, showNotification, fetchAccounts }, dispatch);

export default compose(
  withRouter,
  reduxForm({
    form: 'createPaymentTransfer',
    initialValues: {
      onHold: 'false',
      notes: [],
      account_id: null,
      holdUntil: null,
    },
  }),
  connect(mapStateToProps, mapDispatchToProps),
)(TransferNew);
