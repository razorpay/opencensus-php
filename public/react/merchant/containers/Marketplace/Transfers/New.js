import AsyncButton from 'react-async-button';
import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, FieldArray, reduxForm } from 'redux-form';
import { TypeAhead } from 'react-power-select';
import { withRouter } from 'react-router-dom';

import Alert from 'rzp/ui/Forms/Alert';
import DatePickerField from 'rzp/ui/Forms/DatePickerField';
import InputField from 'rzp/ui/Forms/InputField';
import InlineField from 'rzp/ui/Forms/InlineField';
import InputGroupField from 'rzp/ui/Forms/InputField/InputGroupField';
import { required } from 'rzp/utils/validators';
import RadioButton from 'rzp/ui/Forms/RadioButton';
import { showNotification } from 'rzp/modules/notifications';
import { titleCase } from 'rzp/utils/rzp-utils';

import { fetchAccounts } from 'merchant/modules/marketplace/accounts';
import FormItem from 'merchant/components/FormItem';
import NotesFieldArray from 'merchant/components/NotesFieldArray';
import { savePlan } from 'merchant/modules/plans';

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

@connect(state => ({ accounts: state.accounts }), {
  savePlan,
  showNotification,
  fetchAccounts,
})
@reduxForm({
  form: 'createPaymentTransfer',
  initialValues: {
    notes: [],
  },
})
@withRouter
export default class TransferNew extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {};

  componentWillMount() {
    if (this.props.plan) {
      this.props.initialize(this.props.plan);
    }

    this.props.fetchAccounts({ count: 100 }); // Currently keeping count = 100
  }

  save = props => {
    return this.props
      .savePlan(props)
      .then(plan => {
        this.props.onSave(plan);
        this.props.history.push(`/plans/${plan[plan.resourceIdField]}`);
        this.props.showNotification({
          type: 'success',
          message: 'Plan saved successfully',
        });
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  handleClick = () => {
    this.typeAheadSkin.classList.add('hide');

    setTimeout(
      () =>
        document.getElementsByClassName('transfers-powerselect__Menu')[0] &&
        document
          .getElementsByClassName('transfers-powerselect__Menu')[0]
          .parentNode.classList.add('super-impose')
    );
  };

  handleSelect = ({ option }) => {
    this.typeAheadSkin.classList.remove('hide');

    // For setting in redux-form
    if (option) {
      this.props.change('account_id', option.id);
    } else {
      this.props.untouch('createPaymentTransfer', 'account_id');
    }

    // For display purpose only in TypeAhead
    this.setState({ selectedAccount: option });
  };

  render() {
    const { handleSubmit, invalid, plan, accounts } = this.props;

    return (
      <div class="content-wrapper content-sm txn-details">
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            {this.props.onClose &&
              <button
                type="button"
                class="close close-secondary"
                onClick={this.props.onClose}
              >
                <i class="icon icon-arrow-back" />
                <i class="icon icon-close" />
              </button>}
            <i class="icon icon-plan text-main icon--formal" />{' '}
            <strong>Create New Transfer</strong>
          </div>

          <div class="SliderPanel__Body">
            <form class="panel-body" onSubmit={handleSubmit(this.save)}>
              <FormItem
                label={() => <Label text="Account" required />}
                field={() =>
                  <div class="custom-select" style={{ position: 'relative' }}>
                    <TypeAhead
                      options={accounts.accounts}
                      disabled={accounts.loading}
                      class="transfers-powerselect"
                      searchIndices={['id', 'name', 'email']}
                      placeholder={`${accounts.loading
                        ? 'Loading...'
                        : 'Account ID, Account Name, Email Address'}`}
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
                      beforeOptionsComponent={() =>
                        <div class="heading">Recent</div>}
                      onClick={this.handleClick}
                      onChange={this.handleSelect}
                    />
                    <div
                      class="typeAheadSkin"
                      ref={c => (this.typeAheadSkin = c)}
                    >
                      {this.state.selectedAccount
                        ? <div>
                            <b style={{ marginRight: '5px' }}>
                              {this.state.selectedAccount.name}
                            </b>
                            <span>
                              {' '}- {this.state.selectedAccount.id}{' '}
                            </span>
                          </div>
                        : null}
                    </div>
                  </div>}
              />

              <FormItem
                label={_ => <Label text="Billing Amount" required />}
                field={_ =>
                  <div>
                    <Field
                      name="item[amount]"
                      component={InputGroupField}
                      prefix="INR"
                      class="form-control"
                      validate={required('Transfer amount is required')}
                      placeholder="199.99"
                    />
                    <span class="help-block label--secondary">
                      <i class="icon icon-info-outline" />
                      <b>Transfer amount</b> can not exceed payment amount.
                    </span>
                  </div>}
              />

              <FormItem
                label={_ => <Label text="Internal Notes" />}
                field={_ =>
                  <FieldArray
                    name="notes"
                    component={NotesFieldArray}
                    required
                  />}
              />

              <FormItem
                label={_ => <Label text="Settlement schedule" />}
                field={_ =>
                  <div>
                    <Field
                      component={RadioButton}
                      name="on-hold"
                      htmlValue="on_hold_until"
                      label={_ =>
                        <div>
                          <span>Schedule settlement on</span>
                        </div>}
                    />
                    <div className="transfers-onhold-datepicker">
                      <Field component={DatePickerField} name="onHoldDate" />
                    </div>
                    <Field
                      component={RadioButton}
                      name="on-hold"
                      htmlValue="on_hold"
                      label={_ =>
                        <div>
                          <span>Put on hold</span>
                        </div>}
                    />
                  </div>}
              />

              <Alert type="error" message={this.state.errors} />
              <div class="btn-toolbar text-center">
                <AsyncButton
                  type="submit"
                  class="btn btn-primary btn-half"
                  text="Create Transfer"
                  pendingText="Creating..."
                  onClick={handleSubmit(this.save)}
                />
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
                        action: () => this.props.history.push(`/plans`),
                      })
                      .catch(() => {});
                  }}
                >
                  Discard
                </button>
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
