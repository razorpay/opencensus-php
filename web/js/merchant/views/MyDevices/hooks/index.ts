import { useToast } from '@razorpay/blade/components';
import { useMutation, useQuery } from '@tanstack/react-query';

import { merchantFetch } from 'merchant/utils/ajax';

import { FETCH_DEVICE_ROUTE, getUpdateDeviceRoute } from '../constants';

interface UPDATE_DEVICE_PAYLOAD {
  id?: string;
  settings: {
    deviceLanguagePref: string;
  };
}

const getDevices = async () => {
  try {
    return await merchantFetch({
      absUrl: FETCH_DEVICE_ROUTE,
    });
  } catch (e: any) {
    throw new Error(e);
  }
};

const updateDeviceLanguage = async (payload) => {
  try {
    return await merchantFetch({
      absUrl: getUpdateDeviceRoute(payload.id),
      method: 'PATCH',
      data: payload.settings,
    });
  } catch (e: any) {
    throw new Error(e);
  }
};

export const useFetchDevices = () => {
  return useQuery(['fetchDevices'], () => getDevices());
};

export const useDeviceLanguage = () => {
  const toast = useToast();
  const mutation = useMutation<unknown, unknown, UPDATE_DEVICE_PAYLOAD>({
    mutationFn: (payload) => updateDeviceLanguage(payload),
    onError: () => {
      toast.show({
        color: 'negative',
        content: 'Error updating device language',
      });
    },
    onSuccess: () => {
      toast.show({
        color: 'positive',
        content: 'Device language updated',
      });
    },
  });

  return {
    mutate: mutation.mutateAsync,
  };
};
