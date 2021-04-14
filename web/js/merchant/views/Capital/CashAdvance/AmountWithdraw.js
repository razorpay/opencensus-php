import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import Input from 'common/new-ui/Input';
import Amount from 'common/ui/Amount';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import Popover, { PopoverBody } from 'common/ui/Popover';
import {
  createWithdrawal,
  fetchSeedData,
  fetchWithdrawalConfiguration,
  fetchFunctionalWithdrawalConfigByMerchantID,
  fetchWithdrawals,
} from 'merchant/reducers/capital/withdrawals';
import { fetchBalances } from 'merchant/reducers/capital/repayments';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  CLOSE_OPTIONS,
  STATUSES,
  VIEWS,
  WITHDRAW_ERROR_TYPES,
  COLLECTIONS_PRODUCT_TYPES,
} from './constants';
import CreditSummary from './CreditSummary';
import WithdrawnAmountSummary from './WithdrawnAmountSummary';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import MinWithdrawAmountModal from './MinWithdrawAmountModal';
import EnableAutomatedWithdrawModal from './EnableAutomatedWithdrawModal';
import DisableAutomatedWithdrawModal from './DisableAutomatedWithdrawModal';
import CancelWithdrawalReasons from './CancelWithdrawalReasons';
import trackAutomatedCA from './ga/automated';
import MaxWithdrawError from './MaxWithdrawError';

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
    seedData: state.withdrawals.seedData,
    haveWithdrawals: state.withdrawals.list.data,
  }),
  {
    fetchWithdrawalConfiguration: fetchWithdrawalConfiguration,
    fetchSeedData: fetchSeedData,
    fetchFunctionalWithdrawalConfigByMerchantID,
    showNotification,
    openModal,
    closeModal,
    fetchWithdrawals,
    fetchBalances,
  },
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
      isAutomatedTagTouched: false,
      isAutomatedTagPulsating: false,
      outstandingRepaymentAmount: null,
    };
    this.state = this.initialState;
  }

  componentDidMount() {
    const { fetchSeedData, fetchBalances } = this.props;

    // fetchSeedData();
    this.prefillData();

    fetchBalances({
      product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
      credit_id: this.props.user.current,
    }).then((res) => {
      if (res.data && res.data.balances && res.data.balances.length) {
        let balances = res.data.balances;
        let tempOutstandingRepaymentAmount = balances.reduce(
          (a, b) => parseInt(a) + (parseInt(b['balance_amount']) || 0),
          0,
        );
        this.setState({ outstandingRepaymentAmount: tempOutstandingRepaymentAmount });
      }
    });
  }

  startAutomatedTagPulsating = () => {
    this.setState({ isAutomatedTagPulsating: true });
  };

  stopAutomatedTagPulsating = () => {
    this.setState({ isAutomatedTagPulsating: false });
  };

  gaEventDispatcher = (eventObject) => {
    eventObject['eventCategory'] = 'Dashboard CA - Apply';
    window.rzpAnalytics(eventObject);
  };

  prefillData = () => {
    const maxWithdrawableAmount = this.getMaxWithdrawableAmount();
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
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

  handleWithdrawalAmountChange = (e) => {
    e.persist();

    const amount = parseInt(e.currentTarget.value + '00');
    const errors = [];
    const errorConfig = {
      type: null,
      showReasonCTA: true,
    };

    if (amount > this.getMaxWithdrawableAmount()) {
      errors.push(
        'Max. amount can be withdrawn is ₹' +
          getFormattedAmountNew(this.getMaxWithdrawableAmount()),
      );
      errorConfig.type = WITHDRAW_ERROR_TYPES.MAX_WITHDRAWAL_ERROR;
    } else if (amount < this.getMinWithdrawableAmount()) {
      errors.push(
        'Min. amount can be withdrawn is ₹' +
          getFormattedAmountNew(this.getMinWithdrawableAmount()),
      );
      errorConfig.showReasonCTA = false;
    } else if (amount > this.getInternalCreditBalance()) {
      errors.push(
        'Amount cannot be more than the credit limit(₹' +
          getFormattedAmountNew(this.getMinWithdrawableAmount()) +
          ')',
      );
      errorConfig.type = WITHDRAW_ERROR_TYPES.MIN_WITHDRAWAL_ERROR;
    }

    this.setState({
      withdrawalAmount: e.currentTarget.value,
      withdraw_errors: errors,
      withdrawalErrorConfig: errorConfig,
    });
  };

  handleDueDateChange = (date) => {
    this.setState({
      selectedDueDate: date,
    });
  };

  isDateDisabled = (current) => {
    if (!current) return false;

    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const { start_day_limit, end_day_limit } = withdrawalConfigurationDetails.configuration;

    const startDay = moment().add(start_day_limit, 'days').format('LL');
    const endDay = moment().add(end_day_limit, 'days').format('LL');

    const isValid = current.isAfter(startDay) && current.isBefore(endDay);
    return !isValid;
  };

  getRepayableAmount = () => {
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const { start_day_limit, interest } = withdrawalConfigurationDetails.configuration;
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
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const internalBalance =
      parseInt(withdrawalConfigurationDetails.configuration.internal_credit_limit) -
      parseInt(withdrawalConfigurationDetails.principal_outstanding_balance || 0);
    return internalBalance > 0 ? internalBalance : 0;
  };

  getMaxWithdrawableAmount = () => {
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const {
      max_withdraw_amount,
      internal_credit_limit,
      min_withdraw_amount,
    } = withdrawalConfigurationDetails.configuration;

    const maxAmount = Math.min(parseInt(max_withdraw_amount), this.getInternalCreditBalance());

    const minAmount = parseInt(min_withdraw_amount);
    return maxAmount > 0 ? (maxAmount > minAmount ? maxAmount : minAmount) : 0;
  };

  getMinWithdrawableAmount = () => {
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;

    return parseInt(withdrawalConfigurationDetails.configuration.min_withdraw_amount);
  };

  canWithdraw = () => {
    const { withdrawalAmount, selectedDueDate, withdraw_errors } = this.state;
    return !!withdrawalAmount && !!selectedDueDate && withdraw_errors.length === 0;
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

  handleDisableAutomatedWithdrawalsClick = () => {
    trackAutomatedCA.clickDisableAutomatedWithdrawal({});
    this.props.openModal({
      component: (
        <DisableAutomatedWithdrawModal
          eventCategory="Dashboard FC - Withdraw"
          eventAction="Withdraw | Cancel | Reason"
          closeReasons={CLOSE_OPTIONS}
          onClose={this.props.closeModal}
          openModal={this.props.openModal}
        />
      ),
      size: 'small',
    });
  };

  withdraw = async () => {
    if (!this.canWithdraw()) return;

    this.gaEventDispatcher({
      eventAction: 'Dashboard CA - Withdraw',
      eventLabel: 'Withdraw | Confirm',
    });

    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const { withdrawalAmount, selectedDueDate } = this.state;

    const payload = {
      withdrawal: {
        withdrawal_config_id: withdrawalConfigurationDetails.id,
        owner_id: withdrawalConfigurationDetails.owner_id,
        owner_type: withdrawalConfigurationDetails.owner_type,
        application_id: withdrawalConfigurationDetails.application_id,
        application_number: withdrawalConfigurationDetails.application_number,
        amount: withdrawalAmount * 100,
        due_date: moment(selectedDueDate).utc().format(),
        drawn_at: moment().utc().format(),
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
          showRepaymentDetailsBreakup: false,
        });
        this.props.fetchWithdrawalConfiguration({
          id: withdrawalConfigurationDetails.id,
        });
        this.fetchWithdrawals();
        this.props.showNotification({
          type: 'success',
          message: `Withdrawal of ₹${withdrawalAmount} requested.`,
        });
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
      this.props.showNotification({
        type: 'error',
        message: 'Error Occurred while processing your withdrawal request.',
      });
    }
  };

  closeReasonsModal = () => {
    this.props.closeModal();
    this.setState(
      {
        isConfirmingWithdraw: false,
      },
      this.prefillData,
    );
  };

  cancelWithdraw = () => {
    this.props.openModal({
      component: (
        <CancelWithdrawalReasons
          eventCategory="Dashboard FC - Withdraw"
          eventAction="Withdraw | Cancel | Reason"
          closeReasons={CLOSE_OPTIONS}
          onClose={this.closeReasonsModal}
        />
      ),
      size: 'small',
    });
  };

  toggleWithdrawView = (fromWhere) => {
    this.gaEventDispatcher({
      eventAction: `Withdraw | ${fromWhere}`,
    });
    this.setState(this.initialState, this.prefillData);
  };

  toggleBreakup = () => {
    this.gaEventDispatcher({
      eventAction: this.state.showRepaymentDetailsBreakup
        ? 'Withdraw | Hide Breakup'
        : 'Withdraw | Show Breakup',
    });
    this.setState((prevState) => ({
      showRepaymentDetailsBreakup: !prevState.showRepaymentDetailsBreakup,
    }));
  };

  openEnableAutomatedWithdrawModal = () => {
    const { interest, principle } = this.getRepayableAmount();
    const {
      withdrawalConfigurationDetails: {
        data: {
          configuration: { max_withdraw_amount, end_day_limit },
        },
      },
    } = this.props;
    trackAutomatedCA.openEnableAutomatedWithdrawModal({});
    this.props.openModal({
      component: (
        <EnableAutomatedWithdrawModal
          openModal={this.props.openModal}
          onClose={this.props.closeModal}
          interest={interest}
          principle={principle}
          max_withdraw_amount={max_withdraw_amount}
          end_day_limit={end_day_limit}
          startAutomatedTagPulsating={this.startAutomatedTagPulsating}
        />
      ),
      size: 'small',
    });
  };

  handleEnableNowClickForAutomatedWithdrawal = () => {
    trackAutomatedCA.clickAutomatedWithdrawalEnable();
    this.openEnableAutomatedWithdrawModal();
  };

  handleEnableNowClickForResults = () => {
    trackAutomatedCA.clickAutomatedWithdrawalEnableForResults({});
    this.openEnableAutomatedWithdrawModal();
  };

  handleBlur = () => {
    this.setState({
      isTouched: true,
    });
  };

  isMinWithdrawalBalanceAvailable = () => {
    return this.getInternalCreditBalance() > this.getMinWithdrawableAmount();
  };

  isMaxWithdrawalBalanceAvailable = () => {
    return this.getMaxWithdrawableAmount() > this.getInternalCreditBalance();
  };

  isLenderApolloFinvest = () => {
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;

    return (
      withdrawalConfigurationDetails &&
      withdrawalConfigurationDetails.configuration.custom_partner_fields.partner_id ===
        'APOLLOFINVEST'
    );
  };

  handleAutomatedTextMouseOver = () => {
    trackAutomatedCA.hoverAutomatedText({});
    const { isAutomatedTagPulsating } = this.state;
    if (isAutomatedTagPulsating) {
      this.stopAutomatedTagPulsating();
    }
  };

  getWithdrawCTA = () => {
    const {
      seedData,
      withdrawalConfigurationDetails: {
        data: withdrawalConfigurationDetails,
        loading: withdrawConfigLoading,
      },
    } = this.props;

    if (!withdrawalConfigurationDetails || withdrawConfigLoading || seedData.loading) return null;

    const canWithdraw = this.canWithdraw();
    const { withdrawalErrorConfig = {} } = this.state;
    const showReasonCTA = !canWithdraw && withdrawalErrorConfig.showReasonCTA;

    return (
      <React.Fragment>
        {!this.state.isConfirmingWithdraw ? (
          <React.Fragment>
            <AsyncBtn.Primary
              class="btn btn-primary withdraw-now"
              disabled={!canWithdraw}
              onClick={this.confirmWithdraw}
            >
              Withdraw Now
            </AsyncBtn.Primary>
            {showReasonCTA ? (
              <AsyncBtn.Transparent className="btn btn-link" onClick={this.openWithdrawErrorModal}>
                View Reason
              </AsyncBtn.Transparent>
            ) : null}
          </React.Fragment>
        ) : (
          <div className="flex">
            <AsyncBtn.Primary
              class="btn btn-primary"
              disabled={!this.canWithdraw()}
              onClick={this.withdraw}
            >
              Confirm
            </AsyncBtn.Primary>
            <button className="btn btn-link" onClick={this.cancelWithdraw}>
              Cancel
            </button>
          </div>
        )}
      </React.Fragment>
    );
  };

  openWithdrawErrorModal = () => {
    const { repayDues, closeModal, openModal } = this.props;
    const {
      withdrawalAmount,
      withdrawalErrorConfig: { type: withdrawalErrorType },
    } = this.state;

    this.gaEventDispatcher({
      eventAction: `Low Balance | Why can't I withdraw`,
    });

    const props = {
      amount: withdrawalAmount * 100,
      closeModal: closeModal,
      repayDues: repayDues,
      trackGA: this.gaEventDispatcher,
    };

    const COMPONENT =
      withdrawalErrorType === WITHDRAW_ERROR_TYPES.MIN_WITHDRAWAL_ERROR
        ? MinWithdrawAmountModal
        : MaxWithdrawError;

    if (withdrawalErrorType === WITHDRAW_ERROR_TYPES.MIN_WITHDRAWAL_ERROR) {
      props.minWithdrawalAmount = this.getMinWithdrawableAmount();
    } else {
      props.maxWithdrawalAmount = this.getMaxWithdrawableAmount();
    }

    openModal({
      size: 'small',
      component: <COMPONENT {...props} />,
    });
  };

  automatedWithdrawTooltipView = () => {
    const {
      withdrawalConfigurationDetails: {
        data: {
          configuration: { max_withdraw_amount, end_day_limit },
        },
      },
    } = this.props;
    return (
      <div className="automated-withdraw-tooltip">
        <div className="flex automated-withdraw-tooltip--enabled">
          <div clasName="automated-withdraw-tooltip--enabled--heading">Automated Withdrawals</div>
          <div className="automated-withdraw-tooltip--enabled-content">
            <div className="enabled-dot" />
            ENABLED
          </div>
        </div>
        <div className="automated-withdraw-tooltip--enabled-summary">
          Your next withdrawal will be automatically credited to your bank account as soon as you
          repay your entire due amount.
        </div>
        <div className="automated-withdraw-tooltip--details">
          <div className="automated-withdraw-tooltip--details-row">
            <div>Maximum Withdrawable Amount</div>
            <Amount
              className="automated-withdraw-tooltip--max-amount"
              value={Number(max_withdraw_amount)}
            />
          </div>
          <div className="automated-withdraw-tooltip--details-row">
            <div>Maximum Tenure</div>
            <div className="automated-withdraw-tooltip--details-row-month">
              {end_day_limit} days
            </div>
          </div>
        </div>
        <div
          className="automated-withdraw-tooltip--disable-withdrawal"
          onClick={this.handleDisableAutomatedWithdrawalsClick}
        >
          Disable Automated Withdrawals
        </div>
      </div>
    );
  };

  withdrawableSection = () => {
    const {
      withdrawalAmount,
      isConfirmingWithdrawalTC,
      selectedDueDate,
      showRepaymentDetailsBreakup,
      isAutomatedTagPulsating,
    } = this.state;
    const {
      user,
      withdrawalConfigurationDetails: { data: { automated_loc } } = {
        data: { automated_loc: false },
      },
    } = this.props;
    const hasDueDateAndWithdrawnAmount = selectedDueDate && withdrawalAmount;
    const { principle = 0, interest = 0 } = hasDueDateAndWithdrawnAmount
      ? this.getRepayableAmount()
      : {};
    const repayableAmount = getFormattedAmountNew((principle + interest) * 100, true);
    const showFirstWithdrawalOffer = this.getFirstWithdrawalOffer();
    const withdrawalConfigStatus = this.props.withdrawalConfigurationDetails.data.status;

    return (
      <div className="withdrawals__action-container card flex">
        {showFirstWithdrawalOffer ? (
          <React.Fragment>
            <div class="cash-advance-first-withdrawal">
              <i class="i i-offer2" />
              Doing your first withdrawal? Get 100% interest waived on withdrawal up to ₹50,000.
              <span>
                {' '}
                View offer details
                <p>
                  <strong>
                    <i class="i i-offer2" /> Cashback Offer
                  </strong>
                  <br />
                  <br />
                  100% cashback of all the interest on your first withdrawal up to ₹50,000 and
                  maximum of 10 days repayment period.
                  <br />
                  <br />
                  All the interest charged will be deposited back into your bank account within a
                  day of repayment
                </p>
              </span>
            </div>
            <div class="cash-advance-first-withdrawal-background"></div>
          </React.Fragment>
        ) : null}
        <div class="no-margin full-width" style={{ position: 'relative' }}>
          {withdrawalConfigStatus && withdrawalConfigStatus.toLowerCase() === 'onhold' ? (
            <div>
              <div className="flex end-align">
                <i className="i i-error withdrawals__onhold-icon" />
                <h3 className="withdrawals__onhold-title text--secondary">
                  Withdrawals are on hold!
                </h3>
              </div>
              <p className="withdrawals__onhold-summary">
                Sorry, Your withdrawals are temporarily blocked due to missed repayments. Please
                repay to continue <br /> using your credit line.
              </p>
            </div>
          ) : (
            <div>
              <div className="flex" style={{ marginBottom: 8, alignItems: 'center' }}>
                <h3 className="title text--secondary">Withdraw Amount</h3>
                {user.isAutomatedLOCEligible && automated_loc && (
                  <div style={{ marginLeft: 12 }} className="automated-popover-container">
                    <div className={`${isAutomatedTagPulsating ? 'pulsating-ring' : ''}`}>
                      <div
                        className="automated-tag"
                        onMouseOver={this.handleAutomatedTextMouseOver}
                      >
                        AUTOMATED
                      </div>
                    </div>
                    <Popover className="automated-popover" align="bottom" theme="dark">
                      <PopoverBody>{this.automatedWithdrawTooltipView()}</PopoverBody>
                    </Popover>
                  </div>
                )}
              </div>
              <div class="full-width no-margin" style={{ position: 'absolute' }}>
                {this.getWithdrawalForm(withdrawalAmount)}
              </div>
              {hasDueDateAndWithdrawnAmount && (
                <div class="repayable-amount-hint">
                  <strong>{repayableAmount}</strong>
                  <span class="repayable-helper-text">&nbsp; will be the repayable amount</span>
                  {user.isAutomatedLOCEligible && !automated_loc && (
                    <div className="automated-withdrawal flex">
                      <div className="automated-withdrawal-wrapper">
                        <div style={{ fontSize: 14 }}>Automate your withdrawals</div>
                        <Button.Transparent
                          class="text-small enable-now-btn"
                          onClick={this.handleEnableNowClickForAutomatedWithdrawal}
                        >
                          Enable Now
                        </Button.Transparent>
                      </div>

                      <div className="rounded-rectange" />
                    </div>
                  )}
                  <div class="flex">
                    <div class="text-small full-width no-margin text-strong p-r">
                      <strong>
                        {showRepaymentDetailsBreakup ? (
                          <Button.Transparent onClick={this.toggleBreakup}>
                            <i class="i i-chevron-left" />
                            Hide Breakup
                          </Button.Transparent>
                        ) : (
                          <Button.Transparent onClick={this.toggleBreakup}>
                            Show Breakup
                            <i class="i i-chevron-right" />
                          </Button.Transparent>
                        )}
                        {this.state.showRepaymentDetailsBreakup && (
                          <Button.Transparent class="pull-right" onClick={this.toggleBreakup}>
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
          )}
        </div>
      </div>
    );
  };

  getWithdrawalForm(withdrawalAmount) {
    return (
      <div className="flex withdrawal-form-container">
        <Input.Group
          label="I want to withdraw"
          className="InputGroup--inline Input--vTop no-margin"
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
          {this.state.withdraw_errors.length > 0 && this.state.isTouched && (
            <div class="text-danger error-message">
              {this.state.withdrawalAmount && this.state.withdraw_errors[0]}
            </div>
          )}
        </Input.Group>
        <Input.ToCalendar
          required={false}
          className="Input--vTop no-margin"
          data-name="date_slot"
          format="DD-MM-YYYY"
          label={
            <div className="custom-parent">
              I will repay the amount by
              <small
                className="help-content small"
                style={{ paddingLeft: '4px', position: 'relative' }}
              >
                <i
                  className="i i-info-outline"
                  // onMouseOver={() => trackMouseOver('tenure')}
                />
                <Popover align="top" theme="dark" parentQuerySelector=".withdrawals__top-summary">
                  <PopoverBody>
                    <div className="text-center">
                      Your equated repayments will start from tomorrow
                    </div>
                  </PopoverBody>
                </Popover>
              </small>
            </div>
          }
          // defaultValue={this.state.selectedDueDate}
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
        <div className="withdrawal-cta-container">{this.getWithdrawCTA()}</div>
      </div>
    );
  }

  withdrawalSuccessView = () => {
    const { selectedDueDate, withdrawalAmount } = this.state;
    const {
      user,
      withdrawalConfigurationDetails: { data: { automated_loc } } = {
        data: { automated_loc: false },
      },
    } = this.props;
    const { interest, principle } = this.getRepayableAmount();
    const repayableAmount = parseFloat((interest + principle) * 100).toFixed(2);

    return (
      <div className="withdrawals__action-container card">
        <div class="close-cta">
          <Button.Transparent onClick={() => this.toggleWithdrawView('close')}>
            <i class="i i-close" />
          </Button.Transparent>
        </div>
        <div className="title-container">
          <img height={16} src={`/dist/css/assets/success-tick-green.svg`} alt="Loading icon" />
          <h3 className="text--secondary">
            <strong>Withdrawal Request Successful!</strong>
          </h3>
        </div>
        <p className="disbursal-details text--secondary">
          The money will be transferred to your bank account in a few hours. <br />
          The withdrawal request has been successfully sent to bank.
        </p>
        <div className="flex withdrawal-info">
          <div class="withdrawal__amount">
            <p className="text--secondary no-margin">Withdrawn Amount</p>
            <span className="text--secondary">
              <strong>
                <Amount
                  value={withdrawalAmount + '00'}
                  parentQuerySelector=".withdrawals__top-summary"
                />
              </strong>
            </span>
          </div>
          <div class="withdrawal__date">
            <p className="text--secondary no-margin">Due Date</p>
            <span className="text--secondary">
              <strong>{moment(selectedDueDate).format('LL')}</strong>
            </span>
          </div>
          <div class="withdrawal__repayable">
            <p className="text--secondary no-margin">Repayable Amount</p>
            <span className="text--secondary">
              <strong>
                <Amount value={repayableAmount} parentQuerySelector=".withdrawals__top-summary" />
              </strong>
            </span>
          </div>
          <div class="withdrawal__ctas">
            <Button.Primary onClick={() => this.toggleWithdrawView('Done')}>Done</Button.Primary>
            <Button.Transparent onClick={() => this.toggleWithdrawView('Another Withdrawal')}>
              Another Withdrawal
            </Button.Transparent>
          </div>
        </div>
        {user.isAutomatedLOCEligible && (
          <div className="automated-withdrawal-enable-text">
            <div className="rounded-square" />
            {automated_loc ? (
              <div className="automated-withdrawal-description">
                Automated withdrawals are activated for your account. The next withdrawal will be
                automatically credited to your bank account once you repay your entire due amount.
              </div>
            ) : (
              <div className="automated-withdrawal-description">
                <div style={{ marginRight: 12 }}>Automate your withdrawals</div>
                <Button.Transparent
                  class="enable-now-btn"
                  onClick={this.handleEnableNowClickForResults}
                >
                  Enable Now
                </Button.Transparent>
              </div>
            )}
          </div>
        )}
      </div>
    );
  };

  withdrawalFailedView = () => {
    const { selectedDueDate, withdrawalAmount } = this.state;
    return (
      <div className="withdrawals__action-container card">
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
          Oops, The withdrawal request creation failed due to some internal error. <br />
          Incase if any money has been debited from your withdrawal balance, it will be added back
          in sometime.
        </p>
        <div className="flex withdrawal-info">
          <div className="withdrawal__amount">
            <p className="text--secondary no-margin">Withdrawn Amount</p>
            <span className="text--secondary">
              <strong>
                <Amount
                  value={withdrawalAmount + '00'}
                  parentQuerySelector=".withdrawals__top-summary"
                />
              </strong>
            </span>
          </div>
          <div className="withdrawal__date">
            <p className="text--secondary no-margin">Due Date</p>
            <span className="text--secondary">
              <strong>{selectedDueDate.format('LL')}</strong>
            </span>
          </div>
          <div className="withdrawal__ctas">
            <AsyncBtn.Primary
              class="btn btn-primary retry-btn"
              disabled={!this.canWithdraw()}
              onClick={() => {
                this.gaEventDispatcher({
                  eventAction: 'Withdraw | Retry',
                });
                return this.withdraw();
              }}
            >
              Retry Withdrawal
            </AsyncBtn.Primary>
            <Button.Transparent
              className="cancel-btn"
              onClick={() => this.toggleWithdrawView('Cancel')}
            >
              Close
            </Button.Transparent>
          </div>
        </div>
      </div>
    );
  };

  getTopSection = (currentView) => {
    switch (currentView) {
      case VIEWS.WITHDRAW:
        return this.withdrawableSection();
      case VIEWS.WITHDRAW_SUCCESS:
        return this.withdrawalSuccessView();
      case VIEWS.WITHDRAW_FAIL:
        return this.withdrawalFailedView();
    }
  };

  getRightSection = (currentView) => {
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const withdrawConfigLoading = this.props.withdrawalConfigurationDetails.loading;

    const { seedData, user, history, haveWithdrawals = false } = this.props;

    switch (currentView) {
      case VIEWS.WITHDRAW:
      case VIEWS.WITHDRAW_FAIL:
        if (!withdrawalConfigurationDetails || withdrawConfigLoading || seedData.loading)
          return <CreditSummary loading={true} />;
        else {
          return (
            <CreditSummary
              loading={false}
              internalCreditBalance={this.getInternalCreditBalance()}
              withdrawalConfiguration={withdrawalConfigurationDetails}
              user={user}
              history={history}
              haveWithdrawals={haveWithdrawals}
            />
          );
        }
      case VIEWS.WITHDRAW_SUCCESS:
        const meta = this.getRepayableAmount();
        return (
          <WithdrawnAmountSummary {...meta} repaymentDate={moment(this.state.selectedDueDate)} />
        );
    }
  };

  getLeftSection = (currentView) => {
    const { withdrawalConfigurationDetails: { data = null } = {} } = this.props;
    const showFirstWithdrawalOffer = this.getFirstWithdrawalOffer();

    if (!data) return null;

    const meta = this.getRepayableAmount();
    return (
      <WithdrawnAmountSummary
        {...meta}
        repaymentDate={moment(this.state.selectedDueDate)}
        showFirstWithdrawalOffer={showFirstWithdrawalOffer}
      />
    );
  };

  getFirstWithdrawalOffer = () => {
    return (
      !this.props.haveWithdrawals?.length &&
      this.props.user.isFeatureEnabled('loc_first_withdrawal')
    );
  };

  render() {
    const { currentView, showRepaymentDetailsBreakup } = this.state;
    const showFirstWithdrawalOffer = this.getFirstWithdrawalOffer();

    return (
      <div
        class={`withdrawals__top-summary${showRepaymentDetailsBreakup ? ' move-right' : ''}${
          showFirstWithdrawalOffer ? ' show-first-withdrawal-offer' : ''
        }`}
      >
        <div
          className={`repayment_details_wrapper card ${
            showRepaymentDetailsBreakup ? 'fade-in' : 'fade-out'
          }`}
        >
          {this.getLeftSection(currentView)}
        </div>
        <div className="withdrawals__top-summary-wrapper">
          {this.getTopSection(currentView)}
          <div className="credit_details_wrapper card">{this.getRightSection(currentView)}</div>
        </div>
      </div>
    );
  }
}
