import { useQuery, useMutation } from '@tanstack/react-query';
import { merchantFetch } from 'merchant/utils/ajax';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare/index';
import { PartnerConfig } from './configTypes';
import { ShowNotificationType } from 'common/typings';

type configValues = {
  data: TODO_PD;
  refetch: TODO_PD;
  isLoading: boolean;
  isError: boolean;
};

export const useFetchConfig = (
  id: string,
  appId: null | string,
  showNotification?: ShowNotificationType,
): configValues => {
  const apiParam = appId ? `application_id=${appId}` : `partner_id=${id}`;
  const fetchPartnerConfigs = () => {
    return merchantFetch({
      url: `partner_config?${apiParam}`,
      method: 'get',
    });
  };

  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['get-partner-config'],
    queryFn: fetchPartnerConfigs,
    refetchOnWindowFocus: false,
    onError: ({ errors }: TODO_PD) => {
      showNotification?.({
        type: 'error',
        message: errors,
      });
    },
  });
  return { data, isLoading, isError, refetch };
};

type saveConfig = {
  saveConfig: TODO_PD;
};

export const useSaveConfig = (
  refetch: TODO_PD,
  showNotification?: ShowNotificationType,
): saveConfig => {
  const savePartnerConfig = (values: PartnerConfig) => {
    const body = {
      partner_metadata: {
        brand_name: values.brand_name,
        brand_color: values.brand_color.replace('#', ''),
        text_color: values.text_color.replace('#', ''),
      },
    };
    return merchantFetch({
      url: `partner_config/${values.config_id}`,
      method: 'put',
      data: body,
    });
  };

  const { mutate: saveConfig } = useMutation({
    mutationFn: savePartnerConfig,
    onSuccess: () => {
      refetch();
      showNotification?.({
        type: 'success',
        message: 'Configuration saved Successfully',
      });
    },
    onError: ({ errors }: TODO_PD) => {
      showNotification?.({
        type: 'error',
        message: errors,
      });
    },
  });
  return { saveConfig };
};
