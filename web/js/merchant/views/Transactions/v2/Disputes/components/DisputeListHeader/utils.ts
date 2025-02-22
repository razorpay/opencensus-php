import isEmpty from 'lodash/isEmpty';
import moment from 'moment';

import { getMajorAmountFromMinorUnit } from 'common/utils/rzp-utils';
import { REPORT_HEADER_MAP } from 'merchant/views/Transactions/v2/Disputes/components/DisputeListHeader/constants';
import {
  disputePhaseMap,
  disputesStatusVariantMap,
} from 'merchant/views/Transactions/v2/Disputes/constants';
import { getKeyByValue } from 'merchant/views/Transactions/v2/Disputes/utils';

export const getExcelReportData = ({ mid, downloadData }) => {
  return {
    finalDataSend: [
      {
        category: `Disputes Report`,
        data: downloadData.map((item) => {
          const rowData = {};
          for (const key in REPORT_HEADER_MAP) {
            if (key && item.hasOwnProperty(key)) {
              const value = item[key];
              if (key === 'dispute_status') {
                rowData[REPORT_HEADER_MAP[key]] = disputesStatusVariantMap[value].content;
              } else if (key === 'phase') {
                rowData[REPORT_HEADER_MAP[key]] = getKeyByValue(disputePhaseMap, value);
              } else if (key === 'dispute_amount' || key === 'payment_amount') {
                rowData[REPORT_HEADER_MAP[key]] = getMajorAmountFromMinorUnit(
                  value,
                  item.currency ?? 'INR',
                );
              } else rowData[REPORT_HEADER_MAP[key]] = value;
            }
          }
          return rowData;
        }),
        headers: Object.values(REPORT_HEADER_MAP),
      },
    ],
    fileFormat: 'xlsx',
    fileName: `Dispute_report_${mid}_${moment().format('DDMMYY_hhmm')}`,
  };
};

export const getDownloadReportQueryParams = () => {
  const url = new URL(window.location.href);
  const params = new URLSearchParams(url.search);
  params.delete('payment_id');
  params.delete('id');
  const defaultDuration = `from=${moment().add(-7, 'd').startOf('day').unix()}&to=${moment()
    .endOf('day')
    .unix()}`;
  return `?${isEmpty(params.toString()) ? defaultDuration : params.toString()}`;
};
