import { LOADING } from 'merchant/components/Activation/Constants';
import { formInitialValues, getProductValue } from './utils';

export const initialState = {
  activeTab: 0,
  isLoading: true,
  isSavingForm: LOADING.INITIAL,
  initialValues: formInitialValues,
  tabsValidity: [false, false, false, false],
};

export const initialStateForRevamp = {
  activeTab: 0,
  isLoading: true,
  isSavingForm: LOADING.INITIAL,
  initialValues: formInitialValues,
  tabsValidity: [false, false, false],
  purposeCodeList: [],
  isPurposeCodeSpecial: false,
  initialPurposeCode: null,
};

export const reducer = (state, action) => {
  switch (action.type) {
    case 'ACTIVE_TAB':
      return {
        ...state,
        activeTab: action.payload,
      };
    case 'NEXT_TAB':
      return {
        ...state,
        activeTab: state.activeTab + 1,
      };
    case 'PREV_TAB':
      return {
        ...state,
        activeTab: state.activeTab - 1,
      };
    case 'LOADING':
      return {
        ...state,
        isLoading: action.payload,
      };
    case 'IS_SAVING_FORM':
      return {
        ...state,
        isSavingForm: action.payload,
      };
    case 'FORM_INITIAL_VALUES':
      return {
        ...state,
        initialValues: action.payload,
      };
    case 'TAB_VALIDITY':
      return {
        ...state,
        tabsValidity: action.payload,
      };
    case 'TRIGGER_SOURCE':
      return {
        ...state,
        initialValues: {
          ...state.initialValues,
          products: getProductValue(action.payload),
        },
      };
    case 'SET_PURPOSE_CODE_LIST':
      return {
        ...state,
        purposeCodeList: action.payload,
        isLoading: false,
      };
    case 'IS_PURPOSE_CODE_SPECIAL':
      return {
        ...state,
        isPurposeCodeSpecial: action.payload,
      };
    case 'SET_INIT_PURPOSE_CODE':
      return {
        ...state,
        initialPurposeCode: action.payload,
      };
    default:
      return state;
  }
};
