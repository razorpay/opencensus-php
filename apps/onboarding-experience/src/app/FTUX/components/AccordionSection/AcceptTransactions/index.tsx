import React, { useState } from 'react';
import { Text, Link } from '@razorpay/blade/components';
import { DASHBOARD_MODE } from '@libs/shared-types';
import { PRICING_URL } from '@FTUX/constants/accordion';
import SettlementsGuideModal from '@FTUX/modals/SettlementsGuideModal';

const AcceptTransactions = ({ mode }: { mode: DASHBOARD_MODE }) => {
  const [isSettlementsGuideModalOpen, setIsSettlementsGuideModalOpen] = useState(false);

  return (
    <>
      {mode === 'test' && (
        <>
          <Text size="small" color="surface.text.gray.subtle">
            A test transaction is just practice—it won't move money to your bank.
          </Text>
          <Text size="small" color="surface.text.gray.subtle">
            After a live transaction, payments will be deposited as per your{' '}
            <Link size="small" onClick={() => setIsSettlementsGuideModalOpen(true)}>
              settlement cycle
            </Link>
          </Text>
        </>
      )}
      <Text size="small" color="surface.text.gray.subtle">
        Your final settlement amount will be deposited after adjusting for{' '}
        <Link size="small" href={PRICING_URL} target="_blank">
          platform fees and applicable charges
        </Link>
      </Text>
      {isSettlementsGuideModalOpen && (
        <SettlementsGuideModal onDismiss={() => setIsSettlementsGuideModalOpen(false)} />
      )}
    </>
  );
};

export default AcceptTransactions;
