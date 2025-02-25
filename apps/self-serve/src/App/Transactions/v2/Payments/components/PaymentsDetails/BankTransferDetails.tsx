// TODO: Fix the imports, currently out of scope
// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

import { Box, ChevronDownIcon, ChevronUpIcon, Text, Spinner } from '@razorpay/blade/components';

import { useStore } from '@federated/apps/shell/commonStore';

import { fetchBankTransfer } from 'apps/self-serve/src/App/Transactions/model';

import { StyledBankTransferCollapser } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/styled';

import {
  IBankTransferDetails,
  IBankTransferDetailsProps,
} from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/types';

const BankTransferDetails: React.FC<IBankTransferDetailsProps> = (props) => {
  const { paymentID } = props;

  const [isBankTransferDetailsCollapsed, setIsBankTransferDetailsCollapsed] = useState(false);
  const [bankTransferDetails, setBankTransferDetails] = useState<
    IBankTransferDetails | Record<string | never>
  >({});

  const showNotification = useStore((state) => state.showNotification);

  const [isLoading, setIsLoading] = useState(false);

  const viewHandler = () => {
    setIsBankTransferDetailsCollapsed((prevState) => !prevState);
  };

  useEffect(() => {
    if (paymentID) {
      setIsLoading(true);
      fetchBankTransfer(paymentID)
        .then((res) => {
          setBankTransferDetails((res?.data as IBankTransferDetails) || {});
        })
        .catch((error) => {
          showNotification({
            type: 'error',
            message: error?.errors?.[0] ? error.errors[0] : 'Failed to fetch bank transfer details',
          });
        })
        .finally(() => {
          setIsLoading(false);
        });
    }
  }, [paymentID]);

  const { virtual_account, payer_bank_account } =
    (bankTransferDetails as IBankTransferDetails) || {};
  const { id: vaID } = virtual_account || {};
  const { account_number, ifsc, name } = payer_bank_account || {};

  if (isLoading) {
    return <Spinner accessibilityLabel="bank-transfer-details-loader" />;
  }

  return (
    <Box display="flex" flexDirection="column" gap="12px">
      <StyledBankTransferCollapser onClick={viewHandler}>
        <Text
          size="medium"
          weight="semibold"
          color={
            isBankTransferDetailsCollapsed
              ? 'surface.text.staticBlack.normal'
              : 'surface.text.primary.normal'
          }
        >
          Bank Transfer
        </Text>
        {isBankTransferDetailsCollapsed ? (
          <ChevronUpIcon color="interactive.icon.staticBlack.normal" />
        ) : (
          <ChevronDownIcon color="interactive.icon.primary.normal" />
        )}
      </StyledBankTransferCollapser>
      {isBankTransferDetailsCollapsed ? (
        <Box display="flex" flexDirection="column" gap="8px" width="300px">
          <Box display="flex" justifyContent="space-between" alignItems="center">
            <Text weight="semibold">Virtual account id</Text>
            <Link to={`/virtualaccounts/${vaID || ''}`}>{vaID || '--'}</Link>
          </Box>
          <Box display="flex" justifyContent="space-between" alignItems="center">
            <Text weight="semibold">Payer name</Text>
            <Text wordBreak="break-word" textAlign="left">
              {name || '--'}
            </Text>
          </Box>
          <Box display="flex" justifyContent="space-between" alignItems="center">
            <Text weight="semibold">Payer a/c</Text>
            <Text wordBreak="break-word" textAlign="left">
              {account_number || '--'}
            </Text>
          </Box>
          <Box display="flex" justifyContent="space-between" alignItems="center">
            <Text weight="semibold">Payer IFSC</Text>
            <Text wordBreak="break-word" textAlign="left">
              {ifsc || '--'}
            </Text>
          </Box>
        </Box>
      ) : null}
    </Box>
  );
};

export default BankTransferDetails;
