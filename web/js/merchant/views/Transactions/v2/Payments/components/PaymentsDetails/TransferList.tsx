import React from 'react';
import { Box, Link, Spinner, Text } from '@razorpay/blade/components';

import { useNavigate } from 'react-router-dom';
import { ITransferList } from './types';

const TransferList = ({ transfers }: ITransferList): JSX.Element => {
  const navigate = useNavigate();

  if (transfers.loading) {
    return <Spinner accessibilityLabel="loading transfers" />;
  }

  if (!transfers.items.length) {
    return (
      <Text type="normal" variant="body" size="medium" weight="regular" contrast="low">
        --
      </Text>
    );
  }

  return (
    <Box display="flex" flexDirection="column" gap="spacing.2">
      {transfers.items.map(({ id }) => (
        <Link key={id} onClick={() => navigate(`/route/transfers/${id}`)}>
          {id}
        </Link>
      ))}
    </Box>
  );
};

export default TransferList;
