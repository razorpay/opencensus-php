import { merchantFetch } from 'merchant/utils/ajax';
import { MARKET_PLACE_CONFIG_TYPES } from 'merchant_common/views/Reports/constants';

export const downloadNewReport = async ({ payload, headers, selectedAccount, generatedBy }) => {
  const accountId =
    MARKET_PLACE_CONFIG_TYPES.includes(payload.type) &&
    (!selectedAccount.current ? selectedAccount.id : undefined);

  const finalPayload = {
    ...payload,
    generated_by: generatedBy,
  };

  return merchantFetch({
    url: 'reporting/logs',
    method: 'post',
    data: finalPayload,
    ...(!!accountId && { accountId }),
    headers,
  });
};
