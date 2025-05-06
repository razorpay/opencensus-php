import {
  DashboardType,
  ModeType,
  RefDashboardConfigType,
} from 'merchant_common/views/Reports/types';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import { BaseLogPayloadType } from 'merchant_common/views/Reports/types/log';
import { ScheduleServerPayload, ScheduleType } from 'merchant_common/views/Reports/types/schedule';

import { I18ContextStateType } from 'common/i18/types';
import { SessionReducerState } from 'common/typings';
import { CONFIG_TYPE_BATCH_PAGES } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/constants';
import {
  MONTHLY_INVOICE_REPORT,
  OPTIMISER_SETTLEMENTS,
  PAYMENTS,
  PAYMENTS_REPORTS,
  BILLING_REPORTS
} from 'merchant_common/views/Reports/constants';
import {
  changeScheduleNumericsToString,
  changeScheduleStringsToNumerics,
} from 'merchant_common/views/Reports/features/Schedules/utils';
import {
  getAvailableFormats,
  getMerchantAccounts,
} from 'merchant_common/views/Reports/utils/commonUtils';

import { parseConfigsViaCommonExceptions } from './downloadModal.config';

import { reportConfigType, getCustomConfigs } from '.';
import { isJKOfflineMerchant } from '@libs/shared-utils';

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
  i18?: I18ContextStateType,
  isBillMeMerchantOnly: boolean = false,
): RefDashboardConfigType => {
  return {
    merchant: {
      basePath: '',
      headers: {},
      customConfigs: getCustomConfigs(session),
      availableAccounts: getMerchantAccounts(accounts, session?.user),
      availableFormats: getAvailableFormats(session?.user),
      parseConfigs: (configs: BaseConfigType[]) => {
        if (session?.user?.findTag) {
          return configs.filter(({ name, type }) => {
            const {
              isSupportRole,
              isOptimizerRZPVASEnabled,
              isPaymentPageFileUploadEnabled,
              isHideMonthlyInvoiceEnabled,
            } = session.user;
            const REPORT_CONFIG_TYPE = reportConfigType(i18);
            /**
             * For reports, we check whether the corresponding tag is enabled
             * by matching either the name or type. If the tag is enabled,
             * we return false to remove that report from the rendering list,
             * effectively hiding it from view.
             */
            const isI18TagFound = REPORT_CONFIG_TYPE?.[name] || REPORT_CONFIG_TYPE?.[type];
            const isJKORg = isJKOfflineMerchant(session.org, session.user);

            switch (true) {
              case isI18TagFound:
                return false;
              case name === MONTHLY_INVOICE_REPORT &&
                (isSupportRole || isHideMonthlyInvoiceEnabled):
                return false;
              case name === OPTIMISER_SETTLEMENTS && isOptimizerRZPVASEnabled:
                return false;
              case type === CONFIG_TYPE_BATCH_PAGES:
                return isPaymentPageFileUploadEnabled;
              case isJKORg:
                return name === PAYMENTS_REPORTS || name === PAYMENTS;
              case isBillMeMerchantOnly:
                return type === BILLING_REPORTS;
              default:
                return true;
            }
          });
        } else {
          return configs;
        }
      },
      parseSchedules: (schedules: ScheduleServerPayload[]) => {
        return schedules.map((schedule) => ({
          ...schedule,
          ...changeScheduleStringsToNumerics(schedule),
        }));
      },
      parsePayloadBeforeSubmit: (payload: BaseLogPayloadType, additionalDetails) => {
        const parsedPayload = parseConfigsViaCommonExceptions(payload, additionalDetails, mode);
        return parsedPayload;
      },
      parseSchedulePayloadBeforeSubmit: (payload: ScheduleType) => {
        return {
          ...payload,
          ...changeScheduleNumericsToString(payload),
        };
      },
    },
    partner: {
      basePath: '/partners',
      headers: { 'X-Report-Type': 'partner' },
      customConfigs: [],
      availableAccounts: undefined,
      availableFormats: getAvailableFormats(session?.user),
      parseConfigs: (configs: BaseConfigType[]) => configs,
      parseSchedules: (schedules: ScheduleServerPayload[]) => {
        return schedules.map((schedule) => ({
          ...schedule,
          ...changeScheduleStringsToNumerics(schedule),
        }));
      },
      parsePayloadBeforeSubmit: (payload: BaseLogPayloadType, additionalDetails?) => {
        const parsedPayload = parseConfigsViaCommonExceptions(payload, additionalDetails, mode);
        return parsedPayload;
      },
      parseSchedulePayloadBeforeSubmit: (payload: ScheduleType) => {
        return {
          ...payload,
          ...changeScheduleNumericsToString(payload),
        };
      },
    },
    linkedAccount: {
      basePath: '',
      headers: {},
      customConfigs: [],
      availableAccounts: undefined,
      availableFormats: getAvailableFormats(session?.user),
      parseConfigs: (configs: BaseConfigType[]) => configs,
      parseSchedules: (schedules: ScheduleServerPayload[]) => {
        return schedules.map((schedule) => ({
          ...schedule,
          ...changeScheduleStringsToNumerics(schedule),
        }));
      },
      parsePayloadBeforeSubmit: (payload: BaseLogPayloadType, additionalDetails?) => {
        const parsedPayload = parseConfigsViaCommonExceptions(payload, additionalDetails, mode);
        return parsedPayload;
      },
      parseSchedulePayloadBeforeSubmit: (payload: ScheduleType) => {
        return {
          ...payload,
          ...changeScheduleNumericsToString(payload),
        };
      },
    },
  }[dashboardType];
};
