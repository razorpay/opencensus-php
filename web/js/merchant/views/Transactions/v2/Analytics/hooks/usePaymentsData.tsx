import { useState } from 'react';
import { useMutation } from 'react-query';

import { fetch } from 'common/services/rest/rest-fetch';
import {
  AnalyticsAPISegemntResult,
  EnvironmentsModes,
  PaymentAnalyticsAPIResponse,
  PaymentDataHookParams,
  PaymentDataHookResponse,
  PaymentResponse,
  TxnStatus,
} from 'merchant/views/Transactions/v2/Analytics/types';
import { Duration } from 'merchant/views/Transactions/v2/common/types';
import { paiseToRupees } from 'common/utils/rzp-utils';

import { accumalateCountAmount, getAnalyticsRequestPayload } from './utils';

export default function usePaymentData({
  isRefundPendingEnabled,
}: PaymentDataHookParams): PaymentDataHookResponse {
  const [paymentsData, setPaymentsData] = useState<PaymentResponse>({
    paymentCapturedCount: 0,
    paymentCapturedAmount: 0,
    refundCount: 0,
    refundAmount: 0,
    paymentByMethod: [],
  });
  const [isLoading, setLoading] = useState(false);
  const [isFailed, setFailed] = useState(false);

  const fetchPaymentQuery = async (dateDuration) => {
    // this API doesn't work in test mode
    const mode = EnvironmentsModes.LIVE;
    const response = await fetch<PaymentAnalyticsAPIResponse>({
      url: 'merchant/analytics',
      method: 'POST',
      mode,
      data: getAnalyticsRequestPayload(dateDuration, mode),
    });
    return response;
  };
  const [fetchPaymentData] = useMutation(
    (dateDuration: Duration) => fetchPaymentQuery(dateDuration),
    {
      onMutate: () => {
        setLoading(true);
        setFailed(false);
      },
      onSuccess: (data: PaymentAnalyticsAPIResponse) => {
        const {
          paymentcount,
          paymentsum,
          paymentbymethod,
          refundcountnormal,
          refundcountinstant,
          refundsuminstant,
          refundsumnormal,
        } = data;
        const updatePaymentMethodResponse: PaymentResponse = {
          paymentCapturedCount: 0,
          paymentCapturedAmount: 0,
          refundCount: 0,
          refundAmount: 0,
          paymentByMethod: [],
        };
        if (paymentcount?.result) {
          const capturedPayment = paymentcount.result.find(
            (item: AnalyticsAPISegemntResult) => item.status === TxnStatus.CAPTURED,
          );
          updatePaymentMethodResponse.paymentCapturedCount = capturedPayment?.value || 0;
        }
        if (paymentsum?.result) {
          const capturedPayment = paymentsum.result.find(
            (item: AnalyticsAPISegemntResult) => item.status === TxnStatus.CAPTURED,
          );
          updatePaymentMethodResponse.paymentCapturedAmount = capturedPayment?.value || 0;
        }
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
          updatePaymentMethodResponse.refundCount =
            processedStatusRefunds.count + processedStatusRefundsInstant.count;

          updatePaymentMethodResponse.refundAmount =
            processedStatusRefunds.amount + processedStatusRefundsInstant.amount;
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
          updatePaymentMethodResponse.refundCount =
            processedStatusRefunds.count + processedStatusRefundsInstant.count;

          updatePaymentMethodResponse.refundAmount =
            processedStatusRefunds.amount + processedStatusRefundsInstant.amount;
        }

        if (paymentbymethod?.result) {
          const splitByPaymentMethod: Array<{
            label: string;
            value: number;
          }> = [];
          const sortedPaymentMethod = paymentbymethod.result
            .sort((a, b) => b.value - a.value)
            .map((item) => ({
              label: item.method,
              value: paiseToRupees(item.value),
            }));
          splitByPaymentMethod.push(...sortedPaymentMethod.slice(0, 3));
          if (sortedPaymentMethod.length > 4) {
            const others = sortedPaymentMethod.slice(3).reduce((acc, item) => acc + item.value, 0);
            if (others) {
              splitByPaymentMethod.push({
                label: 'Others',
                value: others,
              });
            }
          } else {
            splitByPaymentMethod.push(...sortedPaymentMethod.slice(3));
          }
          updatePaymentMethodResponse.paymentByMethod = splitByPaymentMethod;
        }
        setPaymentsData(updatePaymentMethodResponse);
        setLoading(false);
      },
      onError: () => {
        setFailed(true);
        setLoading(false);
      },
    },
  );

  return { fetchPaymentData, paymentsData, loading: isLoading, failed: isFailed };
}
