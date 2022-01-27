import { User, XCAStatus, APIResponseType } from '../TypeDeclare/XCATypeDeclare';
import * as TrackerStatusConst from './../Tracker/TrackerConstant';
import { getItem } from 'common/utils/localStorage';
import moment from 'moment';

const getProceededBank = (
  preferences: Array<Record<string, APIResponseType>> | undefined = [],
): APIResponseType => {
  const selectedPref: Record<string, APIResponseType> | undefined = preferences.find(
    ({ group, type, value }) =>
      group === 'x_merchant_current_accounts' && type === 'ca_proceeded_bank' && value,
  );
  return selectedPref?.value || null;
};

const checkTrackerViewLimit = () => {
  let canShowTracker = true;
  const lastSetTimeStr = getItem('neostone-tracker');
  const lastSetTime = lastSetTimeStr?.length ? Number(lastSetTimeStr) : null;

  if (lastSetTime) {
    const currentTime = moment().unix();
    const daysElasped = Math.floor((currentTime - lastSetTime) / 60 / 60 / 24);
    if (daysElasped >= 7) canShowTracker = false;
  }

  return canShowTracker;
};

const getXCAStatus = (user: User): XCAStatus => {
  let showState: APIResponseType = null;
  let proceededBank: APIResponseType = null;

  if (user?.ca_activation_status === 'activated' && !checkTrackerViewLimit())
    return {
      showState,
      proceededBank,
    };
  proceededBank = getProceededBank(user?.attributes?.items);
  if (proceededBank === null) showState = 'offers-for-you';
  else showState = 'neostone-tracker';
  return {
    showState,
    proceededBank,
  };
};

export const getDerivedStatus = (data) => {
  const {
    banking_account_activation_details: { business_pan_validation, declaration_step },
    status = '',
  } = data;
  const {
    panValidationFailureStatus,
    derivedCaApplicationStatus,
    caApplicationStatus,
  } = TrackerStatusConst;
  switch (status) {
    case caApplicationStatus.CREATED:
      if (declaration_step === 1) {
        if (business_pan_validation == 'initiated' || business_pan_validation == 'pending') {
          return derivedCaApplicationStatus.PAN_PENDING;
        }
        if (panValidationFailureStatus.includes(business_pan_validation)) {
          return derivedCaApplicationStatus.PAN_FAILED;
        }
        return derivedCaApplicationStatus.RECIEVED;
      }
      return derivedCaApplicationStatus.PENDING;
    case caApplicationStatus.PICKED:
    case caApplicationStatus.INITIATED:
      return derivedCaApplicationStatus.INITIATED;
    default:
      return status;
  }
};

const getXBaseURL = (): string => {
  switch (window.APP_ENV) {
    case 'stage':
    case 'beta':
    case 'dev':
      return 'https://x.np.razorpay.in';
    case 'func':
      return 'https://x-func.np.razorpay.in';
    case 'prod':
    case 'production':
      return 'https://x.razorpay.com';
    default:
      return 'https://x.razorpay.com';
  }
};

const getICICIApplicationData = (
  data: Array<any> = [],
  basBusinessID: string | null,
): Record<string, any> =>
  (Array.isArray(data) &&
    data.find(
      ({ business_id, application_status, combined_application_status }) =>
        business_id === basBusinessID && (application_status || combined_application_status),
    )) ||
  {};

const getICICIPanStatus = (documents: Array<any> = []): string | null => {
  const panDocumentTypes = ['BUSINESS_PAN', 'PERSONAL_PAN'];
  const noOfDocs = documents.length;

  let panStatus: string | null = null;

  for (let i = 0; i < noOfDocs; i += 1) {
    const document = documents[i];
    const isPanDocument = panDocumentTypes.includes(document.document_type);

    if (isPanDocument) {
      panStatus = document.document_verification_status;
      break;
    }
  }

  return panStatus;
};

const getErrorMessage = ({ code, errors }: { code: any; errors: Array<any> }): string => {
  const errorMessageArray: Array<any> = [];
  if (code) errorMessageArray.push(code);
  if (Array.isArray(errors) && errors.length > 0) errorMessageArray.push(errors[0]?.toString());
  else errorMessageArray.push('An error occurred in connecting to the server');
  return errorMessageArray.join(' - ');
};

export { getXCAStatus, getXBaseURL, getICICIApplicationData, getICICIPanStatus, getErrorMessage };
