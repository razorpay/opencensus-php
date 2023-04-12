import { REPORT_CONFIG_TYPE, getCustomConfigs } from '.';
import {
  DashboardType,
  ModeType,
  RefDashboardConfigType,
} from 'merchant_common/views/Reports/types';
import { getMerchantAccounts } from 'merchant_common/views/Reports/utils/commonUtils';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import { BaseLogPayloadType } from 'merchant_common/views/Reports/types/log';
import { User } from 'common/typings';

/**
 * @param {DashboardType} dashboardType Dashboard type where the core report component will be used.
 * @param {User} sessionUser `session.user` object from the redux state.
 * @param {unknown} accounts `accounts` from the redux state.
 * @param {ModeType} mode `session.mode` from the redux state.
 * @returns {RefDashboardConfigType} a config with all the exceptions defined to modify core reports behaviour wrt a given dashboardType passed.
 */
export const getReportsDashboardConfig = (
  dashboardType: DashboardType,
  sessionUser?: User,
  accounts?,
  mode?: ModeType,
): RefDashboardConfigType => {
  return {
    merchant: {
      basePath: '',
      headers: {},
      customConfigs: getCustomConfigs(sessionUser),
      availableAccounts: getMerchantAccounts(accounts, sessionUser),
      parseConfigs: (configs: BaseConfigType[]) => {
        if (sessionUser?.findTag) {
          return configs.filter((config) => {
            const inCheck = REPORT_CONFIG_TYPE?.[config.name] ?? REPORT_CONFIG_TYPE?.[config.type];
            const isI18TagFound = inCheck && sessionUser.findTag(inCheck);

            switch (true) {
              case isI18TagFound:
                return false;
              case config?.name === 'Monthly Invoice Report' && sessionUser.isSupportRole:
                return false;
              default:
                return true;
            }
          });
        } else {
          return configs;
        }
      },
      parsePayloadBeforeSubmit: (payload: BaseLogPayloadType, additionalDetails) => {
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
      },
    },
    partner: {
      basePath: '/partners',
      headers: { 'X-Report-Type': 'partner' },
      customConfigs: [],
      availableAccounts: undefined,
      parseConfigs: (configs: BaseConfigType[]) => configs,
      parsePayloadBeforeSubmit: (payload) => payload,
    },
    linkedAccount: {
      basePath: '',
      headers: {},
      customConfigs: [],
      availableAccounts: undefined,
      parseConfigs: (configs: BaseConfigType[]) => configs,
      parsePayloadBeforeSubmit: (payload) => payload,
    },
  }[dashboardType];
};
