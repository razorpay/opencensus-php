import React, { useState, useEffect } from 'react';
import { Box, Badge, Text, Link, Spinner } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import { showNotification } from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';
import { ChangeAccountDetails } from 'merchant/views/Marketplace/OptimizerAccounts/components/ChangeAccountDetails';
import { fetchOptimizerAccounts } from './service';
import { ACCOUNT_STATUS_MAP } from './constants';

import { OptimizerAccount } from './types';

const OptimizerAccountDetails = (props): JSX.Element => {
  const { id, showNotification } = props;
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [accountDetails, setAccountDetails] = useState<OptimizerAccount | null>(null);

  useEffect(() => {
    fetchOptimizerAccounts(id)
      .then((res) => {
        if (res.success) {
          setAccountDetails((res.data as { data: OptimizerAccount[] })?.data?.[0]);
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
        setIsLoading(false);
      });
  }, []);

  const updateDetails = (providerId: string, gatewayAccountId: string) => {
    const updatedDetails = { ...accountDetails } as OptimizerAccount;
    updatedDetails.accounts_map = updatedDetails?.accounts_map?.map((account) => {
      if (account?.provider_id === providerId) {
        account.gateway_account_id = gatewayAccountId;
      }
      return account;
    });
    setAccountDetails(updatedDetails);
  };

  const handleChange = (providerId: string, gatewayAccountId: string, providerName: string) => {
    const { openModal, closeModal } = props;
    openModal({
      size: 'xlarge',
      component: (
        <ChangeAccountDetails
          closeModal={closeModal}
          accountId={id}
          providerId={providerId}
          providerName={providerName}
          gatewayAccountId={gatewayAccountId}
          showNotification={showNotification}
          updateDetails={updateDetails}
        />
      ),
    });
  };

  return (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.7"
      marginTop="spacing.2"
      padding="spacing.5"
      height="100vh"
      backgroundColor="surface.background.gray.intense"
    >
      <Box
        display="flex"
        flexDirection="row"
        borderBottomColor="surface.border.gray.subtle"
        borderBottomWidth="thin"
        paddingBottom="spacing.5"
        gap="spacing.2"
      >
        <Text size="large">Account ID:</Text>
        <Text size="large" weight="semibold">
          {id}
        </Text>
      </Box>
      <Box display="flex" flexDirection="column" gap="spacing.6">
        <Box display="flex" flexDirection="row" gap="spacing.2">
          <Box width="35%">
            <Text color="surface.text.gray.muted">Name</Text>
          </Box>
          {isLoading ? (
            <Spinner accessibilityLabel="name-loader" />
          ) : (
            <Text>{accountDetails?.account_name}</Text>
          )}
        </Box>
        <Box display="flex" flexDirection="row" gap="spacing.2">
          <Box width="35%">
            <Text color="surface.text.gray.muted">Account Status</Text>
          </Box>
          {isLoading ? (
            <Spinner accessibilityLabel="status-loader" />
          ) : accountDetails?.account_status ? (
            <Badge color="positive">
              {ACCOUNT_STATUS_MAP[accountDetails?.account_status as string]}
            </Badge>
          ) : (
            ''
          )}
        </Box>
        <Box display="flex" flexDirection="row" gap="spacing.2">
          <Box width="35%">
            <Text color="surface.text.gray.muted">Gateway Linked Account ID</Text>
          </Box>
          {isLoading ? (
            <Spinner accessibilityLabel="gateway-id-loader" />
          ) : (
            <Box width="60%" display="flex" flexDirection="column" gap="spacing.4">
              {accountDetails?.accounts_map?.map((account, index) => (
                <Box key={index}>
                  <Text color="surface.text.gray.normal">{account.provider_name} Account ID</Text>
                  <Box display="flex" flexDirection="row" gap="spacing.3" marginTop="spacing.2">
                    <Text color="surface.text.gray.muted">{account.gateway_account_id}</Text>
                    <Link
                      variant="button"
                      onClick={() =>
                        handleChange(
                          account.provider_id,
                          account.gateway_account_id,
                          account.provider_name,
                        )
                      }
                    >
                      Change
                    </Link>
                  </Box>
                </Box>
              ))}
            </Box>
          )}
        </Box>
      </Box>
    </Box>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification,
      ...ModalActions,
    },
    dispatch,
  );
};

export default compose(connect(null, mapDispatchToProps))(OptimizerAccountDetails);
