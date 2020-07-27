import React from 'react';
import Input from 'common/new-ui/Input';
import Amount from 'common/ui/Amount';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { connect } from 'react-redux';
import {
  fetchSeedData,
  fetchWithdrawalConfiguration,
  createWithdrawal,
  fetchWithdrawalConfigurationByMerchantID,
  fetchWithdrawals,
} from 'merchant/reducers/capital/withdrawals';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { showNotification } from 'merchant_common/reducers/notifications';
import { CSSTransition } from 'react-transition-group';
import { STATUSES, VIEWS, CLOSE_OPTIONS } from './constants';
import CreditSummary from './CreditSummary';
import WithdrawnAmountSummary from './WithdrawnAmountSummary';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import MinWithdrawAmountModal from './MinWithdrawAmountModal';
import CancelWithdrawalReasons from './CancelWithdrawalReasons';
import Popover, { PopoverBody } from 'common/ui/Popover';

@connect(
  state => ({
    user: state.session.user,
    withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
    seedData: state.withdrawals.seedData,
  }),
  {
    fetchWithdrawalConfiguration: fetchWithdrawalConfiguration,
    fetchSeedData: fetchSeedData,
    fetchWithdrawalConfigurationByMerchantID,
    showNotification,
    openModal,
    closeModal,
    fetchWithdrawals,
  }
)
export default class AmountWithdraw extends React.Component {
  constructor(props) {
    super(props);
    this.initialState = {
      withdrawalAmount: '',
      selectedDueDate: null,
      withdraw_errors: [],
      isConfirmingWithdraw: false,
      currentView: VIEWS.WITHDRAW,
      showRepaymentDetailsBreakup: false,
      isTouched: false,
    };
    this.state = this.initialState;
  }

  componentDidMount() {
    const { fetchSeedData } = this.props;

    // fetchSeedData();
    this.fetchWithdrawalConfiguration().then(_ => this.prefillData());
  }

  gaEventDispatcher = eventObject => {
    eventObject['eventCategory'] = 'Dashboard CA - Apply';
    window.rzpAnalytics(eventObject);
  };

  fetchWithdrawalConfiguration = () => {
    const { fetchWithdrawalConfigurationByMerchantID, user } = this.props;
    return fetchWithdrawalConfigurationByMerchantID({
      owner_type: 'RZP_MERCHANT',
      owner_id: user.current,
      status: 'ACTIVE',
      skip: 0,
    });
  };

  prefillData = () => {
    const maxWithdrawableAmount = this.getMaxWithdrawableAmount();
    const withdrawalConfigurationDetails = this.props
      .withdrawalConfigurationDetails.data;
    const { start_day_limit } = withdrawalConfigurationDetails.configuration;
    const startDay = moment().add(start_day_limit, 'days');

    if (maxWithdrawableAmount > this.getMinWithdrawableAmount()) {
      this.setState({
        withdrawalAmount: maxWithdrawableAmount / 100,
        selectedDueDate: startDay.endOf('day'),
      });
    }
  };

  fetchWithdrawals = () => {
    this.props.fetchWithdrawals({
      reference: [
        {
          reference_id: this.props.user.current,
          reference_type: 'OWNER_ID',
        },
      ],
      order_by: 'CREATED_AT',
      order_direction: 'desc',
      limit: 20,
    });
  };

  handleWithdrawalAmountChange = e => {
    e.persist();
    const amount = parseInt(e.currentTarget.value + '00');
    const errors = [];
    if (amount > this.getMaxWithdrawableAmount()) {
      errors.push(
        'Max. amount can be withdrawn is ₹' +
          getFormattedAmountNew(this.getMaxWithdrawableAmount())
      );
    }

    if (amount < this.getMinWithdrawableAmount()) {
      errors.push(
        'Min. amount can be withdrawn is ₹' +
          getFormattedAmountNew(this.getMinWithdrawableAmount())
      );
    }

    if (amount > this.getInternalCreditBalance()) {
      errors.push(
        'Amount cannot be more than the credit limit(₹' +
          getFormattedAmountNew(this.getMinWithdrawableAmount()) +
          ')'
      );
    }

    this.setState({
      withdrawalAmount: e.currentTarget.value,
      withdraw_errors: errors,
    });
  };

  handleDueDateChange = date => {
    this.setState({
      selectedDueDate: date,
    });
  };

  isDateDisabled = current => {
    if (!current) return false;

    const withdrawalConfigurationDetails = this.props
      .withdrawalConfigurationDetails.data;
    const {
      start_day_limit,
      end_day_limit,
    } = withdrawalConfigurationDetails.configuration;

    const startDay = moment()
      .add(start_day_limit, 'days')
      .format('LL');
    const endDay = moment()
      .add(end_day_limit, 'days')
      .format('LL');

    const isValid = current.isAfter(startDay) && current.isBefore(endDay);
    return !isValid;
  };

  getRepayableAmount = () => {
    const withdrawalConfigurationDetails = this.props
      .withdrawalConfigurationDetails.data;
    const {
      start_day_limit,
      interest,
    } = withdrawalConfigurationDetails.configuration;
    const startDay = moment();

    const selectedDate = moment(this.state.selectedDueDate).endOf('day');

    const diffDays = selectedDate.diff(startDay, 'days');

    const roi = parseInt(interest) / 100;

    const amount = {
      principle: parseInt(this.state.withdrawalAmount),
      interest: (diffDays * parseInt(this.state.withdrawalAmount) * roi) / 100,
      diffDays,
      roi,
    };

    return amount;
  };

  getInternalCreditBalance = () => {
    const withdrawalConfigurationDetails = this.props
      .withdrawalConfigurationDetails.data;
    return (
      parseInt(
        withdrawalConfigurationDetails.configuration.internal_credit_limit
      ) -
      parseInt(
        withdrawalConfigurationDetails.principal_outstanding_balance || 0
      )
    );
  };

  getMaxWithdrawableAmount = () => {
    const withdrawalConfigurationDetails = this.props
      .withdrawalConfigurationDetails.data;
    const {
      max_withdraw_amount,
      internal_credit_limit,
      min_withdraw_amount,
    } = withdrawalConfigurationDetails.configuration;

    const maxAmount = Math.min(
      parseInt(max_withdraw_amount),
      this.getInternalCreditBalance()
    );

    const minAmount = parseInt(min_withdraw_amount);
    return maxAmount > 0 ? (maxAmount > minAmount ? maxAmount : minAmount) : 0;
  };

  getMinWithdrawableAmount = () => {
    const withdrawalConfigurationDetails = this.props
      .withdrawalConfigurationDetails.data;

    return parseInt(
      withdrawalConfigurationDetails.configuration.min_withdraw_amount
    );
  };

  canWithdraw = () => {
    const { withdrawalAmount, selectedDueDate, withdraw_errors } = this.state;
    return (
      !!withdrawalAmount && !!selectedDueDate && withdraw_errors.length === 0
    );
  };

  confirmWithdraw = () => {
    this.gaEventDispatcher({
      eventAction: 'Dashboard CA - Withdraw',
      eventLabel: 'Withdraw | Withdraw Now',
    });
    this.setState({
      isConfirmingWithdraw: true,
    });
  };

  withdraw = async () => {
    if (!this.canWithdraw()) return;

    this.gaEventDispatcher({
      eventAction: 'Dashboard CA - Withdraw',
      eventLabel: 'Withdraw | Confirm',
    });

    const withdrawalConfigurationDetails = this.props
      .withdrawalConfigurationDetails.data;
    const { withdrawalAmount, selectedDueDate } = this.state;

    const payload = {
      withdrawal: {
        withdrawal_config_id: withdrawalConfigurationDetails.id,
        owner_id: withdrawalConfigurationDetails.owner_id,
        owner_type: withdrawalConfigurationDetails.owner_type,
        //TODO: BE will remove this validation, then remove hardcoding from
        // FE as well
        application_id: withdrawalConfigurationDetails.application_id,
        application_number: withdrawalConfigurationDetails.application_number,
        amount: withdrawalAmount * 100,
        due_date: moment(selectedDueDate)
          .utc()
          .format(),
        drawn_at: moment()
          .utc()
          .format(),
        //TODO: right now BE has kept this as mandatory, remove this after
        // BE remove this validation
        comments: {
          message: 'withdrawn',
        },
      },
    };
    let response;
    try {
      response = await createWithdrawal(payload);
      if (
        response &&
        !response.errors &&
        response.data.withdrawal.status !== STATUSES.REJECTED &&
        response.data.withdrawal.status !== STATUSES.FAILED
      ) {
        this.setState({
          currentView: VIEWS.WITHDRAW_SUCCESS,
        });
        this.fetchWithdrawalConfiguration();
        this.fetchWithdrawals();
        this.gaEventDispatcher({
          eventAction: 'Withdraw | Success',
        });
      } else {
        this.setState({
          currentView: VIEWS.WITHDRAW_FAIL,
          showRepaymentDetailsBreakup: false,
        });
        this.gaEventDispatcher({
          eventAction: 'Withdraw | Fail',
        });
      }
    } catch (e) {
      this.setState({
        currentView: VIEWS.WITHDRAW_FAIL,
        showRepaymentDetailsBreakup: false,
      });
      this.gaEventDispatcher({
        eventAction: 'Withdraw | Fail',
      });
      showNotification({
        type: 'error',
        message: 'Error Occurred while processing your withdrawal request.',
      });
    }
  };

  cancelWithdraw = () => {
    this.props.openModal({
      component: (
        <CancelWithdrawalReasons
          eventCategory="Dashboard FC - Withdraw"
          eventAction="Withdraw | Cancel | Reason"
          closeReasons={CLOSE_OPTIONS}
          onClose={() => {
            this.props.closeModal();
            this.setState({
              isConfirmingWithdraw: false,
            });
          }}
        />
      ),
      size: 'small',
    });
  };

  toggleWithdrawView = fromWhere => {
    this.gaEventDispatcher({
      eventAction: `Withdraw | ${fromWhere}`,
    });
    this.setState(this.initialState);
  };

  toggleBreakup = () => {
    this.gaEventDispatcher({
      eventAction: this.state.showRepaymentDetailsBreakup
        ? 'Withdraw | Hide Breakup'
        : 'Withdraw | Show Breakup',
    });
    this.setState(prevState => ({
      showRepaymentDetailsBreakup: !prevState.showRepaymentDetailsBreakup,
    }));
  };

  handleBlur = () => {
    this.setState({
      isTouched: true,
    });
  };

  isMinWithdrawalBalanceAvailable = () => {
    return this.getInternalCreditBalance() > this.getMinWithdrawableAmount();
  };

  getWithdrawCTA = () => {
    const withdrawalConfigurationDetails = this.props
      .withdrawalConfigurationDetails.data;
    const withdrawConfigLoading = this.props.withdrawalConfigurationDetails
      .loading;

    const { seedData } = this.props;
    if (
      !withdrawalConfigurationDetails ||
      withdrawConfigLoading ||
      seedData.loading
    )
      return null;

    if (!this.isMinWithdrawalBalanceAvailable()) {
      return (
        <button
          class="btn btn-outline"
          onClick={() => {
            this.gaEventDispatcher({
              eventAction: "Low Balance | Why can't I withdraw",
            });

            this.props.openModal({
              size: 'small',
              component: (
                <MinWithdrawAmountModal
                  availableBalance={this.getInternalCreditBalance()}
                  minWithdrawalAmount={this.getMinWithdrawableAmount()}
                  closeModal={this.props.closeModal}
                  repayDues={this.props.repayDues}
                  trackGA={this.gaEventDispatcher}
                />
              ),
            });
          }}
        >
          Why can't I withdraw?
        </button>
      );
    }
    return (
      <React.Fragment>
        {!this.state.isConfirmingWithdraw ? (
          <AsyncBtn.Primary
            class="btn btn-primary"
            disabled={!this.canWithdraw()}
            onClick={this.confirmWithdraw}
          >
            Withdraw Now
          </AsyncBtn.Primary>
        ) : (
          <div className="flex">
            <button className="btn btn-link" onClick={this.cancelWithdraw}>
              Cancel
            </button>
            <AsyncBtn.Primary
              class="btn btn-primary"
              disabled={!this.canWithdraw()}
              onClick={this.withdraw}
            >
              Confirm
            </AsyncBtn.Primary>
          </div>
        )}
      </React.Fragment>
    );
  };

  withdrawableSection = () => {
    const { withdrawalAmount } = this.state;
    return (
      <div className="withdrawals__action-container flex">
        <div>
          <h3 className="title text--secondary">Withdraw Amount</h3>
          <div className="flex">
            <Input.Group
              label="How much do you want to withdraw?"
              className="InputGroup--inline Input--vTop"
              required
            >
              <div className="Input-content">
                <Input
                  addonBefore={'₹'}
                  type="number"
                  addonAfter={<small>.00</small>}
                  name="amount"
                  onBlur={this.handleBlur}
                  value={withdrawalAmount}
                  onChange={this.handleWithdrawalAmountChange}
                />
              </div>
              {this.state.withdraw_errors.length > 0 &&
                this.state.isTouched && (
                  <div class="text-danger">
                    {this.state.withdrawalAmount &&
                      this.state.withdraw_errors[0]}
                  </div>
                )}
            </Input.Group>
            <Input.ToCalendar
              required={false}
              className="Input--vTop"
              data-name="date_slot"
              label={
                <React.Fragment>
                  When do you repay by?
                  <small
                    className="help-content"
                    style={{ paddingLeft: '4px' }}
                  >
                    <i
                      className="i i-info-outline"
                      // onMouseOver={() => trackMouseOver('tenure')}
                    />
                    <Popover align="top" theme="dark">
                      <PopoverBody>
                        <div className="text-center">
                          Your equated repayments will start from tomorrow
                        </div>
                      </PopoverBody>
                    </Popover>
                  </small>
                </React.Fragment>
              }
              defaultValue={
                this.state.selectedDueDate
                  ? moment(this.state.selectedDueDate).format('DD-MM-YYYY')
                  : ''
              }
              value={
                this.state.selectedDueDate
                  ? moment(this.state.selectedDueDate).format('DD-MM-YYYY')
                  : ''
              }
              onChange={this.handleDueDateChange}
              addonAfter={<i className="i i-date-range" />}
              placement="topLeft"
              allowToday={false}
              disablePastDates={false}
              disabledDate={this.isDateDisabled}
            />
            <div className="withdrawal-cta-container">
              {this.getWithdrawCTA()}
            </div>
          </div>
          {this.state.selectedDueDate && this.state.withdrawalAmount && (
            <div class="repayable-amount-hint">
              <strong>
                {getFormattedAmountNew(
                  (this.getRepayableAmount().principle +
                    this.getRepayableAmount().interest) *
                    100,
                  true
                )}
              </strong>
              <span class="repayable-helper-text">
                &nbsp; will be the repayable amount
              </span>
              <div class="flex">
                <div class="text-small full-width no-margin text-strong p-r">
                  <strong>
                    {this.state.showRepaymentDetailsBreakup ? (
                      <Button.Transparent onClick={this.toggleBreakup}>
                        <i class="i i-chevron-left" />
                        Hide Breakup
                      </Button.Transparent>
                    ) : (
                      <Button.Transparent onClick={this.toggleBreakup}>
                        View Breakup
                        <i class="i i-chevron-right" />
                      </Button.Transparent>
                    )}
                    {this.state.showRepaymentDetailsBreakup && (
                      <Button.Transparent
                        class="pull-right"
                        onClick={this.toggleBreakup}
                      >
                        Show Credit Details
                        <i class="i i-chevron-right" />
                      </Button.Transparent>
                    )}
                  </strong>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    );
  };

  withdrawalSuccessView = () => {
    const { selectedDueDate, withdrawalAmount } = this.state;
    const repayableAmount =
      this.getRepayableAmount().interest +
      this.getRepayableAmount().principle +
      '00';

    return (
      <div className="withdrawals__action-container">
        <div class="close-cta">
          <Button.Transparent onClick={() => this.toggleWithdrawView('close')}>
            <i class="i i-close" />
          </Button.Transparent>
        </div>
        <div className="title-container">
          <img
            height={16}
            src={`/dist/css/assets/success-tick-green.svg`}
            alt="Loading icon"
          />
          <h3 className="text--secondary">
            <strong>Withdraw Request Successful!</strong>
          </h3>
        </div>
        <p className="disbursal-details text--secondary">
          The money will be settled to your bank account within 5 mins. <br />
          The withdrawal request has been successfully sent to bank.
        </p>
        <div className="flex withdrawal-info">
          <div class="m-r">
            <p className="text--secondary no-margin">Withdrawn Amount</p>
            <span className="text--secondary">
              <strong>
                <Amount value={withdrawalAmount + '00'} />
              </strong>
            </span>
          </div>
          <div class="m-l m-r">
            <p className="text--secondary no-margin">Due Date</p>
            <span className="text--secondary">
              <strong>{moment(selectedDueDate).format('LL')}</strong>
            </span>
          </div>
          <div class="m-l m-r">
            <p className="text--secondary no-margin">Repayable Amount</p>
            <span className="text--secondary">
              <strong>
                <Amount value={repayableAmount} />
              </strong>
            </span>
          </div>
          <div class="m-l m-r">
            <Button.Primary onClick={() => this.toggleWithdrawView('Done')}>
              Done
            </Button.Primary>
            <Button.Transparent
              onClick={() => this.toggleWithdrawView('Another Withdrawal')}
            >
              Another Withdrawal
            </Button.Transparent>
          </div>
        </div>
      </div>
    );
  };

  withdrawalFailedView = () => {
    const { selectedDueDate, withdrawalAmount } = this.state;
    return (
      <div className="withdrawals__action-container">
        <div className="close-cta">
          <Button.Transparent onClick={() => this.toggleWithdrawView('Close')}>
            <i className="i i-close" />
          </Button.Transparent>
        </div>
        <div className="title-container">
          <i className="i i-info-circle text-danger" />
          <h3 className="text--secondary">
            <strong>Withdraw Request Failed!</strong>
          </h3>
        </div>
        <p className="disbursal-details text--secondary">
          Oops, The withdrawal request creation failed due to some internal
          error. <br />
          Incase if any money has been debited from your withdrawal balance, it
          will be added back in sometime.
        </p>
        <div className="flex withdrawal-info">
          <div className="m-r">
            <p className="text--secondary no-margin">Withdrawn Amount</p>
            <span className="text--secondary">
              <strong>
                <Amount value={withdrawalAmount + '00'} />
              </strong>
            </span>
          </div>
          <div className="m-l m-r">
            <p className="text--secondary no-margin">Due Date</p>
            <span className="text--secondary">
              <strong>{selectedDueDate.format('LL')}</strong>
            </span>
          </div>
          <div className="m-l m-r">
            <AsyncBtn.Primary
              class="btn btn-primary"
              disabled={!this.canWithdraw()}
              onClick={() => {
                this.gaEventDispatcher({
                  eventAction: 'Withdraw | Retry',
                });
                return this.withdraw();
              }}
            >
              Retry
            </AsyncBtn.Primary>
            <Button.Transparent
              onClick={() => this.toggleWithdrawView('Cancel')}
            >
              Cancel
            </Button.Transparent>
          </div>
        </div>
      </div>
    );
  };

  getTopSection = currentView => {
    switch (currentView) {
      case VIEWS.WITHDRAW:
        return this.withdrawableSection();
      case VIEWS.WITHDRAW_SUCCESS:
        return this.withdrawalSuccessView();
      case VIEWS.WITHDRAW_FAIL:
        return this.withdrawalFailedView();
    }
  };

  getRightSection = currentView => {
    const withdrawalConfigurationDetails = this.props
      .withdrawalConfigurationDetails.data;
    const withdrawConfigLoading = this.props.withdrawalConfigurationDetails
      .loading;

    const { seedData } = this.props;

    switch (currentView) {
      case VIEWS.WITHDRAW:
      case VIEWS.WITHDRAW_FAIL:
        if (
          !withdrawalConfigurationDetails ||
          withdrawConfigLoading ||
          seedData.loading
        )
          return <CreditSummary loading={true} />;
        else {
          return (
            <CreditSummary
              loading={false}
              internalCreditBalance={this.getInternalCreditBalance()}
              minWithdrawableAmount={this.getMinWithdrawableAmount()}
              maxWithdrawableAmount={this.getMaxWithdrawableAmount()}
              withdrawalConfiguration={withdrawalConfigurationDetails}
            />
          );
        }
      case VIEWS.WITHDRAW_SUCCESS:
        const meta = this.getRepayableAmount();
        return (
          <WithdrawnAmountSummary
            {...meta}
            repaymentDate={moment(this.state.selectedDueDate)}
          />
        );
    }
  };

  getLeftSection = currentView => {
    if (
      currentView !== VIEWS.WITHDRAW ||
      !this.state.showRepaymentDetailsBreakup
    )
      return null;
    const meta = this.getRepayableAmount();
    return (
      <WithdrawnAmountSummary
        {...meta}
        repaymentDate={moment(this.state.selectedDueDate)}
      />
    );
  };

  render() {
    const { currentView, showRepaymentDetailsBreakup } = this.state;

    return (
      <div class="withdrawals__top-summary">
        <CSSTransition
          in={showRepaymentDetailsBreakup}
          timeout={200}
          classNames="display"
          unmountOnExit
        >
          <div
            className={`repayment_details_wrapper ${
              showRepaymentDetailsBreakup && currentView === VIEWS.WITHDRAW
                ? 'show'
                : 'hide'
            }`}
          >
            {this.getLeftSection(currentView)}
          </div>
        </CSSTransition>

        {this.getTopSection(currentView)}
        <CSSTransition
          in={
            !showRepaymentDetailsBreakup ||
            currentView === VIEWS.WITHDRAW_SUCCESS
          }
          timeout={500}
          classNames="display"
          unmountOnExit
        >
          <div
            className={`credit_details_wrapper ${
              !showRepaymentDetailsBreakup ? 'show' : 'hide'
            }`}
          >
            {this.getRightSection(currentView)}
          </div>
        </CSSTransition>
      </div>
    );
  }
}
