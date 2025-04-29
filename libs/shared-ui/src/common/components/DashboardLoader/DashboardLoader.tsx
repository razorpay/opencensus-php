import React from 'react';
import { Box, Spinner, type SpinnerProps } from '@razorpay/blade/components';

type DashboardLoaderProps = {
  label?: string;
  labelPosition?: SpinnerProps['labelPosition'];
  accessibilityLabel?: string;
  loaderType?: 'wrt-product' | 'wrt-viewport';
  fullWidth?: boolean;
};

export const DashboardLoader: React.FC<DashboardLoaderProps> = ({
  label = 'Loading...',
  labelPosition = 'bottom',
  accessibilityLabel = 'Loading...',
  loaderType = Boolean(window?.ONE_DASHBOARD) ? 'wrt-product' : 'wrt-viewport',
  fullWidth = false,
}) => {
  const positionStyles =
    loaderType === 'wrt-viewport'
      ? {
          position: 'fixed',
          top: 'spacing.0',
          right: 'spacing.0',
          bottom: 'spacing.0',
          left: 'spacing.0',
          overflow: 'hidden',
        }
      : {
          position: 'absolute',
          width: `${fullWidth ? '100%' : 'calc(100% - 264px)'}`,
          height: '100%',
          right: '0',
        };

  return (
    // @ts-ignore
    <Box
      display="flex"
      alignItems="center"
      justifyContent="center"
      zIndex="10000000"
      {...positionStyles}
    >
      <Spinner
        labelPosition={labelPosition}
        label={label}
        color="primary"
        size="large"
        accessibilityLabel={accessibilityLabel}
      />
    </Box>
  );
};
