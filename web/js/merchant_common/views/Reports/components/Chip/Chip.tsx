import React from 'react';
import { Badge, Indicator } from 'merchant_common/views/Reports/components';
import { ChipPropsType } from './types';
import { ChipContainer, IndicatorContainer } from './styled';

const CHIP_STATUS_CONFIG = {
  Scheduled: 'positive',
  Success: 'positive',
  Ongoing: 'notice',
  Finished: 'information',
  Pending: 'information',
  Failed: 'negative',
  Timeout: 'negative',
  Paused: 'neutral',
};

const IN_PROCESS_STATES = ['Pending', 'Ongoing'];

export const Chip = ({ children, indicator }: ChipPropsType): JSX.Element | null => {
  return (
    <ChipContainer>
      {Boolean(indicator) && (
        <IndicatorContainer isInProcess={IN_PROCESS_STATES.includes(children)}>
          <Indicator
            accessibilityLabel="Indicator"
            intent={CHIP_STATUS_CONFIG[children]}
            size="small"
          />
        </IndicatorContainer>
      )}

      <Badge variant={CHIP_STATUS_CONFIG[children]} contrast="low" size="large">
        {children}
      </Badge>
    </ChipContainer>
  );
};
