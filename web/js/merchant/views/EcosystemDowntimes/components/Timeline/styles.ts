import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

export const TimelineItemContainer = styled.div`
  display: flex;
  min-height: 45px;
  width: 100%;
`;

export const TimelineItemConnectorContainer = styled.div`
  display: flex;
  flex-direction: column;
  align-items: center;
  .icon-container {
    display: flex;
  }
`;

export const TimelineItemContentContainer = styled.div`
  margin-left: 0.5rem;
  width: 100%;
`;

export const TimelineItemConnector = styled.div(
  ({ theme }: { theme: Theme }) => `
  border-left: 1px solid ${theme.colors.interactive.background.neutral.default};
  height: 100%;
  opacity: 0.3;
`,
);
