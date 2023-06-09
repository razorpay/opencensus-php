import styled from 'styled-components';
import { ScrollableModalContent } from 'merchant_common/views/Reports/components/ReportModal/styled';

export const SchedulesWrapper = styled.div(
  () => `
  background: white;
`,
);

export const ControlPanel = styled.div(
  ({ theme }) => `
  display: flex;
  align-items:center;
  justify-content: space-between;
  flex-wrap: wrap;
  padding: ${theme.spacing[4]}px 0;
 
`,
);

export const RepeatOnWrapper = styled.div(
  ({ theme }) => `
  @media (max-width: ${theme.breakpoints.s}px) {
    display: none;
  }
`,
);

export const SchedulesTableIconControlContainer = styled.div`
  width: 100%;
  display: flex;
  justify-content: center;
  button:first-of-type {
    margin-right: 15px;
  }
  button:last-of-type {
    margin-left: 15px;
  }
`;

export const ActionButtonWrapper = styled.div(
  ({ theme }) => `
  width: 200px;
  @media (max-width: ${theme.breakpoints.s}px) {
    width: 100%;
    margin-bottom: ${theme.spacing[4]}px;
  }
`,
);

// TODO: remove style for label from here when
//       this (https://github.com/razorpay/blade/issues/1103)
//       is resolved and version is added.
export const DropdownWrapper = styled.span(
  ({ theme }) => `
    margin-left: ${theme.spacing[4]}px;
    min-width: 200px;
    label {
      display: none;
    }
    @media (max-width: ${theme.breakpoints.s}px) {
      width: 100%;
    margin-left: ${theme.spacing[0]}px;
    }
  `,
);

export const ControlPanelRight = styled.div`
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
`;

export const ModalScrollableTable = styled(ScrollableModalContent)<{
  initialWidth: number;
  heightOffset?: number;
}>`
  ${({ initialWidth }) => `
 overflow: auto; 
 @media (max-width: ${initialWidth}px) {
  width: calc(100vw - 60px);
 }
 `}
`;

export const LogStatusWrapper = styled.div`
  min-width: 68px;
`;
