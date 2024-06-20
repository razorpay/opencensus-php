import React from 'react';
import { Text } from '@razorpay/blade/components';

const DotSeparator: React.FC = () => {
  return (
    <Text size="medium" weight="semibold" color="surface.text.gray.muted">
      •
    </Text>
  );
};

export default DotSeparator;
