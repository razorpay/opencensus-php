import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import moment from 'moment';

import Input from 'common/new-ui/Input';
import Amount from 'common/ui/Amount';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { getFormattedAmountNew, titleCase } from 'common/utils/rzp-utils';
import Popover, { PopoverBody } from 'common/ui/Popover';
import {
  createWithdrawal,
  fetchSeedData,
  fetchWithdrawalConfiguration,
  fetchFunctionalWithdrawalConfigByMerchantID,
  fetchWithdrawals,
  fetchInstallments,
} from 'merchant/reducers/capital/withdrawals';
import { fetchMerchantDetails } from 'merchant/reducers/capital/migrations';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  CLOSE_OPTIONS,
  STATUSES,
  VIEWS,
  WITHDRAW_ERROR_TYPES,
  COLLECTIONS_PRODUCT_TYPES,
  COLLECTIONS_PAYMENT_REFERENCE_TYPE,
  COLLECTIONS_BALANCE_TYPE,
  CASH_ADVANCE_FIRST_LOGIN_KEY,
  REPAYMENT_FREQUENCY_TYPES,
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
import Repayments from 'merchant/models/Capital/Repayments';
import Withdrawal from 'merchant/models/Capital/Withdrawals';
import { loadCheckoutScript, checkifDateExpired } from 'merchant/views/Capital/utils';
import { fetchRepayments } from 'merchant/reducers/capital/repayments';
import Spinner from 'common/ui/Spinner';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import GromorAgreementModal from 'merchant/views/Capital/components/Modals/GromorAgreementModal';
import {
  trackHideBreakup,
  trackRepayDateClicked,
  trackRepayDateUpdated,
  trackShowBreakup,
  trackWithdrawAmountUpdated,
  trackWithdrawNow,
  trackWithdrawNowCancel,
  trackWithdrawNowConfirm,
  trackWithdrawStatus,
} from './TrackEvents/trackEvents';
import { getItem, setItem } from 'common/utils/localStorage';

function updateRepaymentData(data, onResolve, onReject) {
  const repayment = new Repayments();
  return repayment.updateRepayment(data).then(onResolve).catch(onReject);
}

const isBalanceTypePrincipal = ({ balance_type }) =>
  balance_type === COLLECTIONS_BALANCE_TYPE.BALANCE_TYPE_PRINCIPAL;

const isBalanceTypeInterest = ({ balance_type }) =>
  balance_type === COLLECTIONS_BALANCE_TYPE.BALANCE_TYPE_INTEREST;

const computeAmount = (balances) => {
  return balances.reduce(
    (amountBreakup, { breakup_amount = 0 }) => amountBreakup + +Number(breakup_amount),
    0,
  );
};

const getRepaidAmountBreakup = (balances) => {
  const principalBalances = balances.filter(isBalanceTypePrincipal);
  const interestBalances = balances.filter(isBalanceTypeInterest);
  const principalRepaid = computeAmount(principalBalances);
  const interestRepaid = computeAmount(interestBalances);

  return {
    principalRepaid,
    interestRepaid,
  };
};

const parseRepaymentSchedule = (todayTimestamp, array) => {
  let data = {};
  array.forEach((item) => {
    if (parseInt(item.repayment_date, 10) === todayTimestamp) {
      const amount =
        parseInt(item.payment, 10) -
        (parseInt(item.interest_collected ? item.interest_collected : 0, 10) +
          parseInt(item.principal_collected ? item.principal_collected : 0, 10));

      data = {
        amount,
      };
    }
  });

  data = { ...data, latestRepaymentDone: data.amount ? data.amount <= 0 : true };
  return data;
};

const parseRepaymentBreakup = (repayments) => {
  const breakup = repayments.breakups;
  const { principalRepaid, interestRepaid } = getRepaidAmountBreakup(breakup);

  return {
    totalRepaid: principalRepaid + interestRepaid || 0,
    principalRepaid,
    interestRepaid,
    repaymentMethod: repayments.payment_meta ? repayments.payment_meta.method : '',
  };
};

const computeMaxDueDate = (limit) => {
  return moment().add(limit - 1, 'days');
};

const checkIfFirstCashAdvanceLogin = () => {
  const val = getItem(CASH_ADVANCE_FIRST_LOGIN_KEY);
  if (val === null) return true;
  return JSON.parse(val);
};

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
    seedData: state.withdrawals.seedData,
    haveWithdrawals: state.withdrawals.list.data,
    merchantGromorEsignDetails: state.migrations.merchantGromorEsignDetails,
  }),
  {
    fetchWithdrawalConfiguration,
    fetchSeedData,
    fetchFunctionalWithdrawalConfigByMerchantID,
    showNotification,
    openModal,
    closeModal,
    fetchWithdrawals,
    fetchInstallments,
    fetchRepayments,
    fetchMerchantDetails,
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
      latestRepaymentDone: true,
      isRepaymentLoading: 'LOADING',
      outstandingRepayment: {
        amount: null,
      },
      repaymentBreakdown: {
        repaymentMethod: '',
        principalRepaid: 0,
        interestRepaid: 0,
        totalRepaid: 0,
      },
      showRepaymentInfoTooltip: checkIfFirstCashAdvanceLogin(),
    };
    this.state = this.initialState;
  }

  componentDidMount() {
    const {
      withdrawalConfigurationDetails: {
        data: { status, repayment_frequency, comments: { reason = '' } = {} },
      } = {},
    } = this.props;
    const withdrawalInstance = new Withdrawal();
    const repaymentInstance = new Repayments();

    // Setting localStorage key for repayment tooltip
    if (
      repayment_frequency &&
      repayment_frequency === REPAYMENT_FREQUENCY_TYPES.BIMONTHLY &&
      checkIfFirstCashAdvanceLogin()
    ) {
      setItem(CASH_ADVANCE_FIRST_LOGIN_KEY, false);

      document.querySelector('body').addEventListener('click', this.hideRepaymentTooltip);
    }

    // fetchSeedData();
    this.prefillData();
    const isApplicationAtHold =
      status === 'ONHOLD' && reason !== 'cld_risk_policy' && reason !== 'end_of_credit_line_tenure';

    if (isApplicationAtHold) {
      const currentDate = new Date();
      const startOfDay = new Date(
        currentDate.getFullYear(),
        currentDate.getMonth(),
        currentDate.getDate(),
      );
      const unixTimestamp = startOfDay / 1000;

      Promise.all([
        this.fetchInstallment(withdrawalInstance)
          .then(({ data: { repayment_schedule = [] } = {} }) => {
            const { latestRepaymentDone, amount } = parseRepaymentSchedule(
              unixTimestamp,
              repayment_schedule,
            );

            return {
              latestRepaymentDone,
              outstandingRepayment: {
                ...this.state.outstandingRepayment,
                amount,
              },
            };
          })
          .catch(() => {
            return { latestRepaymentDone: false };
          }),
        this.fetchLatestRepaymentBreakdown(repaymentInstance)
          .then(({ data: { repayments = [] } = {} } = {}) => {
            if (!repayments[0].breakups || !repayments[0].breakups.length)
              return Promise.reject('No Repayments');

            return parseRepaymentBreakup(repayments[0]);
          })
          .catch(() => {
            return {
              repaymentMethod: '',
              principalRepaid: 0,
              interestRepaid: 0,
              totalRepaid: 0,
            };
          }),
      ])
        .then((response) => {
          const {
            latestRepaymentDone = false,
            outstandingRepayment = this.state.outstandingRepayment,
          } = response[0];

          this.setState({
            isRepaymentLoading: false,
            repaymentBreakdown: response[1],
            latestRepaymentDone,
            outstandingRepayment,
          });
        })
        .catch(() => {
          this.setState({
            latestRepaymentDone: false,
            isRepaymentLoading: false,
          });
        });
    } else {
      // eslint-disable-next-line
      this.setState({
        latestRepaymentDone: false,
        isRepaymentLoading: false,
      });
    }
  }

  componentWillUnmount() {
    document.querySelector('body').removeEventListener('click', this.hideRepaymentTooltip);
  }

  hideRepaymentTooltip = () => this.handleRepaymentInfoTooltipHover(false);

  fetchInstallment = (withdrawalInstance) => {
    return withdrawalInstance.fetchInstallments({
      owner_id: this.props.user.current,
      from: moment().startOf('day').unix(),
      to: moment().add(2, 'days').unix(),
    });
  };

  fetchLatestRepaymentBreakdown = (repaymentInstance) => {
    return repaymentInstance.fetchRepayments({
      product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
      credit_id: this.props.user.current,
      order_by_type: 'ORDER_BY_TYPE_DESC',
      order_by_field: 'ORDER_BY_FIELD_CREATED_AT',
      count: 1,
    });
  };

  startAutomatedTagPulsating = () => {
    this.setState({ isAutomatedTagPulsating: true });
  };

  stopAutomatedTagPulsating = () => {
    this.setState({ isAutomatedTagPulsating: false });
  };

  gaEventDispatcher = (eventObject) => {
    eventObject.eventCategory = 'Dashboard CA - Apply';
    window.rzpAnalytics(eventObject);
  };

  prefillData = () => {
    const maxWithdrawableAmount = this.getMaxWithdrawableAmount();
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const { end_day_limit } = withdrawalConfigurationDetails.configuration;
    const maxDueDate = computeMaxDueDate(end_day_limit);

    if (maxWithdrawableAmount > this.getMinWithdrawableAmount()) {
      this.setState({
        withdrawalAmount: maxWithdrawableAmount / 100,
        selectedDueDate: maxDueDate.endOf('day'),
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
    trackWithdrawAmountUpdated(e.currentTarget.value);
    e.persist();

    const amount = parseInt(`${e.currentTarget.value}00`, 10);
    const errors = [];
    const errorConfig = {
      type: null,
      showReasonCTA: true,
    };

    if (amount > this.getMaxWithdrawableAmount()) {
      errors.push(
        `Max. amount can be withdrawn is ₹${getFormattedAmountNew(
          this.getMaxWithdrawableAmount(),
        )}`,
      );
      errorConfig.type = WITHDRAW_ERROR_TYPES.MAX_WITHDRAWAL_ERROR;
    } else if (amount < this.getMinWithdrawableAmount()) {
      errors.push(
        `Min. amount can be withdrawn is ₹${getFormattedAmountNew(
          this.getMinWithdrawableAmount(),
        )}`,
      );
      errorConfig.showReasonCTA = false;
    } else if (amount > this.getInternalCreditBalance()) {
      errors.push(
        `Amount cannot be more than the credit limit(₹${getFormattedAmountNew(
          this.getMinWithdrawableAmount(),
        )})`,
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
    trackRepayDateUpdated(moment(date).format('DD-MM-YYYY'));
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
    const { interest } = withdrawalConfigurationDetails.configuration;
    const startDay = moment();

    const selectedDate = this.getDueDate().endOf('day');

    const diffDays = selectedDate.diff(startDay, 'days');

    const roi = parseInt(interest, 10) / 100;

    const amount = {
      principle: parseInt(this.state.withdrawalAmount, 10),
      interest: (diffDays * parseInt(this.state.withdrawalAmount, 10) * roi) / 100,
      diffDays,
      roi,
    };

    return amount;
  };

  getInternalCreditBalance = () => {
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const internalBalance =
      parseInt(withdrawalConfigurationDetails.configuration.internal_credit_limit, 10) -
      parseInt(withdrawalConfigurationDetails.principal_outstanding_balance || 0, 10);
    return internalBalance > 0 ? internalBalance : 0;
  };

  getMaxWithdrawableAmount = () => {
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const {
      max_withdraw_amount,
      min_withdraw_amount,
    } = withdrawalConfigurationDetails.configuration;

    const maxAmount = Math.min(parseInt(max_withdraw_amount, 10), this.getInternalCreditBalance());

    const minAmount = parseInt(min_withdraw_amount, 10);
    return maxAmount > 0 ? (maxAmount > minAmount ? maxAmount : minAmount) : 0;
  };

  getMinWithdrawableAmount = () => {
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;

    return parseInt(withdrawalConfigurationDetails.configuration.min_withdraw_amount, 10);
  };

  canWithdraw = () => {
    const { withdrawalAmount, selectedDueDate, withdraw_errors } = this.state;
    return !!withdrawalAmount && !!selectedDueDate && withdraw_errors.length === 0;
  };

  confirmWithdraw = () => {
    const { withdrawalAmount, selectedDueDate } = this.state;
    trackWithdrawNow({
      amount: withdrawalAmount,
      date: moment(selectedDueDate).format('DD-MM-YYYY'),
    });

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

  handleRazorpayCheckoutPayment = (RepaymentInstance, paymentParams) => {
    const requests = [
      loadCheckoutScript(),
      RepaymentInstance.createRepayment({
        ...paymentParams,
        payment_reference_type: COLLECTIONS_PAYMENT_REFERENCE_TYPE.ORDER,
        amount: Number(this.state.outstandingRepayment.amount),
      }),
    ];

    return Promise.all(requests).then(([_, repaymentDetails]) => {
      const { data: { payment_reference_id: order_id } = {} } = repaymentDetails;
      if (!order_id) return Promise.reject(new Error('No Order Id found'));

      return new Promise((resolve, reject) => {
        // eslint-disable-next-line
        const razorpayInstance = new Razorpay({
          order_id,
          handler: (response) => updateRepaymentData(response, resolve, reject),
          modal: {
            ondismiss: reject,
          },
        });
        razorpayInstance.open();
      });
    });
  };

  handlePayNowClick = () => {
    const RepaymentInstance = new Repayments();
    const paymentParams = {
      credit_id: this.props.user.current,
      product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
      currency: 'INR',
    };

    return (
      Promise.resolve()
        .then(() => this.handleRazorpayCheckoutPayment(RepaymentInstance, paymentParams))
        // eslint-disable-next-line
        .then(({ data }) => {
          if (!data.breakups || !data.breakups.length)
            return Promise.reject('No Repayment Details');
          const { principalRepaid, interestRepaid } = getRepaidAmountBreakup(data.breakups);

          const response = {
            totalRepaid: principalRepaid + interestRepaid || 0,
            principalRepaid,
            interestRepaid,
            repaymentMethod: data?.payment_meta ? data?.payment_meta?.method : '',
          };

          this.setState({
            repaymentBreakdown: response,
            latestRepaymentDone: true,
          });
        })
        .catch(() => {
          this.props.showNotification({
            type: 'error',
            message: 'Oops, Your repayment has been failed due to some internal error.',
          });
          this.setState({
            latestRepaymentDone: false,
            repaymentBreakdown: {
              repaymentMethod: '',
              principalRepaid: 0,
              interestRepaid: 0,
              totalRepaid: 0,
            },
          });
        })
    );
  };

  withdraw = async () => {
    if (!this.canWithdraw()) return;

    this.gaEventDispatcher({
      eventAction: 'Dashboard CA - Withdraw',
      eventLabel: 'Withdraw | Confirm',
    });

    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const { withdrawalAmount, selectedDueDate } = this.state;

    const dueDate = this.getDueDate();

    trackWithdrawNowConfirm({
      amount: withdrawalAmount,
      date: moment(selectedDueDate).format('DD-MM-YYYY'),
    });

    const payload = {
      withdrawal: {
        withdrawal_config_id: withdrawalConfigurationDetails.id,
        owner_id: withdrawalConfigurationDetails.owner_id,
        owner_type: withdrawalConfigurationDetails.owner_type,
        application_id: withdrawalConfigurationDetails.application_id,
        application_number: withdrawalConfigurationDetails.application_number,
        amount: withdrawalAmount * 100,
        due_date: dueDate.utc().format(),
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
        trackWithdrawStatus({
          amount: withdrawalAmount,
          date: moment(selectedDueDate).format('DD-MM-YYYY'),
          status: 'success',
        });
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
        trackWithdrawStatus({
          amount: withdrawalAmount,
          date: moment(selectedDueDate).format('DD-MM-YYYY'),
          status: 'fail',
        });
        this.setState({
          currentView: VIEWS.WITHDRAW_FAIL,
          showRepaymentDetailsBreakup: false,
        });
        this.gaEventDispatcher({
          eventAction: 'Withdraw | Fail',
        });
      }
    } catch (e) {
      trackWithdrawStatus({
        amount: withdrawalAmount,
        date: moment(selectedDueDate).format('DD-MM-YYYY'),
        status: 'fail',
      });
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
    const { withdrawalAmount, selectedDueDate } = this.state;
    trackWithdrawNowCancel({
      amount: withdrawalAmount,
      date: moment(selectedDueDate).format('DD-MM-YYYY'),
    });

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
    const { showRepaymentDetailsBreakup } = this.state;
    if (showRepaymentDetailsBreakup) {
      trackHideBreakup();
    } else trackShowBreakup(this.getRepayableAmount());

    this.gaEventDispatcher({
      eventAction: showRepaymentDetailsBreakup
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

  getRepaymentFrequency = () => {
    return this.props.withdrawalConfigurationDetails.data.repayment_frequency;
  };

  isRepaymentFrequencyBimonthly = () => {
    return this.getRepaymentFrequency() === REPAYMENT_FREQUENCY_TYPES.BIMONTHLY;
  };

  isRepaymentFrequencyCustom = () => {
    return this.getRepaymentFrequency() === REPAYMENT_FREQUENCY_TYPES.CUSTOM;
  };

  getDueDate = () => {
    switch (this.getRepaymentFrequency()) {
      case REPAYMENT_FREQUENCY_TYPES.CUSTOM: {
        return moment(this.state.selectedDueDate);
      }
      case REPAYMENT_FREQUENCY_TYPES.BIMONTHLY: {
        const startOfMonth = moment().startOf('month').startOf('date');
        const halfMonth = moment().startOf('month').add(14, 'days').endOf('date');
        const {
          repayment_date1,
          repayment_date2,
        } = this.props.withdrawalConfigurationDetails.data.configuration;

        if (moment().isBetween(startOfMonth, halfMonth)) {
          return moment().set('date', repayment_date2).endOf('day');
        } else {
          return moment().add(1, 'month').set('date', repayment_date1).endOf('day');
        }
      }
      default:
        return null;
    }
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

    const { comments: { reason = '' } = {}, status } = withdrawalConfigurationDetails;

    const isWithdrawalDisabled = status === 'ONHOLD' && reason === 'end_of_credit_line_tenure';
    const canWithdraw = this.canWithdraw() && !isWithdrawalDisabled;
    const { withdrawalErrorConfig = {} } = this.state;
    const showReasonCTA =
      !canWithdraw && withdrawalErrorConfig.showReasonCTA && !isWithdrawalDisabled;

    return (
      /*eslint-disable */
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

            {isWithdrawalDisabled && (
              <Popover align="top" parentQuerySelector=".withdrawals__top-summary" theme="dark">
                <PopoverBody>
                  Your credit line has been disabled as it has reached the end of tenure.
                </PopoverBody>
              </Popover>
            )}
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
      /*eslint-enable */
    );
  };

  openWithdrawErrorModal = () => {
    const {
      withdrawalAmount,
      withdrawalErrorConfig: { type: withdrawalErrorType },
    } = this.state;

    this.gaEventDispatcher({
      eventAction: `Low Balance | Why can't I withdraw`,
    });

    const props = {
      amount: withdrawalAmount * 100,
      closeModal: this.props.closeModal,
      repayDues: this.props.repayDues,
      trackGA: this.gaEventDispatcher,
    };

    const RenderComp =
      withdrawalErrorType === WITHDRAW_ERROR_TYPES.MIN_WITHDRAWAL_ERROR
        ? MinWithdrawAmountModal
        : MaxWithdrawError;

    if (withdrawalErrorType === WITHDRAW_ERROR_TYPES.MIN_WITHDRAWAL_ERROR) {
      props.minWithdrawalAmount = this.getMinWithdrawableAmount();
    } else {
      props.maxWithdrawalAmount = this.getMaxWithdrawableAmount();
    }

    this.props.openModal({
      size: 'small',
      component: <RenderComp {...props} />,
    });
  };

  openGromorSignModal = () => {
    const {
      withdrawalConfigurationDetails,
      merchantGromorEsignDetails: {
        data: { due_at, email_id = '', name = 'You', leegality_url = '' } = {},
      } = {},
    } = this.props;

    const handleModalClose = () => {
      this.props.closeModal();
    };

    this.props.openModal({
      component: (
        <GromorAgreementModal
          onClose={handleModalClose}
          withdrawalConfigurationDetails={withdrawalConfigurationDetails.data}
          eSignUrl={leegality_url}
          name={name}
          email_id={email_id}
          due_at={due_at}
        />
      ),
      size: 'medium',
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

  withdrawableSectionPostGromorAgreementDate = () => {
    return (
      <div className="withdrawals-disabled">
        <div className="withdrawals-disabled-title-wrapper">
          <div className="flex end-align">
            <i className="i i-error withdrawals-disabled-icon" />
            <h3 className="withdrawals-disabled-title text--secondary">
              Withdrawals are temporarily disabled!
            </h3>
          </div>
          <p className="withdrawals-disabled-summary">
            Kindly review and sign the new lender agreement to continue with your Cash Advance
            withdrawals.
          </p>
        </div>
        <div className="flex cta_wrapper">
          <button onClick={this.openGromorSignModal} className="btn btn-primary">
            {'View & Sign Agreement'}
          </button>
        </div>
      </div>
    );
  };

  withdrawOnholdReasonSection = (reason) => {
    const isReasonCldRiskPolicy = reason === 'cld_risk_policy';

    const resonLabels = {
      cld_risk_policy: (
        <>
          Sorry, your withdrawals are temporarily on hold due to the perceived risk of a decrease in
          payments volume. <br />
          Withdrawals will be enabled once your payments volume is back on track.
        </>
      ),
      non_repayment: (
        <>
          Sorry, Your withdrawals are temporarily blocked due to missed repayments. Please repay to
          continue <br /> using your credit line.
        </>
      ),
    };

    const renderBottomSection = () => {
      return (
        <div className="flex outstanding__wrapper">
          {isReasonCldRiskPolicy ? (
            <div>
              <i className="i i-info-outline withdrawals__onhold-icon bottom-section-icon" />
              Keep using the payments gateway for your business needs to keep the payments volume
              high.
            </div>
          ) : (
            <>
              <div>
                <div className="outstanding-title">Outstanding Repayment</div>
                <strong className="outstanding-amount">
                  <Amount
                    value={this.state.outstandingRepayment.amount}
                    parentQuerySelector=".withdrawals__top-summary"
                  />
                </strong>
              </div>
              <button
                className="btn btn-primary outstanding-paybutton"
                onClick={this.handlePayNowClick}
              >
                Pay Now
              </button>
            </>
          )}
        </div>
      );
    };

    return (
      <div className="flex onhold-reason-container">
        <div>
          <div className="flex end-align">
            <i className="i i-error withdrawals__onhold-icon" />
            <h3 className="withdrawals__onhold-title text--secondary">Withdrawals are on hold!</h3>
          </div>
          <p className="withdrawals__onhold-summary">{resonLabels[reason]}</p>
        </div>
        {renderBottomSection()}
      </div>
    );
  };

  withdrawableSection = () => {
    const {
      withdrawalAmount,
      selectedDueDate,
      showRepaymentDetailsBreakup,
      isAutomatedTagPulsating,
      latestRepaymentDone,
      isRepaymentLoading,
      withdraw_errors,
    } = this.state;
    const {
      user,
      withdrawalConfigurationDetails: {
        data: { automated_loc, status, comments: { reason = '' } = {} },
      } = {
        data: {
          automated_loc: false,
        },
      },
      merchantGromorEsignDetails: { loading, data: { due_at = '' } = {} } = {},
    } = this.props;

    const isApplicationAtHold = status === 'ONHOLD' && reason !== 'end_of_credit_line_tenure';

    const hasDueDateAndWithdrawnAmount = selectedDueDate && withdrawalAmount;
    const { principle = 0, interest = 0 } = hasDueDateAndWithdrawnAmount
      ? this.getRepayableAmount()
      : {};
    const repayableAmount = getFormattedAmountNew((principle + interest) * 100, true);
    const showFirstWithdrawalOffer = this.getFirstWithdrawalOffer();
    const withdrawalInputHasError = withdraw_errors.length > 0;
    const isDateExpired = checkifDateExpired(new Date(due_at));
    const isGromorAgreementLoading = loading;
    const locEsignEnabled = user.isFeatureEnabled('loc_esign');
    const partner_id =
      this.props.withdrawalConfigurationDetails && this.props.withdrawalConfigurationDetails.data
        ? this.props.withdrawalConfigurationDetails.data.configuration.custom_partner_fields
            .partner_id
        : '';
    const isAggrementSigned = isDateExpired && locEsignEnabled && partner_id !== 'GROMOR';

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
            <div class="cash-advance-first-withdrawal-background" />
          </React.Fragment>
        ) : null}

        <div class="no-margin full-width" style={{ position: 'relative' }}>
          {isGromorAgreementLoading ? (
            <div class="page-spinner-container" style={{ height: '100%' }}>
              <Spinner />
            </div>
          ) : isAggrementSigned ? (
            this.withdrawableSectionPostGromorAgreementDate()
          ) : isApplicationAtHold ? (
            isRepaymentLoading === 'LOADING' ? (
              <div class="page-spinner-container" style={{ height: '100%' }}>
                <Spinner />
              </div>
            ) : latestRepaymentDone ? (
              this.repaymentSuccessfull(isRepaymentLoading)
            ) : (
              this.withdrawOnholdReasonSection(reason)
            )
          ) : (
            <div>
              <div
                className={`flex automated-popover-container-wrapper ${
                  this.isRepaymentFrequencyCustom() ? 'custom-frequency' : ''
                }`}
              >
                {user.isAutomatedLOCEligible && automated_loc && (
                  <div className="automated-popover-container">
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
              {hasDueDateAndWithdrawnAmount && !withdrawalInputHasError && (
                <div class="repayable-amount-hint">
                  <strong>{repayableAmount}</strong>
                  <span class="repayable-helper-text">&nbsp; will be the repayable amount</span>
                  {user.isAutomatedLOCEligible &&
                    !automated_loc &&
                    this.isRepaymentFrequencyCustom() && (
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

  repaymentSuccessfull(isRepaymentLoading) {
    return (
      <div>
        <div className="flex end-align">
          <i
            class="i i-done text-success"
            style={{
              fontSize: 20,
            }}
          />
          <h3 className="repayment-succesfull-title text--secondary">Repayment Successful!</h3>
        </div>
        <p className="repayment-succesfull-summary">
          Thanks for your payment. Your cash advance will be activated as soon as the repayment is
          adjusted on your account.
        </p>
        <div className="flex repayment-succesfull-breakdown-wrapper">
          <div>
            <p
              className="repayment-succesfull-breakdown-title"
              style={{ color: 'rgba(22, 47, 86, 0.62)' }}
            >
              Total Repaid
            </p>
            <div className="repayment-succesfull-breakdown-amount-wrapper">
              {isRepaymentLoading ? (
                <PlaceholderLoader />
              ) : (
                <strong
                  className="repayment-succesfull-breakdown-amount"
                  style={{ fontSize: '20px' }}
                >
                  <Amount
                    value={this.state.repaymentBreakdown.totalRepaid}
                    parentQuerySelector=".withdrawals__top-summary"
                  />
                </strong>
              )}
            </div>
          </div>
          <div>
            <p className="repayment-succesfull-breakdown-title">Principal Repaid</p>
            <div className="repayment-succesfull-breakdown-amount-wrapper">
              {isRepaymentLoading ? (
                <PlaceholderLoader />
              ) : (
                <strong className="repayment-succesfull-breakdown-amount">
                  <Amount
                    value={this.state.repaymentBreakdown.principalRepaid}
                    parentQuerySelector=".withdrawals__top-summary"
                  />
                </strong>
              )}
            </div>
          </div>
          <div>
            <p className="repayment-succesfull-breakdown-title">Interest Repaid </p>
            <div className="repayment-succesfull-breakdown-amount-wrapper">
              {isRepaymentLoading ? (
                <PlaceholderLoader />
              ) : (
                <strong className="repayment-succesfull-breakdown-amount">
                  <Amount
                    value={this.state.repaymentBreakdown.interestRepaid}
                    parentQuerySelector=".withdrawals__top-summary"
                  />
                </strong>
              )}
            </div>
          </div>
          <div>
            <p className="repayment-succesfull-breakdown-title">Repayment Method</p>
            <div className="repayment-succesfull-breakdown-amount-wrapper">
              {isRepaymentLoading ? (
                <PlaceholderLoader />
              ) : (
                <strong className="repayment-succesfull-breakdown-amount">
                  {this.state.repaymentBreakdown.repaymentMethod
                    ? titleCase(this.state.repaymentBreakdown.repaymentMethod)
                    : '- -'}
                </strong>
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }

  handleRepaymentInfoTooltipHover = (val) => {
    this.setState((prev) => ({
      ...prev,
      showRepaymentInfoTooltip: val,
    }));
  };

  isDueDate20 = () => {
    return this.getDueDate().format('D') === '20';
  };

  getWithdrawalForm(withdrawalAmount) {
    const {
      withdrawalConfigurationDetails: {
        data: { configuration: { end_day_limit = null } = {} } = {},
      } = {},
    } = this.props;
    const { selectedDueDate } = this.state;
    const dateToShow = selectedDueDate ? moment(selectedDueDate) : computeMaxDueDate(end_day_limit);

    return (
      <div className="flex withdrawal-form-container">
        <Input.Group
          label="How much do you need?"
          className={`InputGroup--inline Input--vTop no-margin ${
            this.isRepaymentFrequencyBimonthly() ? 'less-right-space' : ''
          }`}
        >
          <div className="Input-content">
            <Input
              addonBefore="₹"
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
        {this.isRepaymentFrequencyCustom() && (
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
                  style={{
                    paddingLeft: '4px',
                    position: 'relative',
                  }}
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
            defaultValue={dateToShow}
            onChange={this.handleDueDateChange}
            onClick={() => trackRepayDateClicked(dateToShow)}
            addonAfter={<i className="i i-date-range" />}
            placement="topLeft"
            allowToday={false}
            disablePastDates={false}
            disabledDate={this.isDateDisabled}
          />
        )}
        <div className="withdrawal-cta-container">{this.getWithdrawCTA()}</div>
        {this.isRepaymentFrequencyBimonthly() && (
          <div className="repayment-info-container flex">
            <div className="side-border" />
            <img
              src="/dist/css/assets/capital/calendar2.svg"
              className="calendar-icon"
              alt="calendar"
            />
            <div
              className="info-container flex"
              onMouseOver={() => this.handleRepaymentInfoTooltipHover(true)}
              onMouseLeave={() => this.handleRepaymentInfoTooltipHover(false)}
            >
              <div className="top-part flex">
                <div className="repayment-label">Repay by</div>
                <span className="icon i-info-outline info-icon" />
              </div>
              <div className="bottom-part">{this.getDueDate()?.format('DD MMMM, YYYY')}</div>
              <Popover
                align="right"
                theme="dark"
                parentQuerySelector=".withdrawals__top-summary"
                persistent={this.state.showRepaymentInfoTooltip}
              >
                <PopoverBody>
                  <div className="repayment-info-tooltip-container flex">
                    <div className="flex top-label-container">
                      <div className="top-label">Withdrawal Period</div>
                      <div className="top-label">Repayment Dates</div>
                    </div>
                    <div className={`flex repayment-info ${this.isDueDate20() ? 'active' : ''}`}>
                      <div className="side-border" />
                      <div className="flex repayment-info--detail first-part">
                        <div className="date-label">
                          1st
                          <span className="icon i-arrow-forward" />
                          15th
                        </div>
                        <div className="text-label">of every month</div>
                      </div>
                      <div className="flex repayment-info--detail">
                        <div className="date-label">20th</div>
                        <div className="text-label">of the same month</div>
                      </div>
                    </div>

                    <div className={`flex repayment-info ${!this.isDueDate20() ? 'active' : ''}`}>
                      <div className="side-border" />
                      <div className="flex repayment-info--detail first-part">
                        <div className="date-label">
                          16th
                          <span className="icon i-arrow-forward" />
                          last day
                        </div>
                        <div className="text-label">of every month</div>
                      </div>
                      <div className="flex repayment-info--detail">
                        <div className="date-label">5th</div>
                        <div className="text-label">of the next month</div>
                      </div>
                    </div>
                  </div>
                </PopoverBody>
              </Popover>
            </div>
          </div>
        )}
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
          <img height={16} src="/dist/css/assets/success-tick-green.svg" alt="Loading icon" />
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
                  value={`${withdrawalAmount}00`}
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
                  value={`${withdrawalAmount}00`}
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
      default:
        return null;
    }
  };

  getRightSection = (currentView) => {
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const withdrawConfigLoading = this.props.withdrawalConfigurationDetails.loading;

    const {
      seedData,
      user,
      history,
      haveWithdrawals = false,
      withdrawalConfigurationDetails: { data: { status, comments: { reason = '' } = {} } } = {},
    } = this.props;
    const isWithdrawalOnhold = status === 'ONHOLD';

    switch (currentView) {
      case VIEWS.WITHDRAW:
      case VIEWS.WITHDRAW_FAIL:
        if (!withdrawalConfigurationDetails || withdrawConfigLoading || seedData.loading)
          return (
            <CreditSummary loading={true} isWithdrawalOnhold={isWithdrawalOnhold} reason={reason} />
          );
        else {
          return (
            <CreditSummary
              loading={false}
              internalCreditBalance={this.getInternalCreditBalance()}
              withdrawalConfiguration={withdrawalConfigurationDetails}
              user={user}
              history={history}
              haveWithdrawals={haveWithdrawals}
              isWithdrawalOnhold={isWithdrawalOnhold}
              reason={reason}
            />
          );
        }
      case VIEWS.WITHDRAW_SUCCESS: {
        const meta = this.getRepayableAmount();
        return (
          <WithdrawnAmountSummary {...meta} repaymentDate={moment(this.state.selectedDueDate)} />
        );
      }
      default:
        return null;
    }
  };

  getLeftSection = () => {
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
