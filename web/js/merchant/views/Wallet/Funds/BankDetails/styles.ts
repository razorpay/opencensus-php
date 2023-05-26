import styled from 'styled-components';

const EscrowDetailsContainer = styled.div(
  ({ theme }) => `
  margin-left: auto;
  margin-right: auto;
  background: ${theme.colors.action.background.tertiary.focus};
  width: fit-content;
  box-shadow: ${theme.spacing[0]}px ${theme.spacing[2]}px ${theme.spacing[3]}px ${theme.colors.surface.border.normal.highContrast};
  border-radius: ${theme.spacing[1]};
  color: ${theme.colors.surface.background.level1.highContrast}
`,
);

const TitleContainer = styled.div(
  ({ theme }) => `
  padding: ${theme.spacing[4]}px ${theme.spacing[6]}px;
`,
);

export { EscrowDetailsContainer, TitleContainer };
