import React, { useEffect, useState } from 'react';
import { Action, bindActionCreators, Dispatch } from 'redux';
import { connect } from 'react-redux';

import { Box, Text, Spinner, Link } from '@razorpay/blade/components';

import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchUPITransfer } from 'merchant/views/Transactions/model';

import { titleCase } from 'common/utils/rzp-utils';

import UpiIcon from 'assets/transactions/upi.svg';

import { IUPITransferDetails, IUPITransferDetailsProps } from './types';
import { useNavigate } from 'react-router-dom';

const UPITransferDetails: React.FC<IUPITransferDetailsProps> = (props) => {
  const { paymentID, showNotification, upi, vpa } = props;

  const [upiTransferDetails, setUpiTransferDetails] = useState<
    IUPITransferDetails | Record<string, never>
  >({});
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    if (paymentID) {
      setIsLoading(true);
      fetchUPITransfer(paymentID)
        .then((res) => {
          setUpiTransferDetails(res.data);
        })
        .catch((error) => {
          showNotification({
            type: 'error',
            message: error?.errors?.[0] ? error.errors[0] : 'Failed to fetch upi transfer details',
          });
        })
        .finally(() => {
          setIsLoading(false);
        });
    }
  }, [paymentID]);

  const navigate = useNavigate();

  if (isLoading) {
    return <Spinner accessibilityLabel="upi-transfer-details-loader" />;
  }

  const { virtual_account, virtual_account_id } = upiTransferDetails || {};
  return (
    <Box display="flex" flexDirection="column" gap="spacing.3" width="300px">
      {virtual_account?.description ? (
        <Text wordBreak="break-word">{virtual_account.description}</Text>
      ) : null}
      {virtual_account_id ? (
        <Box display="flex" gap="spacing.3" alignItems="center">
          <Text>Virtual account id:</Text>
          <Link
            variant="button"
            onClick={() => {
              navigate(`/virtualaccounts/${virtual_account_id}`);
            }}
          >
            {virtual_account_id}
          </Link>
        </Box>
      ) : null}
      <Box display="flex" gap="spacing.3" alignItems="center">
        <Text>UPI:</Text>
        <Text as="span">
          (<img src={UpiIcon} alt="upi-icon" /> {vpa || '--'})
        </Text>
      </Box>
      <Box display="flex" gap="spacing.3" alignItems="center">
        <Text>Payer Account Type:</Text>
        <Text as="span" wordBreak="break-word">
          {titleCase(upi?.payer_account_type) || '--'}
        </Text>
      </Box>
    </Box>
  );
};

const mapDispatchToProps = (dispatch: Dispatch<Action>) =>
  bindActionCreators(
    {
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(UPITransferDetails);
