import moment from 'moment';
import { useQuery } from 'react-query';
import React, { useContext } from 'react';
import { NavLink, withRouter } from 'react-router-dom';

import Spinner from 'common/ui/Spinner';
import Definition from 'common/ui/Definition';
import { Alert, Badge, Box } from '@razorpay/blade/components';
import AccountBalanceContainer from 'merchant/views/Wallet/AccountDetail/containers/AccountBalanceContainer';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import { toTitleCase } from '@razorpay/blade/utils';

import { fetchAccountById } from 'merchant/views/Wallet/queries';
import { SessionContext } from 'merchant/views/Wallet/context';
import { STATUS_BADGE_PROPS } from 'merchant/views/Wallet/Accounts/constants';

import type { Account } from 'merchant/views/Wallet/types';
import { walletPaths } from 'merchant/views/Wallet/constants';

interface AccountDetailProps {
  match: {
    params: {
      id: string;
    };
  };
}

export const AccountDetail = ({ match }: AccountDetailProps): JSX.Element => {
  const { mode } = useContext(SessionContext);
  const accountId = match.params.id;

  const { data, isLoading, isError } = useQuery<Account>({
    queryKey: ['wallet:accounts', mode, accountId],
    queryFn: () => fetchAccountById({ id: accountId, mode }),
  });

  return (
    <div className="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div className="page-spinner-container">
          <Spinner center="center" />
        </div>
      ) : (
        <div className="panel panel-default SliderPanel">
          <Box display="flex" alignItems="center">
            Account Id: <b> &nbsp; {data?.account_id || accountId}</b>
          </Box>
          {isError && (
            <Alert
              testID="error-message"
              isFullWidth
              intent="negative"
              description="Something went wrong"
            />
          )}

          <div className="AccountDetailSlider SliderPanel__Body">
            <div className="panel-body">
              <EntityDetailRow label="Account Holder Name">
                <Definition>{data?.account_holder_name || ''}</Definition>
              </EntityDetailRow>

              <EntityDetailRow label="Email">
                <Definition>{data?.email || 'N/A'}</Definition>
              </EntityDetailRow>

              <EntityDetailRow label="Phone">
                <Definition>{data?.contact || 'N/A'}</Definition>
              </EntityDetailRow>

              {data?.status && (
                <EntityDetailRow label="Status">
                  <Badge {...STATUS_BADGE_PROPS[data.status]} fontWeight="bold">
                    {toTitleCase(data.status)}
                  </Badge>
                </EntityDetailRow>
              )}

              <EntityDetailRow label="Full KYC">
                <Badge
                  variant={data?.full_kyc ? 'positive' : 'notice'}
                  fontWeight="bold"
                  contrast="high"
                >
                  {data?.full_kyc ? 'Completed' : 'Not Completed'}
                </Badge>
              </EntityDetailRow>

              <EntityDetailRow label="Creation Date">
                <Definition>{moment(data?.created_at, 'X').format('ll')}</Definition>
              </EntityDetailRow>

              <EntityDetailRow label="Program Name">
                <Definition>{data?.program || 'N/A'}</Definition>
              </EntityDetailRow>

              <EntityDetailRow label="Payments">
                <NavLink to={`${walletPaths.payments}?accountId=${accountId}`}>
                  View Payments
                </NavLink>
              </EntityDetailRow>

              <EntityDetailRow label="Loads">
                <NavLink to={`${walletPaths.loads}?accountId=${accountId}`}>View Loads</NavLink>
              </EntityDetailRow>
            </div>
            <AccountBalanceContainer account_id={accountId} mode={mode} />
          </div>
        </div>
      )}
    </div>
  );
};

export default withRouter(AccountDetail);
