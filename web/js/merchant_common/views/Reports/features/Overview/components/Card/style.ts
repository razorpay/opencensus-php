import styled from 'styled-components';
import { Icon } from 'merchant_common/views/Reports/components/styled';

export const CardWrapper = styled.div(
  ({ theme }) => `
  padding: ${theme.spacing[7]}px;
  background: #ffffff;
  border: ${theme.border.width.thin}px solid #e2e8ea;
  border-radius: ${theme.border.radius.medium}px;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
`,
);

export const CardLink = styled.span(
  ({ theme }) => `
  color: ${theme.colors.action.border.secondary.default};
  text-wrap: nowrap;
`,
);

export const Header = styled.div(
  ({ theme }) => `
  display: flex;
  align-items: center;
  padding-bottom: ${theme.spacing[4]}px;
  border-bottom:  ${theme.border.width.thin}px solid #e2e8ea;
`,
);

export const Footer = styled.div<{ count: number }>`
  display: flex;
  justify-content: ${(p) => (p?.count === 1 ? 'flex-end' : 'space-between')};
  align-items: center;
`;

export const TextWrapper = styled.div(
  ({ theme }) => `
  margin: ${theme.spacing[4]}px ${theme.spacing[0]}px;
`,
);

export const CardIcon = styled(Icon)(
  ({ theme }) => `
  margin-right: ${theme.spacing[4]}px;
`,
);
