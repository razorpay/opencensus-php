import { PREVERIFICATION_VIEW_STATES } from 'merchant/views/Capital/Loans/constants';

export const initialState = {
  view: PREVERIFICATION_VIEW_STATES.ACTIVE,
  option: null,
  netbankingErrorCount: 0,
  netbankingDisabled: false,
  files: [],
  isModalOpen: false,
};

export const reducer = (state, action) => {
  switch (action.type) {
    case 'DISABLE_NETBANKING': {
      return {
        ...state,
        netbankingDisabled: true,
      };
    }

    case 'UPDATE_VIEW': {
      return {
        ...state,
        view: action.payload,
      };
    }

    case 'UPDATE_OPTION': {
      return {
        ...state,
        option: action.payload,
      };
    }

    case 'UPDATE_NETBANKING_ERROR_COUNT': {
      return {
        ...state,
        netbankingErrorCount: state.netbankingErrorCount + 1,
      };
    }

    case 'ADD_FILE': {
      return {
        ...state,
        files: [...state.files, action.payload],
      };
    }

    case 'REMOVE_FILE': {
      return {
        ...state,
        files: state.files.filter((_, index) => index !== action.payload),
      };
    }

    case 'TOGGLE_MODAL_VISIBILITY': {
      return {
        ...state,
        isModalOpen: action.payload,
      };
    }
  }
};
