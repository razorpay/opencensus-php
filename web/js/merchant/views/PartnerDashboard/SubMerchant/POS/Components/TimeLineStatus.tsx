// TODO: replace styled component with blade indicator in V2

import React from 'react';

import { POS_ACTIVATION_STATUS } from 'merchant/views/POS/types';

import {
  StyledDotBackgroundInfo,
  StyledDotBackgroundNegative,
  StyledDotBackgroundNotice,
  StyledDotBackgroundPositive,
  StyledDotInfo,
  StyledDotNegative,
  StyledDotNotice,
  StyledDotPositive,
} from './styles';

export const TimelineStatus = ({ status }: { status: string }): JSX.Element => {
  switch (status) {
    case POS_ACTIVATION_STATUS.needs_clarification:
      return (
        <StyledDotBackgroundNotice>
          <StyledDotNotice />
        </StyledDotBackgroundNotice>
      );
    case POS_ACTIVATION_STATUS.rejected:
      return (
        <StyledDotBackgroundNegative>
          <StyledDotNegative />
        </StyledDotBackgroundNegative>
      );
    case POS_ACTIVATION_STATUS.under_review:
      return (
        <StyledDotBackgroundInfo>
          <StyledDotInfo />
        </StyledDotBackgroundInfo>
      );
    case POS_ACTIVATION_STATUS.activated:
    case POS_ACTIVATION_STATUS.submitted:
    default:
      return (
        <StyledDotBackgroundPositive>
          <StyledDotPositive />
        </StyledDotBackgroundPositive>
      );
  }
};
