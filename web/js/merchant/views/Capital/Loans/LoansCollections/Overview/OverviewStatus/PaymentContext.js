import React, { createContext, useContext, useReducer } from 'react';

const PaymentContext = createContext();

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
        amountToBePaid: action.payload,
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
    case 'RETRY_REPAYMENT': {
      return {
        ...state,
        failedRepayments: [],
        successRepayments: [],
        amountToBePaid: action.payload,
      };
    }
    default: {
      return state;
    }
  }
}

function PaymentProvider({ children, creditId, disbursalId, showHeader, showFooter }) {
  const loanData = {
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
