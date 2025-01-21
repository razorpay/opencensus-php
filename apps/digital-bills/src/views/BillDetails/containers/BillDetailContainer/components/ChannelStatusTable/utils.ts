import { BillStatusTypes } from '@apps/digital-bills/src/utils/constants';
import getTimeStampDiff from '@apps/digital-bills/src/utils/helpers/getTimeStampDiff';

export const getStatusName = (report) =>
  report?.status ? BillStatusTypes[report?.status]?.Name : '-';

export const getReportDeliveryTimeDifference = (status, timeB, timeA) => {
  const isReportDelivered = status === BillStatusTypes.DELIVERED.Value;
  if (isReportDelivered && timeB && timeA) {
    return `${getTimeStampDiff(timeB, timeA)}ms`;
  }
  return 'N.A';
};
