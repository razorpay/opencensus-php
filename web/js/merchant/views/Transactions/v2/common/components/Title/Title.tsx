import React from 'react';
import { Text } from '@razorpay/blade/components';

const Title = ({ children }: { children: React.ReactNode }): JSX.Element => (
  <Text weight="semibold" color="surface.text.staticWhite.normal">
    {children}
  </Text>
);

export default Title;
