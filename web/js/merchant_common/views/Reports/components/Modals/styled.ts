import styled from 'styled-components';
import { ScrollableContainer } from 'merchant_common/views/Reports/components/styled';
import { reportsTheme } from 'merchant_common/views/Reports/configs';

export const ReportModalWrapper = styled.div<{ scrollable: boolean }>`
  width: 750px;
  background: #ffffff;
  padding: 60px;
  ${({ scrollable = true }) => !scrollable && 'overflow-y: unset;'}
  ${({ theme }) => `
  height: 84vh;
  max-height: 860px;
  @media (max-width: ${theme.breakpoints.m}px), (max-height: ${theme.breakpoints.m}px) {
    overflow-y: auto;
    padding: 30px;
  }
  @media (max-width: ${theme.breakpoints.m}px){
    height: 100vh;
    max-height: 100vh;
    width: 100%;
  }
  `}
`;

export const ReportCloseButton = styled.div`
  position: absolute;
  right: 15px;
  top: 15px;
`;

export const ReportModalHeader = styled.div(({ theme }) => {
  const { FIELD_BORDER_WIDTH, FIELD_BORDER_DEFAULT_COLOR } = reportsTheme(theme);
  return `
    padding-bottom: ${theme.spacing[5]}px;
    border-bottom: ${FIELD_BORDER_WIDTH} solid ${FIELD_BORDER_DEFAULT_COLOR};
  `;
});

export const ModalFooter = styled.div(
  ({ theme }) => `
    display: flex;
    justify-content: right;
    align-items: center;
    margin-top: 10px;
    padding-bottom: 65px;
    @media (max-width: ${theme.breakpoints.m}px), (max-height: ${theme.breakpoints.m}px) {
      padding-bottom: 0;
    }
`,
);

export const ScrollableModalContent = styled(ScrollableContainer)(
  ({ theme }) => `
  height: calc(100% - 21px);
  @media (max-width: ${theme.breakpoints.m}px), (max-height: ${theme.breakpoints.m}px) {
    overflow-y: visible;
    height: auto;
  }
`,
);
