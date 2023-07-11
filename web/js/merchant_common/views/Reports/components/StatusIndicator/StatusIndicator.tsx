import React from 'react';
import { Badge, Box, Indicator } from 'merchant_common/views/Reports/components';
import { StatusIndicatorPropsType } from './types';
import { IndicatorContainer } from './styled';

const StatusObj = {
  Scheduled: 'positive',
  Success: 'positive',
  Ongoing: 'information',
  Finished: 'neutral',
  Pending: 'information',
  Failed: 'negative',
  Timeout: 'negative',
  Paused: 'notice',
};

const IN_PROCESS_STATES = ['Pending', 'Ongoing'];

export const StatusIndicator = ({
  children,
  indicator,
}: StatusIndicatorPropsType): JSX.Element | null => {
  return (
    <Box position="relative">
      {Boolean(indicator) ? (
        <IndicatorContainer isInProcess={IN_PROCESS_STATES.includes(children)}>
          <Indicator accessibilityLabel="Indicator" intent={StatusObj[children]} size="small" />
        </IndicatorContainer>
      ) : null}

      <Badge variant={StatusObj[children]} contrast="low" size="large">
        {children}
      </Badge>
    </Box>
  );
};
