import { merchantFetch } from 'merchant/utils/ajax';
import { getPayloadForResolvedDowntimes } from './helpers';
import type { DowntimeResponseType, DowntimeMetaDataType } from './types';

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
