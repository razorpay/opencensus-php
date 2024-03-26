import styled from 'styled-components';

export const AlertIconWrapper = styled.div<{ variant: 'error' | 'warning' }>(
  ({ theme, variant }) => ({
    display: 'flex',
    padding: theme.spacing[4],
    backgroundColor:
      variant === 'error'
        ? theme.colors.feedback.background.negative.lowContrast
        : theme.colors.feedback.background.notice.lowContrast,
    borderRadius: theme.border.radius.round,
  }),
);
