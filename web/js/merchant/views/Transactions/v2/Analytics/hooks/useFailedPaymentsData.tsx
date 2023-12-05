import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';

import { fetch } from 'common/services/rest/rest-fetch';
import {
  FailedDataHookResponse,
  FailedPaymentsAPIResponse,
  FailedOverviewResult,
  EnvironmentsModes,
} from 'merchant/views/Transactions/v2/Analytics/types';
import { Duration } from 'merchant/views/Transactions/v2/common/types';

import {
  accumulateFailedPaymentsTotal,
  accumulateFailureData,
  getFailedPaymentsRequestPayload,
} from './utils';

export default function useFailedPaymentsData(): FailedDataHookResponse {
  const [failedPaymentsData, setFailedPaymentsData] = useState<number>(0);
  const [failureInfo, setFailureInfo] = useState<FailedOverviewResult>({
    customer: {
      value: 0,
      failure_types: [],
    },
    bank: {
      value: 0,
      failure_types: [],
    },
    business: {
      value: 0,
      failure_types: [],
    },
    others: {
      value: 0,
      failure_types: [],
    },
  });
  const [isLoading, setLoading] = useState(false);
  const [isFailed, setFailed] = useState(false);

  const fetchFailedPaymentsQuery = async ({ from, to }) => {
    const response = await fetch<FailedPaymentsAPIResponse>({
      url: 'success-rate/merchant/error',
      method: 'POST',
      // this API doesn't work in test mode
      mode: EnvironmentsModes.LIVE,
      data: getFailedPaymentsRequestPayload(from, to),
    });
    return response;
  };
  const { mutate: fetchFailedPaymentsData } = useMutation({
    mutationFn: (dateDuration: Duration) => fetchFailedPaymentsQuery(dateDuration),
    onMutate: () => {
      setLoading(true);
      setFailed(false);
    },
    onSuccess: (data: FailedPaymentsAPIResponse) => {
      const totalFailedPayments = accumulateFailedPaymentsTotal(data);
      const transformedData = accumulateFailureData(data);
      setFailureInfo(transformedData);
      setFailedPaymentsData(totalFailedPayments);
      setLoading(false);
    },
    onError: () => {
      setFailed(true);
      setLoading(false);
    },
  });
  return {
    failedPaymentsData,
    failureInfo,
    fetchFailedPaymentsData,
    loading: isLoading,
    failed: isFailed,
  };
}
