import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';

import { fetch } from 'common/services/rest/rest-fetch';
import {
  EnvironmentsModes,
  RefundDataHookParams,
  RefundDataHookResponse,
  RefundResponse,
  RefundsAnalyticsAPIResponse,
  TxnStatus,
} from 'merchant/views/Transactions/v2/Analytics/types';
import { Duration } from 'merchant/views/Transactions/v2/common/types';

import { accumalateCountAmount, getRefundsRequestPayload } from './utils';

export default function useRefundsData({
  isRefundPendingEnabled,
}: RefundDataHookParams): RefundDataHookResponse {
  const [refundsData, setRefundsData] = useState<RefundResponse>({
    refunded: {
      count: 0,
      amount: 0,
    },
    processing: {
      count: 0,
      amount: 0,
    },
    failed: {
      count: 0,
      amount: 0,
    },
  });
  const [isLoading, setLoading] = useState(false);
  const [isFailed, setFailed] = useState(false);

  const fetchRefundQuery = async (dateDuration) => {
    const response = await fetch<RefundsAnalyticsAPIResponse>({
      url: 'merchant/analytics',
      method: 'POST',
      // this API doesn't work in test mode
      mode: EnvironmentsModes.LIVE,
      data: getRefundsRequestPayload(dateDuration, 'live'),
    });
    return response;
  };
  const { mutate: fetchRefundData } = useMutation({
    mutationFn: (dateDuration: Duration) => fetchRefundQuery(dateDuration),
    onMutate: () => {
      setLoading(true);
      setFailed(false);
    },
    onSuccess: (data: RefundsAnalyticsAPIResponse) => {
      const { refundcountnormal, refundcountinstant, refundsuminstant, refundsumnormal } = data;
      const updatedRefundResponse: RefundResponse = {
        refunded: {
          count: 0,
          amount: 0,
        },
        processing: {
          count: 0,
          amount: 0,
        },
        failed: {
          count: 0,
          amount: 0,
        },
      };

      if (isRefundPendingEnabled) {
        const processedStatusRefunds = accumalateCountAmount({
          countData: refundcountnormal.result,
          sumData: refundsumnormal.result,
          status: [TxnStatus.PROCESSED],
        });
        const processedStatusRefundsInstant = accumalateCountAmount({
          countData: refundcountinstant.result,
          sumData: refundsuminstant.result,
          status: [TxnStatus.PROCESSED],
        });
        updatedRefundResponse.refunded.count =
          processedStatusRefunds.count + processedStatusRefundsInstant.count;

        updatedRefundResponse.refunded.amount =
          processedStatusRefunds.amount + processedStatusRefundsInstant.amount;

        const processingStatusRefunds = accumalateCountAmount({
          countData: refundcountnormal.result,
          sumData: refundsumnormal.result,
          status: [TxnStatus.FAILED, TxnStatus.PROCESSED],
          exclude: true,
        });
        const processingStatusRefundsInstant = accumalateCountAmount({
          countData: refundcountinstant.result,
          sumData: refundsuminstant.result,
          status: [TxnStatus.FAILED, TxnStatus.PROCESSED],
          exclude: true,
        });
        updatedRefundResponse.processing.count =
          processingStatusRefunds.count + processingStatusRefundsInstant.count;
        updatedRefundResponse.processing.amount =
          processingStatusRefunds.amount + processingStatusRefundsInstant.amount;
      } else {
        const processedStatusRefunds = accumalateCountAmount({
          countData: refundcountnormal.result,
          sumData: refundsumnormal.result,
          status: [TxnStatus.FAILED],
          exclude: true,
        });
        const processedStatusRefundsInstant = accumalateCountAmount({
          countData: refundcountinstant.result,
          sumData: refundsuminstant.result,
          status: [TxnStatus.PROCESSED],
        });
        updatedRefundResponse.refunded.count =
          processedStatusRefunds.count + processedStatusRefundsInstant.count;

        updatedRefundResponse.refunded.amount =
          processedStatusRefunds.amount + processedStatusRefundsInstant.amount;

        const processingStatusRefundsInstant = accumalateCountAmount({
          countData: refundcountinstant.result,
          sumData: refundsuminstant.result,
          status: [TxnStatus.FAILED, TxnStatus.PROCESSED],
          exclude: true,
        });
        updatedRefundResponse.processing.count = processingStatusRefundsInstant.count;
        updatedRefundResponse.processing.amount = processingStatusRefundsInstant.amount;
      }
      const failedStatusRefunds = accumalateCountAmount({
        countData: refundcountnormal.result,
        sumData: refundsumnormal.result,
        status: [TxnStatus.FAILED],
      });
      const failedStatusRefundsInstant = accumalateCountAmount({
        countData: refundcountinstant.result,
        sumData: refundsuminstant.result,
        status: [TxnStatus.FAILED],
      });
      updatedRefundResponse.failed.count =
        failedStatusRefunds.count + failedStatusRefundsInstant.count;
      updatedRefundResponse.failed.amount =
        failedStatusRefunds.amount + failedStatusRefundsInstant.amount;
      setRefundsData(updatedRefundResponse);
      setLoading(false);
    },
    onError: () => {
      setFailed(true);
      setLoading(false);
    },
  });

  return { fetchRefundData, refundsData, loading: isLoading, failed: isFailed };
}
