import { Heading, Text } from '@razorpay/blade/components';
import Shimmer from 'common/components/Shimmer';
import Divider from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Divider';
import React from 'react';
import { BreakupContent, BreakupHeader, BreakupItem, StyledBreakUp } from './styled';

const grossSettlementsPlaceholder = ['80px', '61px', '67px'];
const deductionsPlaceholder = ['80px', '61px', '67px', '61px'];

const BreakupShimmer = (): JSX.Element => {
  return (
    <StyledBreakUp style={{ height: '450px' }} data-testid="breakup-shimmer">
      <BreakupHeader>
        <Heading weight="semibold" size="small">
          Breakup
        </Heading>
      </BreakupHeader>
      <BreakupContent>
        {grossSettlementsPlaceholder.map(
          (each, index): JSX.Element => (
            <BreakupItem key={index}>
              <Shimmer height="20px" width="100px" variant="rounded" borderRadius="12px" />
              <Shimmer height="20px" width={each} variant="rounded" borderRadius="12px" />
            </BreakupItem>
          ),
        )}
        <Divider noMargin />
        {deductionsPlaceholder?.map(
          (each, index): JSX.Element => (
            <BreakupItem key={index}>
              <Shimmer height="20px" width="100px" variant="rounded" borderRadius="12px" />
              <Shimmer height="20px" width={each} variant="rounded" borderRadius="12px" />
            </BreakupItem>
          ),
        )}
        <Divider noMargin />
        <BreakupItem>
          <Text size="medium" weight="semibold">
            Net settlement
          </Text>
          <Shimmer height="20px" width="61px" variant="rounded" borderRadius="12px" />
        </BreakupItem>
      </BreakupContent>
    </StyledBreakUp>
  );
};

export const BreakupRevampShimmer = (): JSX.Element => {
  return (
    <StyledBreakUp style={{ height: '200px' }} data-testid="breakup-shimmer">
      <BreakupContent>
        {grossSettlementsPlaceholder.slice(1).map(
          (each, index): JSX.Element => (
            <BreakupItem key={index}>
              <Shimmer height="20px" width="100px" variant="rounded" borderRadius="12px" />
              <Shimmer height="20px" width={each} variant="rounded" borderRadius="12px" />
            </BreakupItem>
          ),
        )}
        <Divider noMargin />
        {deductionsPlaceholder.slice(1)?.map(
          (each, index): JSX.Element => (
            <BreakupItem key={index}>
              <Shimmer height="20px" width="100px" variant="rounded" borderRadius="12px" />
              <Shimmer height="20px" width={each} variant="rounded" borderRadius="12px" />
            </BreakupItem>
          ),
        )}
        <Divider noMargin />
        <BreakupItem>
          <Text size="medium" weight="semibold">
            Net settlement
          </Text>
          <Shimmer height="20px" width="61px" variant="rounded" borderRadius="12px" />
        </BreakupItem>
      </BreakupContent>
    </StyledBreakUp>
  );
};

export default BreakupShimmer;
