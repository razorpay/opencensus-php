import { useQuery, useMutation } from 'react-query';
import { merchantFetch } from 'merchant/utils/ajax';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare/index';

type configValues = {
  data: TODO_PD;
  refetch: TODO_PD;
  isLoading: boolean;
  isError: boolean;
};

export const useFetchConfig = (id: string, showNotification: TODO_PD): configValues => {
  const fetchPartnerConfigs = () => {
    return merchantFetch({
      url: `partner_config?partner_id=${id}`,
      method: 'get',
    });
  };

  const { data, isLoading, isError, refetch } = useQuery(
    'get-partner-config',
    fetchPartnerConfigs,
    {
      refetchOnWindowFocus: false,
      onError: ({ errors }: TODO_PD) => {
        showNotification?.({
          type: 'error',
          message: errors,
        });
      },
    },
  );
  return { data, isLoading, isError, refetch };
};

type User = {
  config_id: string;
  brand_name: string;
  brand_color: string;
  text_color: string;
  brand_logo: string;
};

type saveConfig = {
  saveConfig: TODO_PD;
};

export const useSaveConfig = (refetch: TODO_PD, showNotification: TODO_PD): saveConfig => {
  const savePartnerConfig = (values: User) => {
    const body = {
      brand_name: values.brand_name,
      brand_color: values.brand_color.replace('#', ''),
      text_color: values.text_color.replace('#', ''),
    };
    return merchantFetch({
      url: `partner_config/${values.config_id}`,
      method: 'put',
      data: body,
    });
  };

  const [saveConfig] = useMutation(savePartnerConfig, {
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

type saveLogo = {
  saveLogo: TODO_PD;
};

export const useSaveLogo = (refetch: TODO_PD, showNotification: TODO_PD): saveLogo => {
  const handleUploadLogo = async (data: TODO_PD) => {
    const { event, configId } = data;
    let file: TODO_PD;
    if (event?.target?.files && event?.target?.files.length > 0) {
      file = event.target.files[0];
    }
    const formData = new FormData();
    formData.append('logo', file);
    const response = await merchantFetch({
      url: `partner_config/${configId}/logo`,
      method: 'post',
      data: formData,
    });
    return response;
  };

  const [saveLogo] = useMutation(handleUploadLogo, {
    onSuccess: () => {
      refetch();
      showNotification?.({
        type: 'success',
        message: 'Logo Uploaded Successfully',
      });
    },
    onError: ({ errors }: TODO_PD) => {
      showNotification?.({
        type: 'error',
        message: errors,
      });
    },
  });

  return { saveLogo };
};
