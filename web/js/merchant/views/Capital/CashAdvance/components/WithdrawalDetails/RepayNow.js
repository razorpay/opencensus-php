import React, { useState } from 'react';
import {
  AlertTriangleIcon,
  Amount,
  Box,
  Button,
  InfoIcon,
  Spinner,
  Text,
} from '@razorpay/blade/components';
import moment from 'moment';
import { useQueryClient } from '@tanstack/react-query';
import { connect } from 'react-redux';

import {
  fetchFunctionalWithdrawalConfigByMerchantID,
  fetchWithdrawalDetails,
  fetchWithdrawals,
} from 'merchant/reducers/capital/withdrawals';
import { STATUSES } from 'merchant/views/Capital/CashAdvance/constants';
import { handleRepayment } from 'merchant/views/Capital/CashAdvance/utils';
import { showNotification } from 'merchant_common/reducers/notifications';

import { useWithdrawalRepaymentSummary } from './useWithdrawalRepaymentSummary';

const RepayNow = (props) => {
  const queryClient = useQueryClient();
  const {
    withdrawalId,
    withdrawalDetails,
    user,
    showNotification,
    fetchWithdrawalDetails,
    fetchFunctionalWithdrawalConfigByMerchantID,
    fetchWithdrawals,
  } = props;

  const [loading, setLoading] = useState(false);

  const status = withdrawalDetails?.data?.status;
  const { isFetching, data, isError } = useWithdrawalRepaymentSummary(withdrawalId, status);
  const {
    total_outstanding: totalOutstandingAmount = 0,
    dpd_amount: dpdAmount = 0,
    next_due_date: dueDate = -1,
  } = data?.data || {};

  const repayAmount = Number(dpdAmount) || Number(totalOutstandingAmount);
  const hasDpd = Number(dpdAmount) > 0;
  const disabled =
    repayAmount <= 0 || [STATUSES.INITIATED, STATUSES.REPAID, STATUSES.FAILED].includes(status);

  const handleRepayClick = async () => {
    setLoading(true);

    try {
      await handleRepayment({
        repayAmount,
        merchantId: user.current,
        withdrawalId,
      });

      showNotification({
        type: 'success',
        message: 'Repayment successful!',
      });

      Promise.all([
        fetchWithdrawalDetails({
          reference_type: 'ID',
          reference_id: withdrawalId,
        }),
        fetchWithdrawals({
          reference: [
            {
              reference_id: user.current,
              reference_type: 'OWNER_ID',
            },
          ],
          skip: 0,
          count: 25,
          order_by: 'CREATED_AT',
          order_direction: 'desc',
          product_type: '',
        }),
        fetchFunctionalWithdrawalConfigByMerchantID({
          owner_id: user.current,
          owner_type: 'RZP_MERCHANT',
        }),
        queryClient.invalidateQueries(['get-withdrawal-repayment-summary']),
      ]);
    } catch (error) {
      showNotification({
        type: 'error',
        message: 'Something went wrong, Please try again!',
      });
    } finally {
      setLoading(false);
    }
  };

  if (isFetching) {
    return (
      <Box width="100%" height="70px" display="flex" justifyContent="center" alignItems="center">
        <Spinner />
      </Box>
    );
  }

  if (isError) {
    return (
      <Box
        width="100%"
        padding="20px 34px 11px 41px"
        marginBottom="spacing.6"
        backgroundColor="brand.primary.300"
      >
        Failed to load repay amount
      </Box>
    );
  }

  return (
    <div
      style={{
        backgroundColor: hasDpd ? 'hsla(9, 91%, 56%, 0.09)' : 'hsla(218, 89%, 51%, 0.09)',
      }}
    >
      <Box width="100%" padding="20px 34px 11px 41px" marginBottom="spacing.6">
        <Box display="flex" justifyContent="space-between" alignItems="center">
          <Box display="flex" flexDirection="column">
            {hasDpd ? (
              <Box display="flex" alignItems="center" gap="8px">
                <AlertTriangleIcon color="surface.text.subtle.lowContrast" size="large" />
                <Text size="medium" color="surface.text.subtle.lowContrast" weight="bold">
                  Pending due amount
                </Text>
              </Box>
            ) : (
              <Text size="medium" color="surface.text.subtle.lowContrast" weight="bold">
                Upcoming due amount
              </Text>
            )}

            <Amount
              marginY="8px"
              size="heading-large-bold"
              value={repayAmount / 100}
              testID="repay-amount"
            />

            {Number(dueDate) !== -1 && (
              <Text color="surface.text.normal.lowContrast">
                Auto-collection scheduled{' '}
                {moment
                  .unix(dueDate)
                  .calendar(null, {
                    sameElse: 'MMM DD, YYYY',
                  })
                  .toLowerCase()}
              </Text>
            )}
          </Box>

          <Button isLoading={loading} onClick={handleRepayClick} isDisabled={disabled}>
            Repay Now
          </Button>
        </Box>

        <Box display="flex" alignItems="center" marginTop="spacing.5">
          <InfoIcon marginRight="spacing.1" size="small" color="surface.text.subdued.lowContrast" />
          <Box className="repay-now-info-text">
            <Text color="surface.text.subdued.lowContrast">
              Repayments take upto 4 hours to process after collection
            </Text>
          </Box>
        </Box>
      </Box>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = {
  fetchWithdrawals,
  fetchWithdrawalDetails,
  fetchFunctionalWithdrawalConfigByMerchantID,
  showNotification,
};

export default connect(mapStateToProps, mapDispatchToProps)(RepayNow);
