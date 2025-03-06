import { useEffect } from 'react';
import { fetchReportingConfigProps } from 'merchant_common/views/Reports/types';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getConfigs } from 'merchant_common/views/Reports/api/overview';
import { trackReportsSection } from 'merchant_common/views/Reports/configs/analytics.config';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import { adminGeneratedReportIds } from '@libs/web-nexus/merchant/views/Reports/components/ReportModal/components/CreateConfigModel/utils/constants';

export const useFetchReportingConfig = ({
  headers,
  fetchReportsConfigsSuccess,
  fetchReportsConfigsFailed,
  handleOverviewLoading,
  dashboardType,
  parseConfigs,
}: fetchReportingConfigProps): void => {
  const setStandardConfigs = useCreateConfigModal((state) => state.setStandardConfigs);
  const setUserConfigs = useCreateConfigModal((state) => state.setUserConfigs);
  const configsFetchTrigger = useCreateConfigModal((state) => state.configsFetchTrigger);
  const standardConfigs: BaseConfigType[] = [];
  const userConfigs: BaseConfigType[] = [];
  const handleAllConfigsFetch = async (validationCheck = true) => {
    try {
      if (validationCheck) {
        handleOverviewLoading({
          key: 'allConfigs',
          state: true,
        });
        const configs = await getConfigs(headers);
        if (configs?.data?.items) {
          configs.data.items.forEach((item) => {
            if (adminGeneratedReportIds.includes(item.consumer ?? '')) {
              standardConfigs.push(item);
            } else {
              userConfigs.push(item);
            }
          });
          setStandardConfigs(standardConfigs);
          setUserConfigs(userConfigs);
          fetchReportsConfigsSuccess({
            configs: !!parseConfigs ? parseConfigs(configs.data.items) : configs.data.items,
          });
        } else {
          trackReportsSection({
            actionName: 'Configs Fetch Failed',
            dashboardType,
          });
        }
      }
    } catch (err) {
      showNotification({
        type: 'error',
        message: 'Unable to fetch reports at this moment, please try again later.',
      });
      fetchReportsConfigsFailed();
    }
  };

  useEffect(() => {
    handleAllConfigsFetch();
  }, [configsFetchTrigger]);
};
