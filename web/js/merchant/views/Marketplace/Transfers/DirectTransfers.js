/* eslint-disable react/no-unsafe */
/* eslint-disable react/jsx-no-duplicate-props */
/* eslint-disable consistent-return */
import React from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { withRouter } from 'common/deprecated/withRouter';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Form from 'common/new-ui/Form';
import Input, { Label, Description } from 'common/new-ui/Input';
import { onChangeNotes } from 'common/new-ui/Input/PairList';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Amount from 'common/ui/Amount';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Spinner from 'common/ui/Spinner';
import { isHoliday, nextWorkingDay } from 'common/utils/bankHolidays';
import { classList } from 'common/utils/rzp-utils';
import { i18nifyConvertToMinorUnit } from 'merchant/views/Transactions/v2/common/utils';
import { isAmount } from 'common/utils/validators';
import AccountSelector from 'merchant/components/AccountSelector';
import { luminateRow } from 'merchant/reducers/app';
import { fetchCurrentBalance } from 'merchant/reducers/home';
import { createDirectTransfer } from 'merchant/reducers/payments/details';
import { prefixEntityValue } from 'merchant_common/helpers/data';
import { showNotification } from 'merchant_common/reducers/notifications';

@connect(
  (state) => ({
    accounts: state.accounts,
    current_balance: state.home.current_balance,
  }),
  {
    luminateRow,
    showNotification,
    fetchCurrentBalance,
  },
)
@RTracking(() => window.rzpQ.component('DirectTransfers'))
class DirectTransfers extends React.Component {
  state = {
    disableSubmit: false,
    isFormLocked: false,
    formData: {},
    disableOnHoldUntilDatePicker: true,
  };

  holdUntilEle = React.createRef();
  formEle = React.createRef();

  isModalView = !!this.props.onClose;

  UNSAFE_componentWillMount() {
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'route_direct_transfers');
      window.hj('tagRecording', ['route_direct_transfers']);
    }
  }

  componentDidMount() {
    this.toggleDisableState();
    this.props.fetchCurrentBalance();
  }

  componentDidUpdate() {
    this.toggleDisableState();
  }

  toggleDisableState = () => {
    // if value not selected, html marks it as ':invalid' which is tehnically valid in our case. Hence, relying on is-invalid.
    const invalidFields = this.formEle.querySelectorAll(`.Input.is-invalid`);
    const disableSubmit = !this.state.selectedAccount || invalidFields.length;

    if (this.state.disableSubmit !== disableSubmit) {
      this.setState({ disableSubmit });
    }
  };

  onSubmit = () => {
    const { formData, selectedAccount } = this.state;

    const payload = {
      ...formData,
      account: prefixEntityValue('account', selectedAccount.id),
      amount: i18nifyConvertToMinorUnit(formData.amount),
      currency: this.props.user?.merchant?.currency || 'INR',
    };

    if (formData.on_hold) {
      payload.on_hold = formData.on_hold === '1' ? 1 : 0;
    }

    if (!payload.on_hold_until) {
      delete payload.on_hold_until;
    }

    this.setState({
      isFormLocked: true,
    });

    return createDirectTransfer(payload)
      .then((resp) => {
        this.props.showNotification({
          type: 'success',
          message: 'Transfer created Successfully',
        });

        if (this.isModalView) {
          this.props.onClose();

          this.props.luminateRow(resp.data.id);
        }

        this.props.history.push(`/route/transfers/${resp.data.id}`);
      })
      .catch(({ errors }) => {
        const error = (errors || [])[0];

        this.props.showNotification({
          type: 'error',
          message: error,
        });

        this.setState({
          isFormLocked: false,
        });
      });
  };

  updateFormData = (key, value) => {
    this.setState((currState) => ({
      formData: {
        ...currState.formData,
        [key]: value,
      },
    }));
  };

  handleAmount = (event) => this.updateFormData(event.target.name, event.target.value);

  onChangeNotes = (pairs) => {
    const notes = onChangeNotes(pairs);
    this.updateFormData('notes', notes);
  };

  // TODO: think batter way to handle this
  handleSettlementSchedule = ({ target }) => {
    this.setState(
      (currState) => ({
        formData: {
          ...currState.formData,
          on_hold: target.value === '2' ? '1' : target.value,
          on_hold_until: null,
        },
        disableOnHoldUntilDatePicker: target.value !== '1',
      }),
      () => {
        if (target.value === '1') {
          this.holdUntilEle.focus();
          this.holdUntilEle.click();
        }
      },
    );
  };

  onDateChange = (ts) => {
    const value = moment(ts).unix();

    this.updateFormData('on_hold_until', value);
  };

  handleAccount = (selectedAccount) => this.setState({ selectedAccount });

  renderFields = () => {
    const { state, props } = this;

    return (
      <>
        <AccountSelector
          required
          showClear
          label="Account"
          updateAccount={this.handleAccount}
          selectedAccount={state.selectedAccount}
        />

        <Input.Group required class="InputGroup--inline InputGroup--vTop" label="Billing Amount">
          <div class="Input-content">
            <Input.CurrencySelect
              name="currency"
              defaultValue={props.user?.merchant?.currency || 'INR'}
              disabled
              parentQuerySelector=".Modal-body"
            />

            <Input
              required
              class="Input--vTop"
              name="amount"
              placeholder="0.00"
              onChange={this.handleAmount}
              disabled={state.isFormLocked}
              validator={amountValidator(props.current_balance.data.balance)}
            />
          </div>

          <Description text={<CurrentBalance currentBalance={props.current_balance} />} />
        </Input.Group>

        <Input.Radio
          label="Settlement Schedule"
          class="Input--vTop settlement-schedule"
          onChange={this.handleSettlementSchedule}
          disabled={state.isFormLocked}
          options={[
            {
              label: (
                <>
                  Settle Now
                  <div class="text-fade">
                    Transfer will be settled in the next available settlement slot
                  </div>
                </>
              ),
              value: '0',
            },
            {
              label: (
                <>
                  <Label text="Schedule Settlement On" />
                  <Input.ToCalendar
                    readOnly
                    allowToday
                    disablePastDates
                    placeholder="DD-MM-YYYY"
                    onChange={this.onDateChange}
                    addonAfter={<i class="i i-date-range" />}
                    placement="topLeft"
                    disabled={state.disableOnHoldUntilDatePicker || state.isFormLocked}
                    isDayBlocked={getIsDayBlocked}
                    ref={(ele) => (this.holdUntilEle = ele)}
                  />
                </>
              ),
              value: '1',
            },
            {
              label: (
                <>
                  Put on hold
                  <div class="text-fade">
                    The settlement will be on hold till specified otherwise.
                  </div>
                </>
              ),
              value: '2',
            },
          ]}
        />

        <Input.PairList
          class="Input--vTop"
          name="notes"
          label="Notes"
          onChange={this.onChangeNotes}
          disabled={state.isFormLocked}
        />
      </>
    );
  };

  render() {
    const { props, state } = this;
    const disableSubmit = props.isLoading || state.disableSubmit || state.isFormLocked;

    const content = (
      <div class="Transfers--DirectTransfer">
        <div class="title">Create Direct Transfer</div>
        <div class="form-container">
          <Form>
            <main ref={(ele) => (this.formEle = ele)}>
              {props.isLoading ? (
                <div className="page-center">
                  <Spinner />
                </div>
              ) : (
                this.renderFields()
              )}
            </main>

            <footer>
              {this.isModalView && (
                <Button type="button" onClick={props.onClose}>
                  Cancel
                </Button>
              )}

              <AsyncBtn.Primary
                type="submit"
                pendingState="Creating..."
                onClick={this.onSubmit}
                disabled={disableSubmit}
              >
                Create Transfer
              </AsyncBtn.Primary>
            </footer>
          </Form>
        </div>
      </div>
    );

    if (this.isModalView) {
      return (
        <Modal class={classList('PaymentLink--CreateV2', 'animate-down')} showCloseBtn={false}>
          <ModalContent>{content}</ModalContent>
        </Modal>
      );
    }

    return <div class="StandAloneContainer">{content}</div>;
  }
}

function CurrentBalance({ currentBalance }) {
  const balanceText = 'Current Balance: ';
  if (currentBalance.loading) {
    return (
      <>
        {balanceText} <PlaceholderLoader />{' '}
      </>
    );
  }

  let balance = currentBalance.data.balance || 0;
  let currentBalanceClassName = 'amount-current-balance';

  if (balance < 0) {
    balance = Math.abs(balance);
    currentBalanceClassName += ' negative-balance';
  }

  return (
    <>
      {balanceText}
      <Amount
        value={balance}
        currency={currentBalance.data.currency || 'INR'}
        class={currentBalanceClassName}
        parentQuerySelector=".Modal-body"
      />{' '}
    </>
  );
}

function amountValidator(balance) {
  return (value) => {
    if (!isAmount(value)) {
      return 'Invalid Amount';
    }

    if (value > parseFloat(balance)) {
      return 'Billing amount cannot exceed current balance';
    }
  };
}

function getIsDayBlocked(date) {
  const nextWorkingDate = nextWorkingDay(moment().startOf('day').toDate(), 3);
  date = date.clone().startOf('day').toDate();

  return date < nextWorkingDate || isHoliday(date);
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

export default connect(mapStateToProps)(withRouter(DirectTransfers));
