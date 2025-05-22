import React, { useEffect, useState } from 'react';
import { Box, PlusIcon, Button } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import { ModeT } from 'common/services/mode';
import Spinner from 'common/ui/Spinner';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import { CardWrapper } from 'merchant/views/GCMS/shared/styles.ts';
import { RESELLERS_STATUS } from 'merchant/views/GCMS/shared/constants';

import ResellersFilter from './ResellersFilters';
import { trackResellerDetailsPageClicked, trackResellersPageLoadSuccess } from './events';
import { LIST_FETCH_BATCH_SIZE } from './queries';
import ResellerTable from './ResellerTable';
import InviteResellers from 'merchant/views/GCMS/Resellers/InviteReseller';
import PageLayout from 'merchant/views/GCMS/shared/PageLayout';

const Resellers = ({ mode, merchantId }: { mode: ModeT; merchantId: string }) => {
  const [isOpen, setIsOpen] = useState(false);
  const [refetchQuery, setRefetchQuery] = useState(0);

  useEffect(() => {
    trackResellersPageLoadSuccess();
  }, []);

  function inviteResellers() {
    setIsOpen(true);
  }

  function renderTableData() {
    return (
      <ResellerTable
        mode={mode}
        merchantId={merchantId}
        refetchQuery={refetchQuery}
        showFilters={false}
      />
    );
  }

  return (
    <Wrapper>
      <PageLayout
        title="Resellers"
        subtitle="View reseller details and invite resellers to the dashboard."
        // leading={
        //   <Button
        //     icon={PlusIcon}
        //     variant="primary"
        //     onClick={inviteResellers}
        //     testID="program-creation-flow"
        //     size="small"
        //   >
        //     Invite Reseller
        //   </Button>
        // }
      >
        {!isOpen && <Box marginTop="spacing.4">{renderTableData()}</Box>}
      </PageLayout>
      {isOpen && (
        <InviteResellers
          closeModal={() => setIsOpen(false)}
          refetchResellers={() => setRefetchQuery(Date.now())}
          setRefetchQuery={setRefetchQuery}
        />
      )}
    </Wrapper>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchantId: state.session?.user?.current,
}))(Resellers);
