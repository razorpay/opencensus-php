import { merchantFetch } from 'merchant/utils/ajax';
import { getPayloadForResolvedDowntimes, getPayloadForSR } from './helpers';
import type { DowntimeResponseType, DowntimeMetaDataType, SuccessRateResponseType } from './types';

export const fetchOngoingDowntimes = async (): Promise<DowntimeMetaDataType[]> => {
  const { data: onGoingDowntimeData }: DowntimeResponseType = await merchantFetch({
    url: 'payments/downtimes/ongoing',
    method: 'get',
  });
  return onGoingDowntimeData;
};

export const fetchResolvedDowntimes = async (): Promise<DowntimeMetaDataType[]> => {
  const { data: previousDowntimes }: DowntimeResponseType = await merchantFetch({
    url: 'payments/downtimes/resolved',
    method: 'GET',
    data: getPayloadForResolvedDowntimes(),
  });
  return previousDowntimes;
};

type fetchSuccessRateTypes = {
  srKey: string;
};

export const fetchSuccessRate = async ({
  srKey,
}: fetchSuccessRateTypes): Promise<SuccessRateResponseType> =>
  merchantFetch({
    url: `success-rate/merchant/sr`,
    data: getPayloadForSR({ srKey }),
    method: 'post',
  });
