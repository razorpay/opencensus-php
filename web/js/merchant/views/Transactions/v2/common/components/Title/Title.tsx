import React from 'react';
import { Text } from '@razorpay/blade/components';

const Title = ({ children }: { children: React.ReactNode }): JSX.Element => (
  <Text weight="bold" color="surface.text.normal.highContrast">
    {children}
  </Text>
);

export default Title;
