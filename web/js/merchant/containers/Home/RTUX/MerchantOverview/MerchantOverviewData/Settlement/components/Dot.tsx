import React from 'react';
import { Text } from '@razorpay/blade/components';
import { useMobile } from 'common/hooks/useMobile';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';

const Dot = () => {
  const isMobile = useMobile(mobileBreakoints);
  return isMobile ? null : (
    <Text size="small" weight="semibold" color="surface.text.gray.muted">
      •
    </Text>
  );
};

export default Dot;
