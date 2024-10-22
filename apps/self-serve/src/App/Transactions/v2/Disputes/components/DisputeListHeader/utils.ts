import moment from 'moment';
import { REPORT_HEADER_MAP } from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeListHeader/constants';
import {
  disputePhaseMap,
  disputesStatusVariantMap,
} from 'apps/self-serve/src/App/Transactions/v2/Disputes/constants';
import { getKeyByValue } from 'apps/self-serve/src/App/Transactions/v2/Disputes/utils';

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
