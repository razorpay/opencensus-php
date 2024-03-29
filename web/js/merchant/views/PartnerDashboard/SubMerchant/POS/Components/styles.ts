import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledDotPositive = styled.div(({ theme }: { theme: Theme }) => ({
  width: theme.spacing[4],
  height: theme.spacing[4],
  borderRadius: '50%',
  backgroundColor: theme.colors.feedback.border.positive.intense,
}));

export const StyledDotBackgroundPositive = styled.div(({ theme }: { theme: Theme }) => ({
  width: theme.spacing[6],
  height: theme.spacing[6],
  borderRadius: '50%',
  backgroundColor: theme.colors.feedback.background.positive.subtle,
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  marginRight: theme.spacing[4],
}));

export const StyledDotNegative = styled(StyledDotPositive)(({ theme }: { theme: Theme }) => ({
  backgroundColor: theme.colors.feedback.background.negative.intense,
}));

export const StyledDotBackgroundNegative = styled(StyledDotBackgroundPositive)(
  ({ theme }: { theme: Theme }) => ({
    backgroundColor: theme.colors.feedback.background.negative.subtle,
  }),
);
export const StyledDotInfo = styled(StyledDotPositive)(({ theme }: { theme: Theme }) => ({
  backgroundColor: theme.colors.feedback.background.information.intense,
}));

export const StyledDotBackgroundInfo = styled(StyledDotBackgroundPositive)(
  ({ theme }: { theme: Theme }) => ({
    backgroundColor: theme.colors.feedback.background.information.subtle,
  }),
);
export const StyledDotNotice = styled(StyledDotPositive)(({ theme }: { theme: Theme }) => ({
  backgroundColor: theme.colors.feedback.background.notice.intense,
}));

export const StyledDotBackgroundNotice = styled(StyledDotBackgroundPositive)(
  ({ theme }: { theme: Theme }) => ({
    backgroundColor: theme.colors.feedback.background.notice.subtle,
  }),
);
