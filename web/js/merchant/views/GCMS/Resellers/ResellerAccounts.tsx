import React from 'react';
import { Box, Heading } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { useParams } from 'react-router-dom';

import { ModeT } from 'common/services/mode';
import Spinner from 'common/ui/Spinner';

import VirtualAccountDetails from './VirtualAccountDetails';
import { fetchResellerBalance } from './queries';

interface ResellerAccountsProps {
  mode: ModeT;
  merchantId: string;
}

const ResellerAccounts = ({ mode, merchantId }: ResellerAccountsProps) => {
  const { resellerId } = useParams<{ resellerId: string }>();

  const { isLoading, data: { virtual_account } = {} } = useQuery({
    queryKey: ['reseller:balance'],
    queryFn: () => fetchResellerBalance({ mode, merchantId, resellerId }),
  });

  return (
    <div>
      <div className="content">
        {isLoading ? (
          <div className="page-spinner-container">
            <Spinner center={undefined} />
          </div>
        ) : (
          <>
            <Box paddingTop={'spacing.5'} paddingLeft={'spacing.6'}>
              <Heading color="surface.text.gray.subtle" size="large">
                Account
              </Heading>
            </Box>
            <VirtualAccountDetails
              accountNumber={virtual_account?.bank_account_number}
              ifsc={virtual_account?.ifsc}
              beneficiaryName={virtual_account?.name}
            />
          </>
        )}
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
  merchantId: state.session?.user?.current,
});

export default connect(mapStateToProps)(ResellerAccounts);
