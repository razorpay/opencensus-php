import { BaseLogPayloadType } from 'merchant_common/views/Reports/types/log';

export const parseConfigsViaCommonExceptions = (
  payload: BaseLogPayloadType,
  additionalDetails,
  mode,
) => {
  switch (true) {
    case Boolean(additionalDetails.selectedConfig.type === 'paymentlinksv2' && mode):
      return {
        ...payload,
        template_overrides: {
          ...payload?.template_overrides,
          filters: {
            ...payload?.template_overrides?.filters,
            paymentlinksv2: {
              ...payload?.template_overrides?.filters?.paymentlinksv2,
              mode: {
                ...payload?.template_overrides?.filters?.paymentlinksv2?.mode,
                op: 'IN',
                values: [mode],
              },
            },
          },
        },
      };
    default:
      return payload;
  }
};
