import React from 'react';
import { Text } from 'merchant_common/views/Reports/components';

// common wrapper for table text via blade
export const TableText = ({ children, ...otherProps }): JSX.Element => (
  <Text variant="body" type="normal" weight="regular" contrast="low" {...otherProps}>
    {children}
  </Text>
);
