import LoanOrigination from 'merchant/models/Capital/LoanOrigination';
import { merge } from 'common/utils/immutable';
import ConfigFactory from '../../views/Capital/ConfigFactory';

const FETCH_SEED_DATA = 'FETCH_SEED_DATA';
const FETCH_PRODUCTS = 'FETCH_PRODUCTS';
const REGISTER_NEW_APPLICATION = 'REGISTER_NEW_APPLICATION';
const REGISTER_BUSINESS = 'REGISTER_BUSINESS';
const FETCH_LOAN_APPLICATION_META = 'FETCH_LOAN_APPLICATION_META';
const SAVE_APPLICATION_DETAILS = 'SAVE_APPLICATION_DETAILS';
const FETCH_BUSINESS_DETAILS = 'FETCH_BUSINESS_DETAILS';
const SAVE_BUSINESS_DETAILS = 'SAVE_BUSINESS_DETAILS';
const FETCH_APPLICANT_DETAILS = 'FETCH_APPLICANT_DETAILS';
const SAVE_APPLICANT_DETAILS = 'SAVE_APPLICANT_DETAILS';
const REGISTER_LOAN_ATTRIBUTES = 'REGISTER_LOAN_ATTRIBUTES';
const FETCH_DOCUMENT_GROUPS = 'FETCH_DOCUMENT_GROUPS';
const UPLOAD_PRE_VERIFICATION_DOCUMENTS = 'UPLOAD_PRE_VERIFICATION_DOCUMENTS';
const UPLOAD_BANK_STATEMENT = 'UPLOAD_BANK_STATEMENT';
const FETCH_CREDIT_OFFERS = 'FETCH_CREDIT_OFFERS';
const ACCEPT_CREDIT_OFFER = 'ACCEPT_CREDIT_OFFER';
const FETCH_ACCEPTED_CREDIT_OFFER = 'FETCH_ACCEPTED_CREDIT_OFFER';
const GET_AGREEMENT_STATUS = 'GET_AGREEMENT_STATUS';
const GET_NACH_DETAILS = 'GET_NACH_DETAILS';
const SUBMIT_OTP = 'SUBMIT_OTP';
const SAVE_D2C_REPORT_DATA = 'SAVE_D2C_REPORT_DATA';
const FETCH_D2C_REPORT_DATA = 'FETCH_D2C_REPORT_DATA';
const GET_ACCEPTED_OFFER_DETAILS = 'GET_ACCEPTED_OFFER_DETAILS';
const GET_OFFER_VERIFICATION_TASKS = 'GET_OFFER_VERIFICATION_TASKS';
const GET_APPLICATIONS = 'GET_APPLICATIONS';
const GET_LENDER_DETAILS = 'GET_LENDER_DETAILS';
const GET_DISBURSAL_DETAILS = 'GET_DISBURSAL_DETAILS';
const GET_SCHEDULED_VERIFICATION_DETAILS = 'GET_SCHEDULED_VERIFICATION_DETAILS';
const REGISTER_PRODUCT = 'REGISTER_PRODUCT';
const RESET_CAPITAL_LENDING_DATA = 'RESET_CAPITAL_LENDING_DATA';

const entities = [
  'meta',
  'promoter_details',
  'business_details',
  'products',
  'credit_offer_details',
  'nach_details',
  'document_details',
  'bureau_report_details',
  'agreement_details',
  'accepted_offer_details',
  'vnv_details',
  'seed_data',
  'lender_details',
  'disbursal_details',
  'schedule_details',
];

const getInitialState = () => {
  return entities.reduce(
    (acc, entity) => ({
      ...acc,
      [entity]: {
        loading: true,
        data: {},
      },
    }),
    {},
  );
};

export const resetCapitalLendingData = () => {
  return {
    type: RESET_CAPITAL_LENDING_DATA,
  };
};

export const fetchSeedData = () => {
  const loanApplication = new LoanOrigination();

  return {
    type: FETCH_SEED_DATA,
    payload: loanApplication.fetchSeedData(),
  };
};

export const fetchProducts = () => {
  const loanApplication = new LoanOrigination();

  return {
    type: FETCH_PRODUCTS,
    payload: loanApplication.fetchProducts(),
  };
};

export const processBankStatement = (data) => {
  const loanApplication = new LoanOrigination();

  return loanApplication.processBankStatement(data);
};

export const registerNewLoanApplication = () => {
  return {
    type: REGISTER_NEW_APPLICATION,
  };
};

export const registerBusiness = (businessDetails) => {
  return {
    type: REGISTER_BUSINESS,
    data: businessDetails,
  };
};

export const fetchLoanApplicationMeta = (applicationId) => {
  const loanApplication = new LoanOrigination();

  return {
    type: FETCH_LOAN_APPLICATION_META,
    payload: loanApplication.fetchLoanApplicationMeta(applicationId),
  };
};

export const saveApplicationDetails = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: SAVE_APPLICATION_DETAILS,
    payload: loanApplication.saveApplicationDetails(data),
  };
};

export const fetchBusinessDetails = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: FETCH_BUSINESS_DETAILS,
    payload: loanApplication.fetchBusinessDetails(data),
  };
};

export const getBusinessByMerchantId = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: FETCH_BUSINESS_DETAILS,
    payload: loanApplication.getBusinessDetailsByMerchantId(data),
  };
};

export const saveBusinessDetails = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: SAVE_BUSINESS_DETAILS,
    payload: loanApplication.saveBusinessDetails(data),
  };
};

export const saveRequestedLoanAttributes = (data) => {
  return {
    type: REGISTER_LOAN_ATTRIBUTES,
    data,
  };
};

export const fetchApplicantDetails = (applicantId) => {
  const loanApplication = new LoanOrigination();
  return {
    type: FETCH_APPLICANT_DETAILS,
    payload: loanApplication.fetchApplicantDetails(applicantId),
  };
};

export const saveApplicantDetails = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: SAVE_APPLICANT_DETAILS,
    payload: loanApplication.saveApplicantDetails(data),
  };
};

export const submitOtp = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: SUBMIT_OTP,
    payload: loanApplication.submitOtp(data),
  };
};

export const saveD2cReportDetails = (data) => {
  return {
    type: SAVE_D2C_REPORT_DATA,
    payload: data,
  };
};

export const fetchD2cReport = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: FETCH_D2C_REPORT_DATA,
    payload: loanApplication.fetchD2cReport(data),
  };
};

export const fetchDocumentGroups = (isProprietorshipBusiness) => {
  const loanApplication = new LoanOrigination();
  return {
    type: FETCH_DOCUMENT_GROUPS,
    payload: loanApplication.fetchDocumentGroups(isProprietorshipBusiness),
  };
};

export const uploadPreVerificationDocuments = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: UPLOAD_PRE_VERIFICATION_DOCUMENTS,
    payload: loanApplication.uploadPreVerificationDocuments(data),
  };
};

export const uploadBankStatement = (data, progressTracker) => {
  const loanApplication = new LoanOrigination();
  return {
    type: UPLOAD_BANK_STATEMENT,
    payload: loanApplication.uploadBankStatement(data, progressTracker),
  };
};

export const getNetBankingLink = (data) => {
  const loanApplication = new LoanOrigination();
  return loanApplication.getNetBankingLink(data);
};

export const fetchCreditOffers = (data, product) => {
  const loanApplication = new LoanOrigination();
  return {
    type: FETCH_CREDIT_OFFERS,
    payload: loanApplication.fetchCreditOffers(data, product),
  };
};

export const acceptCreditOffer = (data, product) => {
  const loanApplication = new LoanOrigination();
  return {
    type: ACCEPT_CREDIT_OFFER,
    payload: loanApplication.acceptCreditOffer(data, product),
  };
};

export const getAcceptedOffer = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: FETCH_ACCEPTED_CREDIT_OFFER,
    payload: loanApplication.getAcceptedOffer(data),
  };
};

export const getAgreementStatus = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: GET_AGREEMENT_STATUS,
    payload: loanApplication.getAgreementStatus(data),
  };
};

export const getLegalAgreementUrl = (data) => {
  const loanApplication = new LoanOrigination();
  return loanApplication.getLegalAgreementUrl(data);
};

export const getNach = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: GET_NACH_DETAILS,
    payload: loanApplication.getNach(data),
  };
};

export const uploadNach = (data) => {
  const loanApplication = new LoanOrigination();
  return loanApplication.uploadNach(data);
};

export const createNach = (payload) => {
  const loanApplication = new LoanOrigination();
  return loanApplication.createNach(payload);
};

export const getApplicationByParamData = (data) => {
  const loanApplication = new LoanOrigination();
  return loanApplication.fetchLoanApplicationByParam(data);
};

export const changeActiveState = (state, data = null) => {
  return {
    type: 'CHANGE_ACTIVE_STATE',
    state,
    data,
  };
};

export const changePseudoState = (state) => {
  return {
    // If we want something in the UI which doesn't represent the
    // Application state. As overriding application state will have other
    // implications such as state trasitions and UI labels. We wan't
    // something which represents the UI state instead of Application State
    type: 'CHANGE_PSEUDO_STATE',
    state,
  };
};

export const scheduleVerification = (data) => {
  const loanApplication = new LoanOrigination();
  return loanApplication.scheduleVerification(data);
};

export const getLenderDetails = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: GET_LENDER_DETAILS,
    payload: loanApplication.getLender(data),
  };
};

export const getDisbursalDetails = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: GET_DISBURSAL_DETAILS,
    payload: loanApplication.getDisbursalDetails(data),
  };
};

export const getApplications = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: GET_APPLICATIONS,
    payload: loanApplication.getApplications(data),
  };
};

export const getScheduleDetails = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: GET_SCHEDULED_VERIFICATION_DETAILS,
    payload: loanApplication.getScheduleDetails(data),
  };
};

export const getOfferVerificationTasks = (data) => {
  const loanApplication = new LoanOrigination();
  return {
    type: GET_OFFER_VERIFICATION_TASKS,
    payload: loanApplication.getOfferVerificationTasks(data),
  };
};

export const registerProduct = (product) => {
  return {
    type: REGISTER_PRODUCT,
    product,
  };
};

const initialState = getInitialState();

const reducer = (state = initialState, action) => {
  switch (action.type) {
    case RESET_CAPITAL_LENDING_DATA:
      return initialState;
    case `${FETCH_SEED_DATA}::PENDING`:
      return merge(state, {
        seed_data: {
          loading: true,
        },
      });

    case `${FETCH_SEED_DATA}::SUCCESS`:
      return merge(state, {
        seed_data: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${FETCH_SEED_DATA}::ERROR`:
      return merge(state, {
        seed_data: {
          loading: false,
          error: action.payload.errors,
        },
      });

    case `${GET_APPLICATIONS}::PENDING`:
      return merge(state, {
        applications: {
          loading: true,
        },
      });

    case `${GET_APPLICATIONS}::SUCCESS`:
      return merge(state, {
        applications: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${GET_APPLICATIONS}::ERROR`:
      return merge(state, {
        applications: {
          loading: false,
          //TODO: change error
          error: action.payload.errors,
        },
      });

    case `${FETCH_PRODUCTS}::PENDING`:
      return merge(state, {
        products: {
          loading: true,
        },
      });

    case `${FETCH_PRODUCTS}::SUCCESS`:
      return merge(state, {
        products: {
          loading: false,
          data: action.payload.data.products,
        },
      });

    case `${FETCH_PRODUCTS}::ERROR`:
      return merge(state, {
        products: {
          loading: false,
          //TODO: change error
          error: action.payload.errors,
        },
      });
    case REGISTER_PRODUCT:
      return merge(state, {
        meta: {
          ...state.meta,
          configuration: new ConfigFactory({}, action.product).create(),
          product: action.product,
        },
      });
    case 'REGISTER_NEW_APPLICATION':
      return merge(state, {
        meta: {
          loading: false,
          data: {
            application: null,
          },
          configuration: new ConfigFactory({}, state.meta.product).create(),
        },
      });
    case 'REGISTER_BUSINESS':
      return merge(state, {
        meta: {
          ...state.meta,
          loading: false,
          data: {
            application: {
              id: 'new',
              status: 'PROMOTER_INFO_PENDING',
            },
          },
          configuration: new ConfigFactory({}).create(),
        },
        business_details: {
          loading: false,
          data: {
            ...state.business_details.data,
            business: action.data.business,
          },
        },
      });
    case `${FETCH_LOAN_APPLICATION_META}::PENDING`:
      return merge(state, {
        meta: {
          ...state.meta,
          loading: true,
          data: state.meta.data,
        },
      });

    case `${SAVE_APPLICATION_DETAILS}::SUCCESS`:
    case `${UPLOAD_PRE_VERIFICATION_DOCUMENTS}::SUCCESS`:
    case `${FETCH_LOAN_APPLICATION_META}::SUCCESS`: {
      const { application } = action.payload.data;
      const product = state.products.data.find((p) => p.id === application.product_id).name;

      const configLoader = new ConfigFactory(application, product);
      return merge(state, {
        meta: {
          loading: false,
          data: action.payload.data,
          configuration: configLoader.create(),
          product,
        },
      });
    }

    case `${SAVE_APPLICATION_DETAILS}::ERROR`:
    case `${FETCH_LOAN_APPLICATION_META}::ERROR`:
      return merge(state, {
        meta: {
          ...state.meta,
          loading: false,
          error: action.payload.errors,
          data: state.meta.data,
        },
      });

    case REGISTER_LOAN_ATTRIBUTES:
      return merge(state, {
        loan_attributes: action.data,
      });

    case `${FETCH_BUSINESS_DETAILS}::PENDING`:
      return merge(state, {
        business_details: {
          loading: true,
          data: state.business_details.data,
        },
      });

    case `${FETCH_BUSINESS_DETAILS}::SUCCESS`:
      return merge(state, {
        business_details: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${FETCH_BUSINESS_DETAILS}::ERROR`:
      return merge(state, {
        business_details: {
          loading: false,
          error: action.payload.errors,
          data: state.meta.data,
        },
      });

    case `${SAVE_BUSINESS_DETAILS}::PENDING`:
      return merge(state, {
        business_details: {
          loading: true,
          data: state.business_details.data,
        },
      });

    case `${SAVE_BUSINESS_DETAILS}::SUCCESS`:
      return merge(state, {
        business_details: {
          loading: false,
          data: {
            ...state.business_details.data,
            ...action.payload.data,
          },
        },
      });

    case `${SAVE_BUSINESS_DETAILS}::ERROR`:
      return merge(state, {
        business_details: {
          loading: false,
          error: action.payload.errors,
          data: state.business_details.data,
        },
      });

    case `${FETCH_APPLICANT_DETAILS}::PENDING`:
      return merge(state, {
        promoter_details: {
          loading: true,
          data: state.promoter_details.data,
        },
      });

    case `${FETCH_APPLICANT_DETAILS}::SUCCESS`:
    case `${SAVE_APPLICANT_DETAILS}::SUCCESS`:
      return merge(state, {
        promoter_details: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${FETCH_APPLICANT_DETAILS}::ERROR`:
      return merge(state, {
        promoter_details: {
          loading: false,
          error: action.payload.errors,
          data: state.promoter_details.data,
        },
      });

    case `${FETCH_D2C_REPORT_DATA}::PENDING`:
      return merge(state, {
        bureau_report_details: {
          loading: true,
          data: {},
        },
      });

    case `${FETCH_D2C_REPORT_DATA}::SUCCESS`:
    case `${SAVE_D2C_REPORT_DATA}`:
      return merge(state, {
        bureau_report_details: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${FETCH_D2C_REPORT_DATA}::ERROR`:
      return merge(state, {
        bureau_report_details: {
          loading: false,
          error: action.payload.errors,
          data: {},
        },
      });

    case `${FETCH_DOCUMENT_GROUPS}::PENDING`:
      return merge(state, {
        document_groups: {
          loading: true,
          data: {},
        },
      });

    //todo: remove error case from here
    case `${FETCH_DOCUMENT_GROUPS}::ERROR`:
    case `${FETCH_DOCUMENT_GROUPS}::SUCCESS`:
      return merge(state, {
        document_groups: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${FETCH_CREDIT_OFFERS}::SUCCESS`:
      return merge(state, {
        credit_offer_details: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${FETCH_CREDIT_OFFERS}::ERROR`:
      return merge(state, {
        credit_offer_details: {
          loading: false,
          error: action.payload.errors,
        },
      });

    case `${FETCH_CREDIT_OFFERS}::PENDING`:
      return merge(state, {
        credit_offer_details: {
          loading: true,
        },
      });

    case `${FETCH_ACCEPTED_CREDIT_OFFER}::PENDING`:
      return merge(state, {
        accepted_offer_details: {
          loading: true,
        },
      });

    case `${FETCH_ACCEPTED_CREDIT_OFFER}::SUCCESS`:
      return merge(state, {
        accepted_offer_details: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${FETCH_ACCEPTED_CREDIT_OFFER}::ERROR`:
      return merge(state, {
        accepted_offer_details: {
          loading: false,
          error: action.payload.errors,
        },
      });
    case `${GET_AGREEMENT_STATUS}::SUCCESS`:
      return merge(state, {
        agreement_details: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${GET_AGREEMENT_STATUS}::ERROR`:
      return merge(state, {
        agreement_details: {
          loading: false,
          data: null,
          error: action.payload.errors,
        },
      });

    case `${GET_AGREEMENT_STATUS}::PENDING`:
      return merge(state, {
        agreement_details: {
          loading: true,
        },
      });

    case `${GET_NACH_DETAILS}::SUCCESS`:
      return merge(state, {
        nach_details: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${GET_NACH_DETAILS}::ERROR`:
      return merge(state, {
        nach_details: {
          loading: false,
          data: null,
          error: action.payload.errors,
        },
      });

    case `${GET_NACH_DETAILS}::PENDING`:
      return merge(state, {
        nach_details: {
          loading: true,
        },
      });

    case 'CHANGE_ACTIVE_STATE':
      return merge(state, {
        context: {
          ...state.context,
          activeState: action.state,
          data: action.data,
          // as we want the application state to dictate the next UI
          pseudoState: null,
        },
      });

    case 'CHANGE_PSEUDO_STATE':
      return merge(state, {
        context: {
          ...state.context,
          pseudoState: action.state,
        },
      });

    case `${GET_ACCEPTED_OFFER_DETAILS}::SUCCESS`:
      return merge(state, {
        accepted_offer_details: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${GET_ACCEPTED_OFFER_DETAILS}::ERROR`:
      return merge(state, {
        accepted_offer_details: {
          loading: false,
          data: null,
          error: action.payload.errors,
        },
      });

    case `${GET_ACCEPTED_OFFER_DETAILS}::PENDING`:
      return merge(state, {
        accepted_offer_details: {
          loading: true,
        },
      });

    case `${GET_OFFER_VERIFICATION_TASKS}::SUCCESS`:
      return merge(state, {
        vnv_details: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${GET_OFFER_VERIFICATION_TASKS}::ERROR`:
      return merge(state, {
        vnv_details: {
          loading: false,
          data: null,
          error: action.payload.errors,
        },
      });

    case `${GET_OFFER_VERIFICATION_TASKS}::PENDING`:
      return merge(state, {
        vnv_details: {
          loading: true,
        },
      });

    case `${GET_LENDER_DETAILS}::SUCCESS`:
      return merge(state, {
        lender_details: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${GET_LENDER_DETAILS}::ERROR`:
      return merge(state, {
        lender_details: {
          loading: false,
          data: null,
          error: action.payload.errors,
        },
      });

    case `${GET_LENDER_DETAILS}::PENDING`:
      return merge(state, {
        lender_details: {
          loading: true,
        },
      });

    case `${GET_DISBURSAL_DETAILS}::SUCCESS`:
      return merge(state, {
        disbursal_details: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${GET_DISBURSAL_DETAILS}::ERROR`:
      return merge(state, {
        disbursal_details: {
          loading: false,
          data: null,
          error: action.payload.errors,
        },
      });

    case `${GET_DISBURSAL_DETAILS}::PENDING`:
      return merge(state, {
        disbursal_details: {
          loading: true,
        },
      });

    case `${GET_SCHEDULED_VERIFICATION_DETAILS}::SUCCESS`:
      return merge(state, {
        schedule_details: {
          loading: false,
          data: action.payload.data,
        },
      });

    case `${GET_SCHEDULED_VERIFICATION_DETAILS}::ERROR`:
      return merge(state, {
        schedule_details: {
          loading: false,
          data: null,
          error: action.payload.errors,
        },
      });

    case `${GET_SCHEDULED_VERIFICATION_DETAILS}::PENDING`:
      return merge(state, {
        schedule_details: {
          loading: true,
        },
      });
    default:
      return state;
  }
};

export default reducer;
