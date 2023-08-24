import styled from 'styled-components';

import type { Theme } from '@razorpay/blade/components';

// Note: feedback.background.positive is not assignable as a background in blade's Box component
export const SuccessBackground = styled.div(
  ({ theme, height }: { theme: Theme; height: string }) => `
  position: absolute;
  height: ${height};
  width: 100%;
  top: 0px;
  left: 0px;
  pointer-events: none;
  background-color: ${theme.colors.feedback.background.positive.lowContrast};
`,
);
