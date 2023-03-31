import moment from 'moment';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';

const DATE_FORMAT = 'DD MMM YYYY';

export const getFormattedDate = (unixTimeStamp) =>
  moment(unixTimeStamp, 'X').local().format(DATE_FORMAT);

export const getTimeUnix = (timeMoment) =>
  moment(timeMoment)
    .clone()
    .startOf('minute')
    .diff(moment(timeMoment).clone().startOf('day'), 'seconds');

export const getStartAndEndUnixTimeStampsForDaysFrom = (
  numberOfDays = 0,
  dateInMoment = moment().subtract(1, 'day'),
) => {
  const lastDayEndOfDayUnix = dateInMoment.endOf('day').format('X');
  const lastNthStartOfDayUnix = dateInMoment
    .subtract(Math.max(numberOfDays - 1, 0), 'day')
    .startOf('day')
    .format('X');

  return [Number(lastNthStartOfDayUnix), Number(lastDayEndOfDayUnix)];
};

export const extractExtensionFromTemplate = (template) =>
  ((template || {}).file_meta || {}).extension;

const logProcessingStatuses = ['created', 'processing'];
export const isLogInProgress = (logStatus) => logProcessingStatuses.includes(logStatus);

export const getActualLogStatus = ({ status, fileId }) => {
  switch (status) {
    case 'created':
    case 'processing':
    case 'retrying':
      return 'in-process';
    case 'processed':
      return fileId ? 'ready-for-download' : 'no-data';
    case 'failed':
      return 'error';
    default:
      return null;
  }
};

export const REPORT_CONFIG_TYPE = {
  // hiding by type
  settlements: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Settlements,
  transactions: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Transactions,
  refunds: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Refunds,
  rawsql: HIDDEN_INTERNATIONAL_FEATURES_TAGS.RawSQL,
  settlement_ondemands: HIDDEN_INTERNATIONAL_FEATURES_TAGS.OnDemandSettlements,
  qr_code: HIDDEN_INTERNATIONAL_FEATURES_TAGS.QrCodes,
  subscriptions: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Subscriptions,
  paymentlinksv2: HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentLinks,
  contacts: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Contacts,
  payment_links: HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentLinks,
  custom: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Custom,
  scrooge_refunds: HIDDEN_INTERNATIONAL_FEATURES_TAGS.Refunds,
  // hiding by name
  'Payments Report With Offers': HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentReportWithOffers,
  'QR Code Report with Pay_Id': HIDDEN_INTERNATIONAL_FEATURES_TAGS.QRCodeReportWithPayID,
  'Payment Button Report': HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentBtnReport,
  'Payment page': HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentPages,
};
