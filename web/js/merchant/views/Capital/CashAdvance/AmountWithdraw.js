import React from 'react';
import { Badge, Box, InfoIcon } from '@razorpay/blade/components';
import moment from 'moment';
import { connect } from 'react-redux';
import { NavLink, withRouter } from 'react-router-dom';

import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import Amount from 'common/ui/Amount';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Spinner from 'common/ui/Spinner';
import { getItem, setItem } from 'common/utils/localStorage';
import { classList, getFormattedAmountNew, titleCase } from 'common/utils/rzp-utils';
import Repayments from 'merchant/models/Capital/Repayments';
import Withdrawal from 'merchant/models/Capital/Withdrawals';
import { fetchMerchantDetails } from 'merchant/reducers/capital/migrations';
import { fetchRepayments } from 'merchant/reducers/capital/repayments';
import {
  createWithdrawal,
  fetchCreditSummary,
  fetchFunctionalWithdrawalConfigByMerchantID,
  fetchInstallments,
  fetchSeedData,
  fetchWithdrawalConfiguration,
  fetchWithdrawals,
} from 'merchant/reducers/capital/withdrawals';
import GromorAgreementModal from 'merchant/views/Capital/components/Modals/GromorAgreementModal';
import {
  checkifDateExpired,
  getDisabledReasons,
  getProductNames,
  getProductType,
  isMerchantNewToCashOnCard,
  loadCheckoutScript,
} from 'merchant/views/Capital/utils';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import CancelWithdrawalReasons from './CancelWithdrawalReasons';
import CreditSummary from './CreditSummary';
import DisableAutomatedWithdrawModal from './DisableAutomatedWithdrawModal';
import EnableAutomatedWithdrawModal from './EnableAutomatedWithdrawModal';
import MaxWithdrawError from './MaxWithdrawError';
import MinWithdrawAmountModal from './MinWithdrawAmountModal';
import { getCurrentOutstandingBreakup } from './OverviewFooter/utils';
import {
  trackCloseButton,
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
import WithdrawnAmountSummary from './WithdrawnAmountSummary';
import CardsDashboardRedirectionModal from './components/CardsDashboardRedirectionModal';
import FirstWithdrawalView from './components/FirstWithdrawal/FirstWithdrawalView';
import FungibleCreditSummary from './components/FungibleCreditSummary';
import ReducingRepaymentTooltip from './components/ReducingRepaymentTooltip';
import RepaymentPreferenceBanner from './components/RepaymentPreferenceBanner';
import StaticTenureSelector from './components/StaticTenureSelector';
import {
  CASH_ADVANCE_BASE_URL,
  CASH_ADVANCE_FIRST_LOGIN_KEY,
  CASH_ADVANCE_PRODUCT_TYPES,
  CASH_ADVANCE_SECTIONS,
  CLOSE_OPTIONS,
  COLLECTIONS_BALANCE_TYPE,
  COLLECTIONS_PAYMENT_REFERENCE_TYPE,
  COLLECTIONS_PRODUCT_TYPES,
  ONHOLD_REASONS,
  REPAYMENT_FREQUENCY_TYPES,
  REPAYMENT_TYPES,
  STATUSES,
  VIEWS,
  WITHDRAW_ERROR_TYPES,
} from './constants';
import trackAutomatedCA from './ga/automated';
import {
  getFirstTimeRepaymentPreference,
  isLenderLiquiloans,
  isMerchantNew,
  showSettings,
} from './utils';

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
  // utcOffset here is used to set timezone
  // 330 is basically difference between ist time and utc time in minutes (5.5 * 60)
  // ref: https://momentjs.com/docs/#/manipulating/utc-offset/
  // value returned from here is used as defaultValue for rc-calendar
  // hence calendar shown will always be based on IST time (not local)
  return moment()
    .utc()
    .utcOffset(330)
    .add(limit - 1, 'days');
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
    fungibleData: state.withdrawals.cash_on_card.data,
    productConfig: state.productConfig.productConfig,
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
    fetchCreditSummary,
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
      locDisabledReason: {
        fetching: this.isCashAdvanceDisabled,
        reasons: [], // product types eg:- [PRODUCT_TYPE_CARDS, ...]
      },
    };
    this.state = this.initialState;
  }

  get isCashAdvanceDisabled() {
    return this.props.user.isCashAdvanceDisabled;
  }

  get isCashAdvanceDisabledDue2SelfBlock() {
    const { locDisabledReason } = this.state;
    return (
      !!locDisabledReason.reasons.length &&
      locDisabledReason.reasons.every(
        (prodType) => prodType === COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
      )
    ); // only cash advance in dpd - self blocked
  }

  componentDidMount() {
    const {
      withdrawalConfigurationDetails: {
        data: { status, repayment_frequency, comments: { reason = '' } = {} },
      } = {},
    } = this.props;
    const withdrawalInstance = new Withdrawal();
    const repaymentInstance = new Repayments();
    const isFirstCashAdvanceLogin =
      repayment_frequency &&
      (this.isRepaymentFrequencyBimonthly() || this.isRepaymentFrequencyMonthly()) &&
      checkIfFirstCashAdvanceLogin();

    // Setting localStorage key for repayment tooltip
    if (isFirstCashAdvanceLogin) {
      setItem(CASH_ADVANCE_FIRST_LOGIN_KEY, false);

      document.querySelector('body').addEventListener('click', this.hideRepaymentTooltip);
    }

    if (this.isFungibleLimitProductType()) {
      this.fetchCreditSummaryCall();
    } else {
      this.prefillData();
    }
    const isApplicationAtHold =
      status === 'ONHOLD' &&
      reason !== ONHOLD_REASONS.CLD_RISK_POLICY &&
      reason !== ONHOLD_REASONS.END_OF_CREDIT_LINE_TENURE;

    if (isApplicationAtHold) {
      Promise.all([
        this.fetchInstallment(withdrawalInstance)
          .then(({ data: { current_outstanding = {} } = {} }) => {
            const amount = getCurrentOutstandingBreakup({ data: current_outstanding }).total;
            return {
              latestRepaymentDone: amount ? amount <= 0 : true,
              outstandingRepayment: {
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
    this.isCashAdvanceDisabled &&
      getDisabledReasons(this.props.user, COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE).then(
        (reasons) => {
          this.setState({
            locDisabledReason: {
              fetching: false,
              reasons,
            },
          });
        },
      );
  }

  componentWillUnmount() {
    document.querySelector('body').removeEventListener('click', this.hideRepaymentTooltip);
  }

  hideRepaymentTooltip = () => this.handleRepaymentInfoTooltipHover(false);

  productType = getProductType(this.props.user);

  fetchInstallment = (withdrawalInstance) => {
    return withdrawalInstance.fetchInstallments({
      owner_id: this.props.user.current,
      from: moment().startOf('day').unix(),
      to: moment().add(2, 'days').unix(),
      product_type: this.productType,
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
    window.rzpAnalytics?.(eventObject);
  };

  prefillData = () => {
    const maxWithdrawableAmount = this.getMaxWithdrawableAmount();
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const { end_day_limit } = withdrawalConfigurationDetails.configuration;
    const maxDueDate = computeMaxDueDate(end_day_limit);

    if (maxWithdrawableAmount > this.getMinWithdrawableAmount()) {
      this.setState({
        withdrawalAmount: Math.trunc(maxWithdrawableAmount / 100),
        selectedDueDate: this.isRepaymentFrequencyDays90() ? null : maxDueDate.endOf('day'),
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
      product_type: this.productType,
    });
  };

  fetchCreditSummaryCall = () => {
    const {
      fetchCreditSummary,
      user: { current: merchant_id },
    } = this.props;

    fetchCreditSummary({
      product_type: CASH_ADVANCE_PRODUCT_TYPES.CASH_ON_CARD,
      merchant_id,
    }).then(() => {
      this.prefillData();
    });
  };

  isOnlyNumbers = (value) => {
    const numberRegex = /^(\d*)?\d+$/;
    const regexNumbers = new RegExp(numberRegex);
    return regexNumbers.test(value);
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

    const eventAmount = e.currentTarget.value;
    if (!this.isOnlyNumbers(eventAmount)) {
      errors.push('No Decimals Allowed');
    }

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
    trackRepayDateClicked(moment(date).format('DD-MM-YYYY'));
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
    const withdrawalConfigurationDetails = this.props?.withdrawalConfigurationDetails?.data;
    const { interest, auto_collection = false } = withdrawalConfigurationDetails?.configuration;
    const startDay = moment();

    const selectedDate = this.getDueDate().endOf('day');

    const diffDays = selectedDate.diff(startDay, 'days');

    const roi = parseInt(interest, 10) / 100;

    const isInterestTypeReducing = this.isInterestTypeReducing();

    const amount = {
      principle: parseInt(this.state.withdrawalAmount, 10),
      interest: (diffDays * parseInt(this.state.withdrawalAmount, 10) * roi) / 100,
      diffDays,
      roi,
      isInterestTypeReducing,
      autoCollection: auto_collection,
    };

    return amount;
  };

  getInternalCreditBalance = () => {
    let internalBalance = 0;
    if (this.isFungibleLimitProductType()) {
      internalBalance = parseInt(
        this.props?.fungibleData?.cash_advance?.available_balance || 0,
        10,
      );
    } else {
      const withdrawalConfigurationDetails = this.props?.withdrawalConfigurationDetails?.data;
      internalBalance = Number(withdrawalConfigurationDetails?.effective_balance || 0);
    }
    return internalBalance > 0 ? internalBalance : 0;
  };

  getMaxWithdrawableAmount = () => {
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const { max_withdraw_amount, min_withdraw_amount } =
      withdrawalConfigurationDetails.configuration;

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

  showFirstTimeRepaymentPreference = () => {
    return !getFirstTimeRepaymentPreference();
  };

  showSettings = () => {
    return (
      showSettings(this.props.user, this.getRepaymentFrequency()) &&
      !isLenderLiquiloans(this.props.withdrawalConfigurationDetails)
    );
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

    const showFirstTimeRepaymentPref =
      this.showSettings() && this.showFirstTimeRepaymentPreference();

    if (showFirstTimeRepaymentPref) {
      this.setState({
        currentView: VIEWS.WITHDRAW_FIRST_TIME,
      });
    } else {
      this.setState({
        isConfirmingWithdraw: true,
      });
    }
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

  withdraw = async ({ tenure }) => {
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
        metadata: {
          tenure,
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
        if (this.isFungibleLimitProductType()) {
          this.fetchCreditSummaryCall();
        }
        trackWithdrawStatus({
          amount: withdrawalAmount,
          date: moment(selectedDueDate).format('DD-MM-YYYY'),
          status: 'success',
        });
        this.setState({
          currentView: VIEWS.WITHDRAW_SUCCESS,
          showRepaymentDetailsBreakup: false,
        });
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

  isRepaymentFrequencyMonthly = () => {
    return this.getRepaymentFrequency() === REPAYMENT_FREQUENCY_TYPES.MONTHLY;
  };

  isRepaymentFrequencyCustomORDays90 = () => {
    return [REPAYMENT_FREQUENCY_TYPES.CUSTOM, REPAYMENT_FREQUENCY_TYPES.DAYS_90].includes(
      this.getRepaymentFrequency(),
    );
  };

  isRepaymentFrequencyDays90 = () => {
    return (
      this.getRepaymentFrequency() === REPAYMENT_FREQUENCY_TYPES.DAYS_90 &&
      this.props?.withdrawalConfigurationDetails?.data?.configuration?.end_day_limit === '90'
    );
  };

  isFungibleLimitProductType = () => {
    return this.props.user.isCashOnCardEnabled;
  };

  getDueDate = () => {
    switch (this.getRepaymentFrequency()) {
      case REPAYMENT_FREQUENCY_TYPES.CUSTOM:
      case REPAYMENT_FREQUENCY_TYPES.DAYS_90: {
        return moment(this.state.selectedDueDate);
      }
      case REPAYMENT_FREQUENCY_TYPES.BIMONTHLY: {
        const startOfMonth = moment().startOf('month').startOf('date');
        const halfMonth = moment().startOf('month').add(14, 'days').endOf('date');
        const { repayment_date1, repayment_date2 } =
          this.props.withdrawalConfigurationDetails.data.configuration;

        if (moment().isBetween(startOfMonth, halfMonth)) {
          return moment().set('date', repayment_date2).endOf('day');
        } else {
          return moment().add(1, 'month').set('date', repayment_date1).endOf('day');
        }
      }
      case REPAYMENT_FREQUENCY_TYPES.MONTHLY: {
        const dueDate =
          this.props?.withdrawalConfigurationDetails?.data?.configuration?.repayment_date;
        return moment.unix(dueDate);
      }
      default:
        return null;
    }
  };

  getInterestType = () => {
    return (
      this.props.withdrawalConfigurationDetails.data?.configuration?.interest_type ||
      REPAYMENT_TYPES.FLAT_INTEREST
    );
  };

  isInterestTypeReducing = () => this.getInterestType() === REPAYMENT_TYPES.REDUCING_INTEREST;

  handleAutomatedTextMouseOver = () => {
    trackAutomatedCA.hoverAutomatedText({});
    const { isAutomatedTagPulsating } = this.state;
    if (isAutomatedTagPulsating) {
      this.stopAutomatedTagPulsating();
    }
  };

  isMerchantNew = () => {
    return isMerchantNew(this.props.productConfig?.data?.configuration?.live_by_date);
  };

  getWithdrawCTA = ({ tenure }) => {
    const {
      seedData,
      withdrawalConfigurationDetails: {
        data: withdrawalConfigurationDetails,
        loading: withdrawConfigLoading,
      },
    } = this.props;
    const { selectedDueDate } = this.state;
    if (!withdrawalConfigurationDetails || withdrawConfigLoading || seedData.loading) return null;

    const { comments: { reason = '' } = {}, status } = withdrawalConfigurationDetails;

    const isWithdrawalDisabled =
      status === 'ONHOLD' && reason === ONHOLD_REASONS.END_OF_CREDIT_LINE_TENURE;
    const canWithdraw = this.canWithdraw() && !isWithdrawalDisabled;
    const { withdrawalErrorConfig = {} } = this.state;
    const showReasonCTA =
      !canWithdraw && withdrawalErrorConfig.showReasonCTA && !isWithdrawalDisabled;
    const withdrawNowClass = `btn btn-primary withdraw-now${
      this.state.showRepaymentInfoTooltip ? ' animation-wrapper' : ''
    }`;
    const isMerchantNew = this.isMerchantNew();

    const isLiquiloansLenderDown =
      withdrawalConfigurationDetails?.effective_balance === -1 &&
      withdrawalConfigurationDetails?.lender_balance_diff_reason === 'LENDER_IS_DOWN';

    let withdrawCtaDisabled = false;

    if (isLenderLiquiloans(this.props.withdrawalConfigurationDetails)) {
      withdrawCtaDisabled = isLiquiloansLenderDown;
    } else if (isMerchantNew) {
      withdrawCtaDisabled = true;
    } else {
      withdrawCtaDisabled = !canWithdraw;
    }

    return (
      /*eslint-disable */
      <React.Fragment>
        {!this.state.isConfirmingWithdraw ? (
          <React.Fragment>
            <AsyncBtn.Primary
              className={withdrawNowClass}
              disabled={withdrawCtaDisabled}
              onClick={this.confirmWithdraw}
            >
              Withdraw Now
            </AsyncBtn.Primary>

            {showReasonCTA && selectedDueDate ? (
              <AsyncBtn.Transparent className="btn btn-link" onClick={this.openWithdrawErrorModal}>
                View Reason
              </AsyncBtn.Transparent>
            ) : null}

            {isWithdrawalDisabled ? (
              <Popover align="top" parentQuerySelector=".withdrawals__top-summary" theme="dark">
                <PopoverBody>
                  Your credit line has been disabled as it has reached the end of tenure.
                </PopoverBody>
              </Popover>
            ) : (
              Boolean(withdrawCtaDisabled && isLiquiloansLenderDown) && (
                <Popover align="top" parentQuerySelector=".withdrawals__top-summary" theme="dark">
                  <PopoverBody>System is down right now, Please try again in sometime!</PopoverBody>
                </Popover>
              )
            )}
          </React.Fragment>
        ) : (
          <div className="flex">
            <AsyncBtn.Primary
              class="btn btn-primary"
              disabled={!this.canWithdraw()}
              onClick={() => this.withdraw({ tenure })}
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
    } else if (withdrawalErrorType === WITHDRAW_ERROR_TYPES.MAX_WITHDRAWAL_ERROR) {
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
          <div className="automated-withdraw-tooltip--enabled--heading">Automated Withdrawals</div>
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

  withdrawOnholdReasonSection = (reason, showFooter = true) => {
    const isReasonCldRiskPolicy = reason === ONHOLD_REASONS.CLD_RISK_POLICY;
    const isReasonKudosNotMigrated = reason === ONHOLD_REASONS.NOT_MIGRATED_TO_GROMOR;
    const isReasonWithdrawlDisabledDueToDPD = reason === ONHOLD_REASONS.DISABLE_LOC_POST_DPD; // high priority to show messages related to this issue.

    const { locDisabledReason, isRepaymentLoading, outstandingRepayment } = this.state;

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
      not_migrated_to_gromor: (
        <>
          The account is put on hold as we have changed our lending partner. Please reach out to us
          at <a href="mailto:harshit.jain@razorpay.com">harshit.jain@razorpay.com</a>, so we can
          help you activate cash advance account
        </>
      ),
      [ONHOLD_REASONS.DISABLE_LOC_POST_DPD]:
        locDisabledReason.fetching || isRepaymentLoading ? (
          <div className="withdrawals__onhold-summary__disabled-loc-loading">
            <div className="PlaceholderLoader w-full" />
            <div className="PlaceholderLoader w-6" />
            <div className="PlaceholderLoader w-3 mt-7" />
          </div>
        ) : (
          <>
            <div>
              {this.isCashAdvanceDisabledDue2SelfBlock
                ? 'Sorry, Your withdrawals are temporarily blocked due to missed repayments. Please repay to continue using your credit line.'
                : `Sorry, your withdrawals are temporarily blocked due to missed repayments on one or
                more of your other products - ${getProductNames(locDisabledReason.reasons)}.
                Please repay to continue using your credit line.`}
            </div>
            <br />
            <div className="mt-8">
              {' '}
              If you have already repaid your pending dues, then your withdrawals will be enabled
              back within 24 - 48 working hours.
            </div>
          </>
        ),
    };

    const renderBottomSection = () => {
      const isReasonKudosorCldOnly =
        !isReasonWithdrawlDisabledDueToDPD && (isReasonCldRiskPolicy || isReasonKudosNotMigrated);
      return (
        <div className="flex outstanding__wrapper">
          {isReasonKudosorCldOnly ? (
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
                    value={outstandingRepayment.amount}
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
          <div className="withdrawals__onhold-summary">{resonLabels[reason]}</div>
        </div>
        {showFooter && renderBottomSection()}
      </div>
    );
  };

  getRepaymentHelperText = () => {
    const { withdrawalConfigurationDetails = {} } = this.props;
    const showFirstTimeRepaymentPreference = this.showFirstTimeRepaymentPreference();
    const showSettings = this.showSettings();
    const collectionMethod = showSettings && (
      <>
        in{' '}
        <strong>
          {withdrawalConfigurationDetails?.data?.configuration?.auto_collection
            ? 'automatic daily deductions.'
            : 'manual repayment.'}
        </strong>
      </>
    );
    const setPreferenceLink = showSettings && !showFirstTimeRepaymentPreference && (
      <NavLink
        className="change-preference-link"
        exact
        to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.SETTINGS}`}
      >
        Change Preference
      </NavLink>
    );
    if (this.isInterestTypeReducing()) {
      return (
        <span>
          is the repayable amount at reducing interest {collectionMethod}
          <ReducingRepaymentTooltip />
          {setPreferenceLink}
        </span>
      );
    }
    return (
      <span>
        will be the repayable amount {collectionMethod} {setPreferenceLink}
      </span>
    );
  };

  getRepaymentTooltipBody = () => {
    if (this.isFungibleLimitProductType()) {
      return (
        <div className="flex repayment-info active">
          <div className="flex repayment-info--detail first-part">
            <div className="date-label">
              1st
              <span className="icon i-arrow-forward" />
              31st
            </div>
            <div className="text-label">of every month</div>
          </div>
          <div className="flex repayment-info--detail">
            <div className="date-label">21st</div>
            <div className="text-label">of the next month</div>
          </div>
        </div>
      );
    }
    return (
      <>
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
      </>
    );
  };

  getRepaymentTooltipSection = () => {
    return (
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
            {this.getRepaymentTooltipBody()}
          </div>
        </PopoverBody>
      </Popover>
    );
  };

  withdrawableSection = () => {
    const {
      withdrawalAmount,
      selectedDueDate,
      isAutomatedTagPulsating,
      latestRepaymentDone,
      isRepaymentLoading,
      withdraw_errors,
      locDisabledReason,
      outstandingRepayment,
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

    const isApplicationAtHold =
      status === 'ONHOLD' && reason !== ONHOLD_REASONS.END_OF_CREDIT_LINE_TENURE;
    const isApplicationKudosHold =
      status === 'ONHOLD' && reason === ONHOLD_REASONS.NOT_MIGRATED_TO_GROMOR;

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

    const isWithdrawlDisabled = this.isCashAdvanceDisabled; // due to post dpd in any capital products, message shown on high priority
    const allowOutStandingAmountRepayment =
      !locDisabledReason.fetching &&
      this.isCashAdvanceDisabledDue2SelfBlock &&
      outstandingRepayment.amount;
    const showRepaybleHelperText =
      hasDueDateAndWithdrawnAmount &&
      !withdrawalInputHasError &&
      !this.isRepaymentFrequencyCustomORDays90();
    const showEnableNowCTA =
      user.isAutomatedLOCEligible && !automated_loc && this.isRepaymentFrequencyCustomORDays90();

    const lenderBalanceDiffReason =
      this.props?.withdrawalConfigurationDetails?.data?.lender_balance_diff_reason;
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
          {isWithdrawlDisabled ? (
            this.withdrawOnholdReasonSection(
              ONHOLD_REASONS.DISABLE_LOC_POST_DPD,
              allowOutStandingAmountRepayment,
            )
          ) : isGromorAgreementLoading ? (
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
            ) : isApplicationKudosHold ? (
              this.withdrawOnholdReasonSection(reason)
            ) : latestRepaymentDone ? (
              this.repaymentSuccessfull(isRepaymentLoading)
            ) : (
              this.withdrawOnholdReasonSection(reason)
            )
          ) : (
            <div>
              <div
                className={`flex automated-popover-container-wrapper ${
                  this.isRepaymentFrequencyCustomORDays90() ? 'custom-frequency' : ''
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
              <div className="full-width no-margin">
                {lenderBalanceDiffReason ? (
                  <Box width="100%" display="flex" marginBottom="spacing.4">
                    <Badge variant="notice" icon={InfoIcon}>
                      {lenderBalanceDiffReason}
                    </Badge>
                  </Box>
                ) : null}
                {this.getWithdrawalForm(withdrawalAmount, repayableAmount)}
              </div>
              {showRepaybleHelperText && (
                <div className="repayable-amount-hint">
                  {!this.isFungibleLimitProductType() ? (
                    <>
                      <strong>{repayableAmount}</strong>{' '}
                      <span className="repayable-helper-text">{this.getRepaymentHelperText()}</span>
                    </>
                  ) : (
                    ''
                  )}
                  {showEnableNowCTA && (
                    <div className="automated-withdrawal flex">
                      <div className="automated-withdrawal-wrapper">
                        <div>Automate your withdrawals</div>
                        <Button.Transparent
                          className="text-small enable-now-btn"
                          onClick={this.handleEnableNowClickForAutomatedWithdrawal}
                        >
                          Enable Now
                        </Button.Transparent>
                      </div>

                      <div className="rounded-rectange" />
                    </div>
                  )}
                  {!this.isRepaymentFrequencyCustomORDays90() && this.getBreakupCTA()}
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

  getBreakupCTA() {
    const { showRepaymentDetailsBreakup } = this.state;
    return (
      <div className="flex">
        <div className="text-small full-width no-margin text-strong p-r">
          <strong>
            {showRepaymentDetailsBreakup ? (
              <Button.Transparent onClick={this.toggleBreakup}>
                <i className="i i-chevron-left" />
                Hide Breakup
              </Button.Transparent>
            ) : (
              <Button.Transparent onClick={this.toggleBreakup}>
                Show Breakup
                <i className="i i-chevron-right" />
              </Button.Transparent>
            )}
          </strong>
        </div>
      </div>
    );
  }

  getWithdrawalForm(withdrawalAmount, repayableAmount) {
    const { fungibleData } = this.props;
    const { selectedDueDate } = this.state;
    const updatedCardLimit =
      (fungibleData?.cards?.available_balance || 0) - Number(withdrawalAmount) * 100;

    return (
      <div className="flex withdrawal-form-container">
        <div className={classList(this.isRepaymentFrequencyCustomORDays90() && 'flex-col')}>
          <div className="flex-col">
            <Input.Group
              label="How much do you need?"
              className={`InputGroup--inline Input--vTop no-margin${
                this.isRepaymentFrequencyBimonthly() ? ' less-right-space' : ''
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
              {this.state?.withdraw_errors?.length > 0 && this.state.isTouched && (
                <div className="text-danger error-message">
                  {this.state.withdrawalAmount && this.state.withdraw_errors?.[0]}
                </div>
              )}
            </Input.Group>
            {this.state.isConfirmingWithdraw && this.isFungibleLimitProductType() && (
              <div className="flex">
                <img
                  className="overview-card-icon"
                  src={`${window.cdnBaseUrl}/static/assets/capital/cash_on_card/card_icon.svg`}
                  alt="card icon"
                />
                <div className="card-limit-text">
                  Updated card balance will be
                  <Amount
                    className="updated-card-balance-text"
                    value={updatedCardLimit}
                    parentQuerySelector=".withdrawals__top-summary"
                  />
                </div>
              </div>
            )}
          </div>
          {this.isRepaymentFrequencyCustomORDays90() && !this.isFungibleLimitProductType() && (
            <StaticTenureSelector
              isRepaymentFrequencyDays90={this.isRepaymentFrequencyDays90()}
              handleDueDateChange={this.handleDueDateChange}
              withdrawCTA={this.getWithdrawCTA}
            />
          )}
        </div>
        {!this.isRepaymentFrequencyCustomORDays90() && (
          <div className="withdrawal-cta-container">{this.getWithdrawCTA()}</div>
        )}

        {selectedDueDate && (
          <div
            className={classList(
              'repayment-info-container',
              this.isRepaymentFrequencyCustomORDays90() && 'static-tenure-repayment-info-container',
              'flex',
            )}
          >
            <div className="side-border" />
            <div className="flex-col">
              {this.isFungibleLimitProductType() || this.isRepaymentFrequencyCustomORDays90() ? (
                <div className="flex repay-amount-wrapper">
                  <img
                    src={`${window.cdnBaseUrl}/static/assets/capital/cash_on_card/rupee_icon.svg`}
                    className="amount-icon"
                    alt="amount"
                  />
                  <div className="repay-info-container flex">
                    <div className="top-part flex">
                      <div className="repayment-label">Repayable amount</div>
                    </div>
                    <div className="bottom-part">{repayableAmount}</div>
                  </div>
                </div>
              ) : null}
              {this.isRepaymentFrequencyCustomORDays90() && (
                <div className="breakup-cta-container">{this.getBreakupCTA()}</div>
              )}
              <div className="flex">
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
                    <div className="repayment-label">
                      {this.isFungibleLimitProductType() ||
                      this.isRepaymentFrequencyCustomORDays90()
                        ? 'Repay all dues by'
                        : 'Repay by'}
                    </div>
                    {!this.isRepaymentFrequencyCustomORDays90() && (
                      <span className="icon i-info-outline info-icon" />
                    )}
                  </div>
                  <div className="bottom-part">{this.getDueDate()?.format('DD MMMM, YYYY')}</div>
                  {!this.isRepaymentFrequencyCustomORDays90() && this.getRepaymentTooltipSection()}
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }

  refreshWithdrawView = (fromWhere = '') => {
    this.gaEventDispatcher({
      eventAction: `Withdraw | ${fromWhere}`,
    });
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    this.props.fetchWithdrawalConfiguration({
      id: withdrawalConfigurationDetails.id,
    });
    this.fetchWithdrawals();

    this.changeView(VIEWS.WITHDRAW);
  };

  openRedirectModal = () => {
    this.props.openModal({
      component: <CardsDashboardRedirectionModal closeModal={this.props.closeModal} />,
      size: 'large',
    });
  };

  handleRedirectionClick = () => {
    this.openRedirectModal();
    this.refreshWithdrawView();
  };

  withdrawalSuccessView = () => {
    const { withdrawalAmount } = this.state;
    const {
      user,
      withdrawalConfigurationDetails: { data: { automated_loc, configuration } } = {
        data: { automated_loc: false },
      },
      fungibleData,
    } = this.props;
    const { interest, principle } = this.getRepayableAmount();
    const repayableAmount = parseFloat((interest + principle) * 100).toFixed(2);
    const updatedCardLimit = fungibleData?.cards?.available_balance || 0;
    const selectedDueDate = this.getDueDate();
    const showSettings = this.showSettings();
    const showRepaymentPreferences = showSettings && !this.showFirstTimeRepaymentPreference();

    const handleCloseBtn = () => {
      trackCloseButton();
      this.refreshWithdrawView('close');
    };

    return (
      <div className="withdrawals__action-container card">
        <div className="close-cta">
          <Button.Transparent onClick={handleCloseBtn}>
            <i className="i i-close" />
          </Button.Transparent>
        </div>
        <div
          className={`success-heading-container${
            showSettings ? ' success-heading-container-settings' : ''
          }`}
        >
          <div>
            <div className="title-container">
              <img height={16} src="/dist/css/assets/success-tick-green.svg" alt="Loading icon" />
              <h3 className="text--secondary">
                <strong>Withdrawal Request Successful!</strong>
              </h3>
            </div>
            <p className="disbursal-details text--secondary">
              The money will be transferred to your bank account in a few hours.
            </p>
          </div>
          {showSettings && (
            <div className="withdrawal__ctas">
              <Button.Secondary onClick={() => this.refreshWithdrawView('Done')}>
                Done
              </Button.Secondary>
              {!this.isFungibleLimitProductType() && (
                <Button.Transparent onClick={() => this.refreshWithdrawView('Another Withdrawal')}>
                  Another Withdrawal
                </Button.Transparent>
              )}
            </div>
          )}
        </div>
        <div
          className={`flex withdrawal-info${
            !this.isFungibleLimitProductType() ? ' top-border' : ''
          }`}
        >
          {!this.isFungibleLimitProductType() && (
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
          )}
          <div
            className={`withdrawal__date ${
              this.isFungibleLimitProductType() ? ' fungible_date' : ''
            }`}
          >
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
          {showRepaymentPreferences && (
            <div className="withdrawal__repayment-preference">
              <p className="text--secondary no-margin">Repayment Preference</p>
              <strong className="text--secondary">
                {configuration?.auto_collection ? 'Automatic Daily Deductions' : 'Manual Repayment'}
              </strong>
              <NavLink
                className="change-preference-link"
                exact
                to={`${CASH_ADVANCE_BASE_URL}${CASH_ADVANCE_SECTIONS.SETTINGS}`}
              >
                Change Preference
              </NavLink>
            </div>
          )}
          {!showSettings && (
            <div className="withdrawal__ctas">
              <Button.Primary onClick={() => this.refreshWithdrawView('Done')}>Done</Button.Primary>
              {!this.isFungibleLimitProductType() && (
                <Button.Transparent onClick={() => this.refreshWithdrawView('Another Withdrawal')}>
                  Another Withdrawal
                </Button.Transparent>
              )}
            </div>
          )}
          {this.isFungibleLimitProductType() && (
            <div className="flex">
              <div className="vertical-splitter" />
              <div className="withdrawal__limit">
                <div className="tooltip-wrapper">
                  <p className="text--secondary no-margin">
                    Updated Card Balance <span className="icon i-info-outline info-icon" />
                  </p>
                  <Popover
                    className="card-balance-popover"
                    align="top"
                    theme="dark"
                    parentQuerySelector=".withdrawals__top-summary"
                  >
                    <PopoverBody>
                      <div className="card-balance-text">
                        Your card balance decreases on withdrawing funds from Cash Advance
                      </div>
                    </PopoverBody>
                  </Popover>
                </div>
                <span className="text--secondary">
                  <strong>
                    <Amount
                      value={updatedCardLimit}
                      parentQuerySelector=".withdrawals__top-summary"
                    />
                    <div
                      className="view-details-link"
                      onClick={() => {
                        this.handleRedirectionClick();
                      }}
                    >
                      <Button.Transparent>Card Limit Details</Button.Transparent>
                      <img
                        className="redirect-icon"
                        src={`${window.cdnBaseUrl}/static/assets/capital/cash_on_card/redirect.svg`}
                        alt="redirect icon"
                      />
                    </div>
                  </strong>
                </span>
              </div>
            </div>
          )}
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
        {showSettings && <RepaymentPreferenceBanner />}
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

  changeView = (view) => {
    if (view !== this.currentView) {
      this.setState({
        currentView: view,
      });
    }
  };

  getTopSection = (currentView) => {
    const { selectedDueDate, withdrawalAmount } = this.state;
    const hasDueDateAndWithdrawnAmount = selectedDueDate && withdrawalAmount;
    const { principle = 0, interest = 0 } = hasDueDateAndWithdrawnAmount
      ? this.getRepayableAmount()
      : {};
    const repayableAmount = (principle + interest) * 100;

    switch (currentView) {
      case VIEWS.WITHDRAW:
        return this.withdrawableSection();
      case VIEWS.WITHDRAW_SUCCESS:
        return this.withdrawalSuccessView();
      case VIEWS.WITHDRAW_FAIL:
        return this.withdrawalFailedView();
      case VIEWS.WITHDRAW_FIRST_TIME:
        return (
          <FirstWithdrawalView
            withdrawalAmount={this.state.withdrawalAmount * 100}
            changeView={this.changeView}
            withdraw={this.withdraw}
            repaybleAmount={repayableAmount}
            selectedDueDate={selectedDueDate}
            isInterestTypeReducing={this.isInterestTypeReducing()}
          />
        );
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
    const isWithdrawalOnhold = status === 'ONHOLD' || this.isCashAdvanceDisabled;
    const withdrawalHoldReason = this.isCashAdvanceDisabled
      ? ONHOLD_REASONS.DISABLE_LOC_POST_DPD
      : reason;

    switch (currentView) {
      case VIEWS.WITHDRAW:
      case VIEWS.WITHDRAW_FAIL:
      case VIEWS.WITHDRAW_FIRST_TIME:
        if (!withdrawalConfigurationDetails || withdrawConfigLoading || seedData.loading)
          return (
            <CreditSummary
              loading
              isWithdrawalOnhold={isWithdrawalOnhold}
              reason={withdrawalHoldReason}
            />
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
              reason={withdrawalHoldReason}
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
          {this.isFungibleLimitProductType() ? (
            <FungibleCreditSummary
              openModal={this.props.openModal}
              closeModal={this.props.closeModal}
              data={this.props.fungibleData}
              isMerchantNew={isMerchantNewToCashOnCard()}
            />
          ) : (
            <div className="credit_details_wrapper card">{this.getRightSection(currentView)}</div>
          )}
        </div>
      </div>
    );
  }
}
