import React, { useState, useEffect } from 'react';
import {
  Box,
  Heading,
  Text,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  Button,
  Badge,
  CheckIcon,
  CloseIcon,
} from '@razorpay/blade/components';
import { getCurrencySymbol } from '@razorpay/i18nify-js/currency';
import isEmpty from 'lodash/isEmpty';

import { METHODS_MAP } from 'merchant/views/Navigator/constants';

import { RefundFailureAlert } from './FailureAlerts';

export const RefundTesting = ({
  gateway,
  payments,
  setPayments,
  isPaymentsTableLoading,
  initiateRefund,
  isRefundDetialsFetched,
  setIsRefundDetialsFetched,
  refundResult,
  setRefundResult,
  integrationType,
}) => {
  const [refundInitiated, setRefundInitiated] = useState({});

  const initiateRefundClick = (item) => {
    setIsRefundDetialsFetched(false);
    setRefundResult({});
    setRefundInitiated({
      transactionId: item.id,
      inProgress: true,
    });
    initiateRefund(item.id, item.amount);
  };

  const REFUND_TABLE_COLUMNS = [
    {
      label: 'Transaction ID',
      value: (item) => item.id,
    },
    {
      label: 'Gateway',
      value: (item) => item.settled_by,
    },
    {
      label: 'Method',
      value: (item) => METHODS_MAP[item.method],
    },
    {
      label: 'Amount',
      value: (item) => `${getCurrencySymbol(item.currency || 'INR')}${item.amount / 100}`,
    },
    {
      label: '',
      value: (item) => {
        if (item.refund_success === undefined) {
          return (
            <Button
              variant="primary"
              onClick={() => initiateRefundClick(item)}
              size="small"
              isLoading={refundInitiated.transactionId === item.id && !!refundInitiated.inProgress}
              isDisabled={
                refundInitiated.inProgress !== undefined ? refundInitiated.inProgress : false
              }
            >
              Initiate refund
            </Button>
          );
        } else if (item.refund_success) {
          return (
            <Badge color="positive" icon={CheckIcon}>
              Initiated
            </Badge>
          );
        } else {
          return (
            <Badge color="negative" icon={CloseIcon}>
              Refund failed
            </Badge>
          );
        }
      },
    },
  ];

  useEffect(() => {
    if (isRefundDetialsFetched) {
      setPayments(
        payments.map((payment) => {
          if (payment.id === refundResult.transactionId) {
            return {
              ...payment,
              refund_success: refundResult.refund_success,
            };
          }
          return payment;
        }),
      );
      if (refundResult.transactionId === refundInitiated.transactionId) {
        setRefundInitiated({});
      }
    }
  }, [isRefundDetialsFetched, refundResult]);

  return (
    <Box display="flex" flexDirection="column" padding="spacing.8">
      <Heading size="medium">Refund testing</Heading>
      <Text as="p" marginTop="spacing.5" size="small" color="surface.text.gray.normal">
        Please select the transaction(s) for which you&#39;d want to initiate refund(s) for
      </Text>
      <Box width="44rem" marginTop="spacing.7">
        <Table
          data={{
            nodes: payments,
          }}
          showStripedRows={true}
          isLoading={isPaymentsTableLoading}
          gridTemplateColumns="30% 20% 15% 15% 20%"
        >
          {(items) => {
            return (
              <>
                <TableHeader>
                  <TableHeaderRow>
                    {REFUND_TABLE_COLUMNS.map(({ label }, index) => (
                      <TableHeaderCell key={index}>{label}</TableHeaderCell>
                    ))}
                  </TableHeaderRow>
                </TableHeader>
                <TableBody>
                  {items?.length > 0 &&
                    items.map((item, index) => (
                      <TableRow key={index} item={item}>
                        {REFUND_TABLE_COLUMNS.map(({ value }, index) => (
                          <TableCell key={index}>{value(item)}</TableCell>
                        ))}
                      </TableRow>
                    ))}
                </TableBody>
              </>
            );
          }}
        </Table>
      </Box>
      {!isPaymentsTableLoading && payments?.length <= 0 && (
        <Text as="p" marginTop="spacing.5" textAlign="center">
          No transactions found
        </Text>
      )}
      {!isEmpty(refundResult) && !refundResult?.refund_success && (
        <RefundFailureAlert gateway={gateway} integrationType={integrationType} />
      )}
    </Box>
  );
};
