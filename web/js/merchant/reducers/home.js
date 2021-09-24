import ajax, { merchantFetch } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';
import { createLineData } from 'common/utils/chart/index';
import { paiseToRupees } from 'common/utils/rzp-utils';

// graph data
// fetched everytime date is changed
const ANALYTICS_FETCH = 'ANALYTICS_FETCH';

// numbers apart from graph
const ENTITY_TOTALS_FETCH = 'ENTITY_TOTALS_FETCH';
const PAYMENT_BREAKUP_FETCH = 'PAYMENT_BREAKUP_FETCH';
const CURRENT_BALANCE_FETCH = 'CURRENT_BALANCE_FETCH';
const SETTLEMENT_AMOUNT_FETCH = 'SETTLEMENT_AMOUNT_FETCH';
const BALANCE_CONFIG_FETCH = 'BALANCE_CONFIG_FETCH';
const ONDEMAND_RESTRICTIONS_FETCH = 'ONDEMAND_RESTRICTIONS_FETCH';

// Instant activation actions
const SHOW_IA_SUCCESS = 'SHOW_IA_SUCCESS';
const SHOW_KYC_DETAILS = 'SHOW_KYC_DETAILS';
const HIDE_KYC_DETAILS = 'HIDE_KYC_DETAILS';
const SHOW_ACCEPT_PAYMENTS = 'SHOW_ACCEPT_PAYMENTS';
const HIDE_ACCEPT_PAYMENTS = 'HIDE_ACCEPT_PAYMENTS';
const SHOW_PRODUCTS = 'SHOW_PRODUCTS';
const HIDE_PRODUCTS = 'HIDE_PRODUCTS';
const SHOW_PAN_STATUS_MODAL = 'SHOW_PAN_STATUS_MODAL';
const HIDE_PAN_STATUS_MODAL = 'HIDE_PAN_STATUS_MODAL';
const SHOW_KYC_STATUS_MODAL = 'SHOW_KYC_STATUS_MODAL';
const HIDE_KYC_STATUS_MODAL = 'HIDE_KYC_STATUS_MODAL';
const SHOW_FRAUD_DETECTION_MODAL = 'SHOW_FRAUD_DETECTION_MODAL';
const HIDE_FRAUD_DETECTION_MODAL = 'HIDE_FRAUD_DETECTION_MODAL';
const SHOW_TNC_MODAL = 'SHOW_TNC_MODAL';
const HIDE_TNC_MODAL = 'HIDE_TNC_MODAL';
const ESCALATIONS_FETCH = 'ESCALATIONS_FETCH';
const SHOW_PARTNER_KYC_STATUS_MODAL = 'SHOW_PARTNER_KYC_STATUS_MODAL';
const HIDE_PARTNER_KYC_STATUS_MODAL = 'HIDE_PARTNER_KYC_STATUS_MODAL';

const initialState = {
  analytics: {
    loading: true,
    data: [],
    transaction_count: null,
    transaction_amount: null,
  },
  entity_totals: {
    loading: true,
    data: {},
    error: null,
  },
  payment_breakup: {
    loading: true,
    data: {},
    error: null,
  },
  current_balance: {
    loading: true,
    data: {},
    error: null,
  },
  settlement_amount: {
    loading: true,
    data: {},
    error: null,
  },
  merchantBalanceConfigs: {
    loading: true,
    data: {},
    error: null,
  },
  ondemand_restrictions: {
    loading: true,
    data: {},
    error: null,
  },
  instantActivations: {
    showInstantActivationSuccess: false,
    showKYCDetails: false,
    showAcceptPayments: false,
    showProductsModal: false,
    showPANStatus: false,
    showKYCStatus: false,
    showInstantActivationFraudModal: false,
  },
  kycStatusModalType: '',
  kycStatusActivationDuration: '1-2 working days',
  showTnCModal: false,
  limitBreach: {
    amount: null,
    type: null,
    limit: null,
    escaltionsLastUpdatedAt: null,
  },
  partnerActivations: {
    kycStatusModalType: '',
    kycStatusActivationDuration: '1-2 working days',
    showKYCStatus: false,
  },
};

const getTransactionCountData = (data, mode) => {
  return createLineData(
    data.filter((d) => d.mode === mode),
    'count',
    'Successful Transactions',
  );
};

const getTransactionAmountData = (data, mode) => {
  data = JSON.parse(JSON.stringify(data));
  data = data.filter((d) => {
    d.amount = d.amount / 100;
    return d.mode === mode;
  });
  return createLineData(data, 'amount', 'Transaction Volume');
};

export const closeOnboardingStep = (_) => {
  return {
    type: 'CLOSE_ONBOARDING_STEP',
  };
};

export const fetchAnalytics = (params) => {
  return {
    type: ANALYTICS_FETCH,
    payload: ajax({
      url: '/analytics/transactions',
      data: {
        type: 'day',
        from: params.from,
        to: params.to,
      },
    }).then((response) => {
      let transaction_count = null;
      let transaction_amount = null;
      if (response.data) {
        transaction_count = getTransactionCountData(response.data, params.mode);
        transaction_amount = getTransactionAmountData(response.data, params.mode);
      }
      return {
        transaction_count,
        transaction_amount,
      };
    }),
  };
};

export const fetchEntityTotals = () => {
  return {
    type: ENTITY_TOTALS_FETCH,
    payload: ajax('/analytics/aggregations'),
  };
};

export const fetchEscalations = () => {
  return {
    type: ESCALATIONS_FETCH,
    payload: merchantFetch({
      url: 'merchants/onboarding/escalations',
      mode: 'live',
    }),
  };
};

export const fetchPaymentBreakup = () => {
  return {
    type: PAYMENT_BREAKUP_FETCH,
    payload: ajax('/analytics/payment/aggregations'),
  };
};

export const fetchCurrentBalance = () => {
  return {
    type: CURRENT_BALANCE_FETCH,
    payload: merchantFetch('balance'),
  };
};

export const showInstantActivationSuccessModal = () => {
  return {
    type: SHOW_IA_SUCCESS,
  };
};

export const showKYCStatusModal = ({ modalType = '', activationDuration = '1-2 working days' }) => {
  return {
    type: SHOW_KYC_STATUS_MODAL,
    payload: { modalType, activationDuration },
  };
};

export const hideKYCStatusModal = () => {
  return {
    type: HIDE_KYC_STATUS_MODAL,
  };
};

export const showPartnerKYCStatusModal = ({
  modalType = '',
  activationDuration = '1-2 working days',
}) => {
  return {
    type: SHOW_PARTNER_KYC_STATUS_MODAL,
    payload: { modalType, activationDuration },
  };
};

export const hidePartnerKYCStatusModal = () => {
  return {
    type: HIDE_PARTNER_KYC_STATUS_MODAL,
  };
};

export const showKYCDetailsModal = () => ({
  type: SHOW_KYC_DETAILS,
});

export const hideKYCDetailsModal = () => ({
  type: HIDE_KYC_DETAILS,
});

export const showAcceptPaymentsModal = () => ({
  type: SHOW_ACCEPT_PAYMENTS,
});

export const hideAcceptPaymentsModal = () => ({
  type: HIDE_ACCEPT_PAYMENTS,
});

export const showProductsModal = () => {
  return {
    type: SHOW_PRODUCTS,
  };
};

export const hideProductsModal = () => {
  return {
    type: HIDE_PRODUCTS,
  };
};

export const showPANStatusModal = () => {
  return {
    type: SHOW_PAN_STATUS_MODAL,
  };
};

export const hidePANStatusModal = () => {
  return {
    type: HIDE_PAN_STATUS_MODAL,
  };
};

export const fetchSettlementAmount = () => {
  return {
    type: SETTLEMENT_AMOUNT_FETCH,
    payload: merchantFetch('settlements/amount'),
  };
};

export const fetchOndemandRestrictions = () => {
  return {
    type: ONDEMAND_RESTRICTIONS_FETCH,
    payload: merchantFetch('settlements/ondemand/feature/validate'),
  };
};

export const fetchBalanceConfig = () => {
  return {
    type: BALANCE_CONFIG_FETCH,
    payload: merchantFetch(`balance_configs`),
  };
};

export const showFraudDetectionModal = () => ({
  type: SHOW_FRAUD_DETECTION_MODAL,
});

export const hideFraudDetectionModal = () => ({
  type: HIDE_FRAUD_DETECTION_MODAL,
});

export const showTnC = () => ({
  type: SHOW_TNC_MODAL,
});

export const hideTnC = () => ({
  type: HIDE_TNC_MODAL,
});

export default function homeReducer(state = initialState, action) {
  switch (action.type) {
    case `${ANALYTICS_FETCH}::PENDING`:
      return set(state, 'analytics', initialState.analytics);

    case `${ENTITY_TOTALS_FETCH}::PENDING`:
      return set(state, 'entity_totals', initialState.entity_totals);

    case `${PAYMENT_BREAKUP_FETCH}::PENDING`:
      return set(state, 'payment_breakup', initialState.payment_breakup);

    case `${CURRENT_BALANCE_FETCH}::PENDING`:
      return set(state, 'current_balance', initialState.current_balance);

    case `${ANALYTICS_FETCH}::SUCCESS`:
      return merge(state, {
        analytics: {
          transaction_count: action.payload.transaction_count,
          transaction_amount: action.payload.transaction_amount,
          loading: false,
          error: null,
        },
      });

    case `${ENTITY_TOTALS_FETCH}::SUCCESS`:
      return merge(state, {
        entity_totals: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${PAYMENT_BREAKUP_FETCH}::SUCCESS`:
      return merge(state, {
        payment_breakup: {
          data: action.payload.data || initialState.payment_breakup.data,
          loading: false,
          error: null,
        },
      });

    case `${CURRENT_BALANCE_FETCH}::SUCCESS`:
      return merge(state, {
        current_balance: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${ANALYTICS_FETCH}::ERROR`:
      return set(state, 'analytics', {
        loading: false,
        error: action.payload.errors,
        transaction_count: initialState.analytics.transaction_count,
        transaction_amount: initialState.analytics.transaction_amount,
      });

    case `${ENTITY_TOTALS_FETCH}::ERROR`:
      return set(state, 'entity_totals', {
        loading: false,
        error: action.payload.errors,
        data: initialState.entity_totals.data,
      });

    case `${PAYMENT_BREAKUP_FETCH}::ERROR`:
      return set(state, 'payment_breakup', {
        loading: false,
        error: action.payload.errors,
        data: initialState.payment_breakup.data,
      });

    case `${CURRENT_BALANCE_FETCH}::ERROR`:
      return set(state, 'current_balance', {
        loading: false,
        error: action.payload.errors,
        data: initialState.current_balance.data,
      });

    case `${SETTLEMENT_AMOUNT_FETCH}::SUCCESS`:
      return merge(state, {
        settlement_amount: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${SETTLEMENT_AMOUNT_FETCH}::ERROR`:
      return set(state, 'settlement_amount', {
        loading: false,
        error: action.payload.errors,
        data: initialState.settlement_amount.data,
      });

    case `${BALANCE_CONFIG_FETCH}::SUCCESS`:
      return merge(state, {
        merchantBalanceConfigs: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${BALANCE_CONFIG_FETCH}::ERROR`:
      return set(state, 'merchantBalanceConfigs', {
        loading: false,
        error: action.payload.errors,
        data: initialState.merchantBalanceConfigs.data,
      });

    case `${ONDEMAND_RESTRICTIONS_FETCH}::SUCCESS`:
      return merge(state, {
        ondemand_restrictions: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${ONDEMAND_RESTRICTIONS_FETCH}::ERROR`:
      return set(state, 'ondemand_restrictions', {
        loading: false,
        error: action.payload.errors,
        data: initialState.ondemand_restrictions.data,
      });

    case `SHOW_IA_SUCCESS`:
      return set(state, 'instantActivations', {
        showInstantActivationSuccess: true,
      });

    case `SHOW_KYC_STATUS_MODAL`:
      return {
        ...state,
        instantActivations: {
          ...state.instantActivations,
          showKYCStatus: true,
        },
        kycStatusModalType: action.payload.modalType,
        kycStatusActivationDuration: action.payload.activationDuration,
      };

    case `HIDE_KYC_STATUS_MODAL`:
      return {
        ...state,
        instantActivations: {
          ...state.instantActivations,
          showKYCStatus: false,
        },
        kycStatusModalType: '',
      };

    case SHOW_PARTNER_KYC_STATUS_MODAL:
      return {
        ...state,
        partnerActivations: {
          ...state.partnerActivations,
          showKYCStatus: true,
          kycStatusModalType: action.payload.modalType,
          kycStatusActivationDuration: action.payload.activationDuration,
        },
      };

    case HIDE_PARTNER_KYC_STATUS_MODAL:
      return {
        ...state,
        partnerActivations: {
          ...state.partnerActivations,
          showKYCStatus: false,
          kycStatusModalType: '',
        },
      };

    case `SHOW_KYC_DETAILS`:
      return set(state, 'instantActivations', {
        showKYCDetails: true,
      });

    case `HIDE_KYC_DETAILS`:
      return set(state, 'instantActivations', {
        showKYCDetails: false,
      });

    case `SHOW_ACCEPT_PAYMENTS`:
      return set(state, 'instantActivations', {
        showAcceptPayments: true,
      });

    case `HIDE_ACCEPT_PAYMENTS`:
      return set(state, 'instantActivations', {
        showAcceptPayments: false,
      });

    case SHOW_PRODUCTS:
      return set(state, 'instantActivations', {
        showProductsModal: true,
      });

    case HIDE_PRODUCTS:
      return set(state, 'instantActivations', {
        showProductsModal: false,
      });

    case 'CLOSE_ONBOARDING_STEP':
      return set(state, 'closeOnboardingStep', true);

    case SHOW_PAN_STATUS_MODAL:
      return set(state, 'instantActivations', {
        showPANStatus: true,
      });

    case HIDE_PAN_STATUS_MODAL:
      return set(state, 'instantActivations', {
        showPANStatus: false,
      });
    case SHOW_FRAUD_DETECTION_MODAL:
      return set(state, 'instantActivations', {
        showInstantActivationFraudModal: true,
      });
    case HIDE_FRAUD_DETECTION_MODAL:
      return set(state, 'instantActivations', {
        showInstantActivationFraudModal: false,
      });
    case SHOW_TNC_MODAL:
      return merge(state, {
        showTnCModal: true,
      });
    case HIDE_TNC_MODAL:
      return merge(state, {
        showTnCModal: false,
      });
    case `${ESCALATIONS_FETCH}::SUCCESS`:
      return merge(state, {
        limitBreach: {
          ...state.limitBreach,
          amount: paiseToRupees(action.payload.data.amount),
          type: action.payload.data.type,
          limit: paiseToRupees(action.payload.data.limit.payment),
          escaltionsLastUpdatedAt: action.payload.data.updated_at,
        },
      });

    default:
      return state;
  }
}
