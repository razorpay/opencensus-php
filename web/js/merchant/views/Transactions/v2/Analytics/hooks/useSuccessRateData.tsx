import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';

import { fetch } from '@federated/apps/shell/rest-fetch';
import {
  EnvironmentsModes,
  SuccessRateAPIResponse,
  SuccessRateDataHookResponse,
} from 'merchant/views/Transactions/v2/Analytics/types';
import { Duration } from 'merchant/views/Transactions/v2/common/types';

import { getSuccessRateRequestPayload } from './utils';

export default function useSuccessRateData(): SuccessRateDataHookResponse {
  const [successRateData, setSuccessRateData] = useState<number>(0);
  const [isLoading, setLoading] = useState(false);
  const [isFailed, setFailed] = useState(false);

  const fetchSuccessRateQuery = async ({ from, to }: Duration): Promise<SuccessRateAPIResponse> => {
    const response = await fetch<SuccessRateAPIResponse>({
      url: 'success-rate/merchant/sr',
      method: 'POST',
      // this API doesn't work in test mode.
      mode: EnvironmentsModes.LIVE,
      data: getSuccessRateRequestPayload(from, to),
    });
    return response;
  };
  const { mutate: fetchSuccessRateData } = useMutation({
    mutationFn: (dateDuration: Duration) => fetchSuccessRateQuery(dateDuration),
    onMutate: () => {
      setLoading(true);
      setFailed(false);
    },
    onSuccess: (data: SuccessRateAPIResponse) => {
      if (data.sr) {
        setSuccessRateData(data.sr);
      }
      setLoading(false);
    },
    onError: () => {
      setFailed(true);
      setLoading(false);
    },
  });

  return { successRateData, fetchSuccessRateData, loading: isLoading, failed: isFailed };
}
