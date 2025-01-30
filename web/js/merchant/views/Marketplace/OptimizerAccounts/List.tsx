import React, { useState, useEffect } from 'react';
import { Box, Heading, Link, ExternalLinkIcon, Button } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import { FilterOptimizerAccounts } from 'merchant/views/Marketplace/OptimizerAccounts/components/FilterOptimizerAccounts';
import { OptimizerAccountsTable } from 'merchant/views/Marketplace/OptimizerAccounts/components/OptimizerAccountsTable';
import { OptimizerLinkAccount } from 'merchant/views/Marketplace/OptimizerAccounts/components/OptimizerLinkAccount';
import { fetchOptimizerAccounts } from './service';
import { trackOptimizerAccountEvents } from './track';
import { OPTIMIZER_ACCOUNTS_DEFAULT_COUNT } from './constants';
import type { OptimizerAccount } from './types';

const OptimizerAccountsList = (props): JSX.Element => {
  const { fetchTerminalProviders, providers, showNotification } = props;
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [accountId, setAccountId] = useState<string>('');
  const [count, setCount] = useState<string>(OPTIMIZER_ACCOUNTS_DEFAULT_COUNT);
  const [isRefreshing, setIsRefreshing] = useState<boolean>(false);
  const [optimizerAccounts, setOptimizerAccounts] = useState<OptimizerAccount[]>([]);
  const [isLinkAccountModalOpen, setIsLinkAccountModalOpen] = useState<boolean>(false);

  useEffect(() => {
    setIsLoading(true);
    fetchOptimizerAccounts(accountId, count)
      .then((res) => {
        if (res.success && res.data?.hasOwnProperty('data')) {
          setOptimizerAccounts((res.data as { data: OptimizerAccount[] })?.data);
        }
      })
      .finally(() => {
        setIsLoading(false);
      });
    fetchTerminalProviders();
  }, []);

  const documentationClick = () => {
    trackOptimizerAccountEvents({
      objectName: 'Documentation',
      actionName: 'clicked',
    });
  };

  const handleLinkAccount = () => {
    trackOptimizerAccountEvents({
      objectName: 'Link account button',
      actionName: 'clicked',
    });
    setIsLinkAccountModalOpen(true);
  };

  const handleSearch = () => {
    setIsRefreshing(true);
    fetchOptimizerAccounts(accountId, count)
      .then((res) => {
        if (res.success && res.data?.hasOwnProperty('data')) {
          setOptimizerAccounts((res.data as { data: OptimizerAccount[] })?.data);
        }
      })
      .catch(({ errors }) => {
        if (errors) {
          showNotification({
            type: 'error',
            message: errors[0],
            closeTimeout: 3000,
          });
        }
      })
      .finally(() => {
        setIsRefreshing(false);
      });
  };

  const accountLinkedSuccess = (accountName: string) => {
    showNotification({
      type: 'success',
      message: `${accountName} linked successfully.`,
      closeTimeout: 3000,
    });
    handleSearch();
  };

  return (
    <Box paddingTop="spacing.6" paddingRight="spacing.8" paddingLeft="spacing.6">
      <OptimizerLinkAccount
        isOpen={isLinkAccountModalOpen}
        closeModal={() => setIsLinkAccountModalOpen(false)}
        optimizerAccounts={optimizerAccounts}
        providers={providers}
        accountLinkedSuccess={accountLinkedSuccess}
      />
      <Box display="flex" flexDirection="column" gap="spacing.7">
        <Box
          display="flex"
          gap="spacing.7"
          justifyContent="space-between"
          alignItems="center"
          flex="1"
        >
          <Heading size="medium">Optimizer accounts</Heading>
          <Box display="flex" gap="spacing.5" alignItems="center">
            <Link
              href="https://razorpay.com/docs/payments/optimizer"
              target="_blank"
              rel="noopener noreferer"
              icon={ExternalLinkIcon}
              iconPosition="right"
              onClick={documentationClick}
            >
              Documentation
            </Link>
            <Button variant="primary" onClick={handleLinkAccount}>
              + Link Account
            </Button>
          </Box>
        </Box>
        <Box display="flex" flexDirection="column" gap="spacing.5">
          <FilterOptimizerAccounts
            accountId={accountId}
            setAccountId={setAccountId}
            count={count}
            setCount={setCount}
            handleSearch={handleSearch}
          />
          <OptimizerAccountsTable
            optimizerAccounts={optimizerAccounts}
            isLoading={isLoading}
            isRefreshing={isRefreshing}
          />
        </Box>
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => {
  return {
    providers: state.navigator.terminalProviders,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchTerminalProviders,
      showNotification,
    },
    dispatch,
  );
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(OptimizerAccountsList);
