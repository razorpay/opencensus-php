import moment from 'moment';

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

export const reportConfigType = (i18) =>
  i18?.isConfigTagEnabled
    ? {
        // hiding by type
        transactions: i18.isConfigTagEnabled('reports.transactions'),
        refunds: i18.isConfigTagEnabled('reports.refunds'),
        rawsql: i18.isConfigTagEnabled('reports.raw_sql'),
        settlement_ondemands: i18.isConfigTagEnabled('reports.on_demand_settlements'),
        qr_code: i18.isConfigTagEnabled('reports.qr_codes'),
        subscriptions: i18.isConfigTagEnabled('reports.subscriptions'),
        paymentlinksv2: i18.isConfigTagEnabled('reports.payment_links'),
        contacts: i18.isConfigTagEnabled('reports.contacts'),
        payment_links: i18.isConfigTagEnabled('reports.payment_links'),
        custom: i18.isConfigTagEnabled('reports.custom'),
        scrooge_refunds: i18.isConfigTagEnabled('reports.refunds'),
        // hiding by name
        'Payments Report With Offers': i18.isConfigTagEnabled('reports.payment_report_with_offers'),
        'QR Code Report with Pay_Id': i18.isConfigTagEnabled('reports.qr_code_report_with_pay_id'),
        'Payment Button Report': i18.isConfigTagEnabled('reports.payment_btn_report'),
        'Payment page': i18.isConfigTagEnabled('reports.payment_pages'),
      }
    : {};
