import styled from 'styled-components';
import { StyledJourneyMetadata } from 'merchant/views/Transactions/v2/Payments/components/Timeline/styled';

export const StyledTimeline = styled.div`
  background: ${({ theme }) => `${theme.colors.surface.background.level2.lowContrast}`};
  flex: 1.2;
  width: 100%;
  @media screen and (min-width: 768px) {
    min-width: 340px;
  }
`;

export const TimelineHeader = styled.div`
  padding: 12px 24px;
  border-bottom: 1px solid ${({ theme }) => `${theme.colors.surface.border.normal.lowContrast}`};
`;

export const StyledTimelineRevamp = styled(StyledTimeline)`
  flex: 1;
  border: 1.5px solid rgba(120, 134, 155, 0.18);
  border-radius: ${({ theme }) => theme.spacing[2]}px;
  overflow-y: scroll;
`;

const handleBackground = (status, theme) => {
  switch (status) {
    case 'failed':
      return `background: ${theme.colors.feedback.background.negative.lowContrast}`;
    case 'processed':
      return `background: ${theme.colors.feedback.background.positive.lowContrast}`;
    default:
      return `background: ${theme.colors.feedback.background.neutral.lowContrast}`;
  }
};
export const IconBackground = styled.div<{ status: string }>`
  height: 20px;
  width: 20px;
  position: relative;
  border-radius: 50%;
  background: #79879c17;
  display: flex;
  justify-content: center;
  align-items: center;
  ${({ status, theme }) => handleBackground(status, theme)};
`;

export const StyledVerticalPath = styled.div`
  height: 44px;
  width: 0;
  border: 1px solid ${({ theme }) => `${theme.colors.surface.border.normal.lowContrast}`};
`;

export const StyledSettlementJourneyMetadata = styled(StyledJourneyMetadata)`
  width: 100%;
`;
