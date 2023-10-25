import { read, utils } from 'xlsx';

import { isOrgFeatureExist } from 'merchant/models/User';
import { getUser } from 'merchant/store';

export const showNoExpiryPL = () => {
  const user = getUser();
  const orgFeatureEnabled = isOrgFeatureExist('hide_no_expiry_for_pl');
  const merchantFeatureEnabled = user?.isMerchantExpiryPL;
  return orgFeatureEnabled ? merchantFeatureEnabled : true;
};

export const showPayerNamePL = () => {
  return isOrgFeatureExist('enable_payer_name_for_pl');
};

export const showDynamicFields = () => {
  return getUser()?.isDynamicPlOffset;
};

export const convertExcelToObj = async (url) => {
  const f = await (await fetch(url)).arrayBuffer();
  const wb = read(f); // Parse the array buffer.
  const ws = wb.Sheets[wb.SheetNames[0]]; // Get the first worksheet.
  return utils.sheet_to_json(ws); // Generate objects.
};

export const getStatsTableForBatchPLV2 = ({ stats, processedCount }) => {
  return [
    [
      { title: 'Total rows processed', value: processedCount },
      {
        title: 'Records successfully uploaded',
        value: stats.created || 0,
      },
    ],
    [
      {
        title: 'Paid',
        value: <span class="text-success">{stats.paid || 0}</span>,
      },
      {
        title: 'Unpaid',
        value: <span class="text-danger">{stats.expired || 0}</span>,
      },
    ],
  ];
};
