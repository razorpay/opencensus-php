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
  const [openDisputes, setOpenDisputes] = useState<DisputeTypeState>({
    count: 0,
    amount: 0,
    loading: false,
    failed: false,
  });
  const [underReviewDisputes, setUnderReviewDisputes] = useState<DisputeTypeState>({
    count: 0,
    amount: 0,
    loading: false,
    failed: false,
  });
  const [wonDisputes, setWonDisputes] = useState<DisputeTypeState>({
    count: 0,
    amount: 0,
    loading: false,
    failed: false,
  });
  const [lostDisputes, setLostDisputes] = useState<DisputeTypeState>({
    count: 0,
    amount: 0,
    loading: false,
    failed: false,
  });
  const [closedDisputes, setClosedDisputes] = useState<DisputeTypeState>({
    count: 0,
    amount: 0,
    loading: false,
    failed: false,
  });
  const [totalDispute, setTotalDispute] = useState<DisputeTypeState>({
    count: 0,
    amount: 0,
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
    onSuccess: (data: DisputeAPIResponse, { setState }) => {
      setState({
        count: data.count || 0,
        amount: data.disputed_amount_sum || 0,
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
      setState: setOpenDisputes,
    });
    fetchDisputes({
      params: {
        ...duration,
        status: 'under_review',
      },
      setState: setUnderReviewDisputes,
    });
    fetchDisputes({
      params: {
        ...duration,
        status: 'won',
      },
      setState: setWonDisputes,
    });
    fetchDisputes({
      params: {
        ...duration,
        status: 'lost',
      },
      setState: setLostDisputes,
    });
    fetchDisputes({
      params: {
        ...duration,
        status: 'closed',
      },
      setState: setClosedDisputes,
    });
    fetchDisputes({
      params: {
        ...duration,
      },
      setState: setTotalDispute,
    });
  };

  return {
    fetchDisputesData,
    disputeData: {
      totalDisputeAmount: totalDispute.amount - closedDisputes.amount,
      totalDisputesCount: totalDispute.count - closedDisputes.count,
      openDisputes: {
        count: openDisputes.count,
        amount: openDisputes.amount,
      },
      underReviewDisputes: {
        count: underReviewDisputes.count,
        amount: underReviewDisputes.amount,
      },
      wonDisputes: {
        count: wonDisputes.count,
        amount: wonDisputes.amount,
      },
      lostDisputes: {
        count: lostDisputes.count,
        amount: lostDisputes.amount,
      },
    },
    loading:
      openDisputes.loading ||
      underReviewDisputes.loading ||
      wonDisputes.loading ||
      lostDisputes.loading ||
      closedDisputes.loading ||
      totalDispute.loading,
    failed:
      openDisputes.failed ||
      underReviewDisputes.failed ||
      wonDisputes.failed ||
      lostDisputes.failed ||
      closedDisputes.failed ||
      totalDispute.failed,
  };
}
