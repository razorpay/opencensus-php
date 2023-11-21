/* eslint-disable @typescript-eslint/naming-convention */
import { Box, Button, Spinner, Text, Tooltip } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import moment from 'moment';
import React, { useState } from 'react';
import isEmpty from 'lodash/isEmpty';
import { useSplitzService } from 'common/splitz';

import usePreclosureAmount from './usePreclosureAmount';
import {
  fetchFunctionalWithdrawalConfigByMerchantID,
  fetchWithdrawalDetails,
  fetchWithdrawals,
} from 'merchant/reducers/capital/withdrawals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { handleRepayment } from 'merchant/views/Capital/CashAdvance/utils';
import { useQueryCache } from 'react-query';
import { User } from 'common/typings';

const TOOLTIP_DISABLE_MESSAGES = {
  PROCESSING_WITHDRAWAL: 'Pre-Closure is disabled while repayment is being processed',
  DISABLE_FOR_TWO_DAYS: 'Unavailable for 2 days from withdrawal',
};

const PreClosure = ({
  dueDate,
  withdrawalId,
  user,
  showNotification,
  fetchWithdrawalDetails,
  fetchWithdrawals,
  fetchFunctionalWithdrawalConfigByMerchantID,
}: {
  dueDate: string;
  withdrawalId: string;
  user: User;
  showNotification: (x: any) => void;
  fetchWithdrawalDetails: (x: any) => void;
  fetchWithdrawals: (x: any) => void;
  fetchFunctionalWithdrawalConfigByMerchantID: (x: any) => void;
}): JSX.Element | null => {
  const { isLoading, data } = usePreclosureAmount(withdrawalId);
  const queryCache = useQueryCache();
  const [loading, setLoading] = useState(false);
  const { total = 0, error_response = '' } = data?.data || {};

  const {
    abExperiments: { capitalPreclosureEdiExp },
  } = useSplitzService();
  const isExperimentEnabled = capitalPreclosureEdiExp.variables.result === 'on';

  const isTodayDueDate = moment().isSame(Number(dueDate) * 1000, 'day');

  const handleRepaymentClick = async (): Promise<void> => {
    setLoading(true);

    try {
      await handleRepayment({
        repayAmount: Number(total),
        merchantId: user.current,
        withdrawalId,
        metadata: {
          pre_closure: {
            is_pre_closure: true,
          },
        },
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
        queryCache.invalidateQueries(['get-withdrawal-repayment-summary']),
        queryCache.invalidateQueries(['get-preclosure-amount', withdrawalId]),
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

  if (!isExperimentEnabled) {
    return null;
  }

  if (isTodayDueDate) {
    return null;
  }

  if (isLoading) {
    return <Spinner accessibilityLabel="pre-closure details" />;
  }

  if (Number(total) <= 0 && isEmpty(error_response)) {
    return null;
  }

  const showTooltip = !isEmpty(error_response);

  const getTooltipContent = (): string => {
    if (isEmpty(total)) {
      return TOOLTIP_DISABLE_MESSAGES.DISABLE_FOR_TWO_DAYS;
    } else {
      return TOOLTIP_DISABLE_MESSAGES.PROCESSING_WITHDRAWAL;
    }
  };

  return (
    <Box>
      <Text size="small">Want to close all dues for this withdrawal?</Text>
      {showTooltip ? (
        <Tooltip content={getTooltipContent()} placement="top">
          <Button
            marginY="spacing.4"
            size="small"
            variant="tertiary"
            onClick={handleRepaymentClick}
            isDisabled={showTooltip}
          >
            Pre-close withdrawal
          </Button>
        </Tooltip>
      ) : (
        <Button
          marginY="spacing.4"
          size="small"
          variant="tertiary"
          onClick={handleRepaymentClick}
          isLoading={loading}
        >
          Pre-close withdrawal
        </Button>
      )}
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = {
  showNotification,
  fetchWithdrawals,
  fetchWithdrawalDetails,
  fetchFunctionalWithdrawalConfigByMerchantID,
};

export default connect(mapStateToProps, mapDispatchToProps)(PreClosure);
