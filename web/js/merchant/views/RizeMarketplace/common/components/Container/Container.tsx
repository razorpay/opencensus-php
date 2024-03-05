import React, { forwardRef } from 'react';
import { Box, BoxProps, BoxRefType, useTheme } from '@razorpay/blade/components';

const Container = forwardRef<BoxRefType, BoxProps>((props, ref): JSX.Element => {
  const {
    theme: { breakpoints },
  } = useTheme();

  return (
    <Box
      marginX="auto"
      paddingX="spacing.7"
      maxWidth={{
        base: '100%',
        m: `${breakpoints.m}px`,
        l: `${breakpoints.l}px`,
        xl: `${breakpoints.xl}px`,
      }}
      ref={ref}
      {...props}
    />
  );
});

export default Container;
