import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledDotPositive = styled.div(({ theme }: { theme: Theme }) => ({
  width: theme.spacing[4],
  height: theme.spacing[4],
  borderRadius: '50%',
  backgroundColor: theme.colors.feedback.border.positive.highContrast,
}));

export const StyledDotBackgroundPositive = styled.div(({ theme }: { theme: Theme }) => ({
  width: theme.spacing[6],
  height: theme.spacing[6],
  borderRadius: '50%',
  backgroundColor: theme.colors.feedback.background.positive.lowContrast,
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  marginRight: theme.spacing[4],
}));

export const StyledDotNegative = styled(StyledDotPositive)(({ theme }: { theme: Theme }) => ({
  backgroundColor: theme.colors.feedback.background.negative.highContrast,
}));

export const StyledDotBackgroundNegative = styled(StyledDotBackgroundPositive)(
  ({ theme }: { theme: Theme }) => ({
    backgroundColor: theme.colors.feedback.background.negative.lowContrast,
  }),
);
export const StyledDotInfo = styled(StyledDotPositive)(({ theme }: { theme: Theme }) => ({
  backgroundColor: theme.colors.feedback.background.information.highContrast,
}));

export const StyledDotBackgroundInfo = styled(StyledDotBackgroundPositive)(
  ({ theme }: { theme: Theme }) => ({
    backgroundColor: theme.colors.feedback.background.information.lowContrast,
  }),
);
export const StyledDotNotice = styled(StyledDotPositive)(({ theme }: { theme: Theme }) => ({
  backgroundColor: theme.colors.feedback.background.notice.highContrast,
}));

export const StyledDotBackgroundNotice = styled(StyledDotBackgroundPositive)(
  ({ theme }: { theme: Theme }) => ({
    backgroundColor: theme.colors.feedback.background.notice.lowContrast,
  }),
);
