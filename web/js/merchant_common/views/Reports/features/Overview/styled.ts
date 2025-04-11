import styled from 'styled-components';
import { BaseDivType } from './types';

export const CardContainer = styled.div(
  ({ theme }) => `
  background: #ffffff;
  padding: ${theme.spacing[7]}px;
`,
);

export const CardsWrapper = styled.div(
  ({ theme }) => `
  display: grid;
  grid-template-rows: auto;
  grid-column-gap: ${theme.spacing[7]}px;
  grid-row-gap: ${theme.spacing[7]}px;
  justify-items: stretch;
  align-items: stretch;
  grid-template-columns: repeat(3, 1fr);
  @media only screen and (max-width: 1260px) {
    grid-template-columns: repeat(2, 1fr);
  }
  @media only screen and (max-width: 1000px) {
    grid-template-columns: 1fr;
  }
`,
);

export const AccessabilityToolbar = styled.div(
  ({ theme, isBillMeMerchant }) => `
  display: ${isBillMeMerchant ? 'none' : 'flex'};
  border-top-left-radius: ${theme.border.radius.large}px;
  border-top-right-radius: ${theme.border.radius.large}px;
  padding: ${theme.spacing[4]}px;
  background: #ffffff;
  align-items: center;
 `,
);

export const ReportTypeHeader = styled.div<BaseDivType>(
  ({ theme, index }) => `
  border-left: ${theme.spacing[1]}px solid #2A86F3;
  padding-left: ${theme.spacing[3]}px;
  margin-bottom: ${theme.spacing[4]}px;
  margin-top: ${index === 0 ? 0 : theme.spacing[8]}px;
`,
);

export const ReportTypeWrapper = styled.div<BaseDivType>(
  ({ theme, index }) => `
  margin-top:  ${index === 0 ? 0 : theme.spacing[5]}px;
`,
);

export const DropdownLabel = styled.div`
  padding: 6px 12px;
`;

export const DropdownWrapper = styled.div`
  min-width: 200px;
`;
