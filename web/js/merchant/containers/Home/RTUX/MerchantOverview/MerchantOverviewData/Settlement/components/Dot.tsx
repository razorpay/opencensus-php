import React from 'react';
import { Text } from '@razorpay/blade/components';
import { useMobile } from 'common/hooks/useMobile';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';

const Dot = () => {
  const isMobile = useMobile(mobileBreakoints);
  return isMobile ? null : (
    <Text type="subdued" size="small" weight="bold">
      •
    </Text>
  );
};

export default Dot;
