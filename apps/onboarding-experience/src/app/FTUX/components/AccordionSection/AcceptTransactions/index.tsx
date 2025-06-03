import React, { useState } from 'react';
import { Text, Link, Box } from '@razorpay/blade/components';
import { DASHBOARD_MODE } from '@libs/shared-types';
import { isMobileDevice } from '@libs/shared-utils';
import { PLATFORM_FEES_URL } from '@FTUX/constants/payments';
import SettlementsGuideModal from '@FTUX/modals/SettlementsGuideModal';

const AcceptTransactions = ({ mode }: { mode: DASHBOARD_MODE }) => {
  const [isSettlementsGuideModalOpen, setIsSettlementsGuideModalOpen] = useState(false);
  const textSize = isMobileDevice() ? 'small' : 'medium';

  return (
    <Box display="flex" flexDirection="column" gap="spacing.4">
      {mode === 'test' && (
        <Text size={textSize} weight="regular" color="surface.text.gray.subtle">
          A test transaction is just practice—it won't move money to your bank.
        </Text>
      )}
      <Text size={textSize} weight="regular" color="surface.text.gray.subtle">
        Once you do a live transaction, we'll deposit the collected payments in your bank account as
        per your{' '}
        <Link size={textSize} variant="button" onClick={() => setIsSettlementsGuideModalOpen(true)}>
          settlement cycle
        </Link>
      </Text>
      <Text size={textSize} weight="regular" color="surface.text.gray.subtle">
        Your final settlement amount will be deposited after adjusting for{' '}
        <Link size={textSize} href={PLATFORM_FEES_URL} target="_blank">
          platform fees and applicable charges
        </Link>
      </Text>
      {isSettlementsGuideModalOpen && (
        <SettlementsGuideModal onDismiss={() => setIsSettlementsGuideModalOpen(false)} />
      )}
    </Box>
  );
};

export default AcceptTransactions;
