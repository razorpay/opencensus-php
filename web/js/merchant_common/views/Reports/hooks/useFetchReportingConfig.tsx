import { useEffect } from 'react';
import { fetchReportingConfigProps } from 'merchant_common/views/Reports/types';

import { showNotification } from 'merchant_common/reducers/notifications';
import { getConfigs } from 'merchant_common/views/Reports/api/overview';
import { trackReportsSection } from 'merchant_common/views/Reports/configs/analytics.config';

export const useFetchReportingConfig = ({
  headers,
  fetchReportsConfigsSuccess,
  fetchReportsConfigsFailed,
  handleOverviewLoading,
  dashboardType,
  parseConfigs,
}: fetchReportingConfigProps): void => {
  const handleAllConfigsFetch = async (validationCheck = true) => {
    try {
      if (validationCheck) {
        handleOverviewLoading({
          key: 'allConfigs',
          state: true,
        });
        const configs = await getConfigs(headers);
        if (configs?.data?.items) {
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
  }, []);
};
