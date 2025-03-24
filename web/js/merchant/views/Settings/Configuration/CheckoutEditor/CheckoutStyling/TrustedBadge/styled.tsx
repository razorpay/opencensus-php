import styled from 'styled-components';

export const TrustedIconWrapper = styled.div(
  ({ theme }) => `
    display: flex;
    padding: ${theme.spacing[1]}px  ${theme.spacing[3]}px ${theme.spacing[1]}px ${theme.spacing[3]}px;
    justify-content: center;
    align-items: center;
    gap: 8px;
    border-radius: 12px;
    border: 1px solid ${theme.colors.interactive.border.neutral.faded};
    background: rgba(108, 132, 157, 0.12);
    margin-top: ${theme.spacing[4]}px
`,
);
