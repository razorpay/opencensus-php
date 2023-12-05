import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';

import { fetch } from 'common/services/rest/rest-fetch';
import { Environments } from 'common/typings';
import {
  DisputeTypeState,
  DisputeDataHookResponse,
  DisputeAPIResponse,
  FetchDisputesData,
} from 'merchant/views/Transactions/v2/Analytics/types';
import { Duration } from 'merchant/views/Transactions/v2/common/types';

export default function useDisputesData({ mode }: { mode: Environments }): DisputeDataHookResponse {
  const [openDisputesCount, setOpenDisputesCount] = useState<DisputeTypeState>({
    value: 0,
    loading: false,
    failed: false,
  });
  const [underReviewDisputesCount, setUnderReviewDisputesCount] = useState<DisputeTypeState>({
    value: 0,
    loading: false,
    failed: false,
  });
  const [totalDisputeAmount, setTotalDisputeAmount] = useState<DisputeTypeState>({
    value: 0,
    loading: false,
    failed: false,
  });

  const fetchDisputesQuery = async (params) => {
    const response = await fetch<DisputeAPIResponse>({
      url: 'disputes-aggregate',
      method: 'GET',
      mode,
      params,
    });
    return response;
  };
  const { mutate: fetchDisputes } = useMutation({
    mutationFn: ({ params }: FetchDisputesData) => fetchDisputesQuery(params),
    onMutate: ({ setState }) => {
      setState((prevState) => ({
        ...prevState,
        loading: true,
        failed: false,
      }));
    },
    onSuccess: (data: DisputeAPIResponse, { setState, dataField }) => {
      setState({
        value: data[dataField] || 0,
        loading: false,
        failed: false,
      });
    },
    onError: (_, { setState }) => {
      setState((prevState) => ({
        ...prevState,
        loading: false,
        failed: true,
      }));
    },
  });

  const fetchDisputesData = (duration: Duration) => {
    fetchDisputes({
      params: {
        ...duration,
        status: 'open',
      },
      setState: setOpenDisputesCount,
      dataField: 'count',
    });
    fetchDisputes({
      params: {
        ...duration,
        status: 'under_review',
      },
      setState: setUnderReviewDisputesCount,
      dataField: 'count',
    });
    fetchDisputes({
      params: {
        ...duration,
      },
      setState: setTotalDisputeAmount,
      dataField: 'disputed_amount_sum',
    });
  };

  return {
    fetchDisputesData,
    disputeData: {
      openDisputesCount: openDisputesCount.value,
      underReviewDisputesCount: underReviewDisputesCount.value,
      totalDisputeAmount: totalDisputeAmount.value,
    },
    loading:
      openDisputesCount.loading || underReviewDisputesCount.loading || totalDisputeAmount.loading,
    failed:
      openDisputesCount.failed || underReviewDisputesCount.failed || totalDisputeAmount.failed,
  };
}
