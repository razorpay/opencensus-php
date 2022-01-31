import React, { createContext, useContext, useReducer } from 'react';

const PaymentContext = createContext();

export const ACTIONS = {
  RESET_REPAYMENT: 'RESET_REPAYMENT',
  SET_TOTAL_DUE_INFO: 'SET_TOTAL_DUE_INFO',
};
function reducer(state, action) {
  switch (action.type) {
    case 'SET_ORDER_DATA': {
      return {
        ...state,
        ...action.payload,
      };
    }
    case 'SET_PAYMENT_AMOUNT': {
      return {
        ...state,
        amountToBePaid: Number(action.payload),
      };
    }
    case ACTIONS.SET_TOTAL_DUE_INFO: {
      return {
        ...state,
        totalDueInfo: { amount: Number(action.payload.amount), date: Number(action.payload.date) },
      };
    }
    case 'SET_PAYMENT_METHOD': {
      return {
        ...state,
        paymentMethod: action.payload,
      };
    }
    case 'REPAYMENT_SUCCESS': {
      return {
        ...state,
        successRepayments: [...state.successRepayments, action.payload],
      };
    }
    case 'REPAYMENT_FAILURE': {
      return {
        ...state,
        failedRepayments: [...state.failedRepayments, action.payload],
      };
    }
    case ACTIONS.RESET_REPAYMENT: {
      return {
        ...state,
        failedRepayments: [],
        successRepayments: [],
        amountToBePaid: 0,
        totalDueInfo: { amount: 0, date: null },
      };
    }
    default: {
      return state;
    }
  }
}

function PaymentProvider({ children, creditId, disbursalId, showHeader, showFooter }) {
  const loanData = {
    totalDueInfo: { amount: 0, date: null }, // date in unix seconds
    amountToBePaid: 0,
    creditId,
    disbursalId,
    failedRepayments: [],
    successRepayments: [],
    showHeader,
    showFooter,
  };
  const [state, dispatch] = useReducer(reducer, loanData);
  const value = { state, dispatch };
  return <PaymentContext.Provider value={value}>{children}</PaymentContext.Provider>;
}

function useLoanData() {
  const context = useContext(PaymentContext);
  if (!context) {
    throw new Error('Incorrect usage of context');
  }
  return context;
}

export { PaymentProvider, useLoanData };
