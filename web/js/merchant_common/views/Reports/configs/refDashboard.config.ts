import { REPORT_CONFIG_TYPE, getCustomConfigs } from '.';
import {
  DashboardType,
  ModeType,
  RefDashboardConfigType,
} from 'merchant_common/views/Reports/types';
import { getMerchantAccounts } from 'merchant_common/views/Reports/utils/commonUtils';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import { BaseLogPayloadType } from 'merchant_common/views/Reports/types/log';
import { SessionReducerState } from 'common/typings';
import { parseConfigsViaCommonExceptions } from './downloadModal.config';

/**
 * @param {DashboardType} dashboardType Dashboard type where the core report component will be used.
 * @param {SessionReducerState} session `session.user` object from the redux state.
 * @param {unknown} accounts `accounts` from the redux state.
 * @param {ModeType} mode `session.mode` from the redux state.
 * @returns {RefDashboardConfigType} a config with all the exceptions defined to modify core reports behavior wrt a given dashboardType passed.
 */
export const getReportsDashboardConfig = (
  dashboardType: DashboardType,
  session?: SessionReducerState,
  accounts?,
  mode?: ModeType,
): RefDashboardConfigType => {
  return {
    merchant: {
      basePath: '',
      headers: {},
      customConfigs: getCustomConfigs(session),
      availableAccounts: getMerchantAccounts(accounts, session?.user),
      parseConfigs: (configs: BaseConfigType[]) => {
        if (session?.user?.findTag) {
          return configs.filter((config) => {
            const inCheck = REPORT_CONFIG_TYPE?.[config.name] ?? REPORT_CONFIG_TYPE?.[config.type];
            const isI18TagFound = inCheck && session.user.findTag(inCheck);

            switch (true) {
              case isI18TagFound:
                return false;
              case config?.name === 'Monthly Invoice Report' && session.user.isSupportRole:
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
        const parsedPayload = parseConfigsViaCommonExceptions(payload, additionalDetails, mode);
        return parsedPayload;
      },
    },
    partner: {
      basePath: '/partners',
      headers: { 'X-Report-Type': 'partner' },
      customConfigs: [],
      availableAccounts: undefined,
      parseConfigs: (configs: BaseConfigType[]) => configs,
      parsePayloadBeforeSubmit: (payload: BaseLogPayloadType, additionalDetails?) => {
        const parsedPayload = parseConfigsViaCommonExceptions(payload, additionalDetails, mode);
        return parsedPayload;
      },
    },
    linkedAccount: {
      basePath: '',
      headers: {},
      customConfigs: [],
      availableAccounts: undefined,
      parseConfigs: (configs: BaseConfigType[]) => configs,
      parsePayloadBeforeSubmit: (payload: BaseLogPayloadType, additionalDetails?) => {
        const parsedPayload = parseConfigsViaCommonExceptions(payload, additionalDetails, mode);
        return parsedPayload;
      },
    },
  }[dashboardType];
};
