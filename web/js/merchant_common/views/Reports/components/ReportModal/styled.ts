import styled from 'styled-components';
import { ScrollableContainer } from 'merchant_common/views/Reports/components/styled';
import { reportsTheme } from 'merchant_common/views/Reports/configs';

export const ReportModalWrapper = styled.div<{
  scrollable?: boolean;
  width?: number;
}>`
  ${({ width }) => (width ? `min-width: ${width}px;` : 'max-width: 750px;')}
  background: white;
  padding: ${({ width }) => (width && width > 750 ? 30 : 60)}px;
  ${({ scrollable = true }) => !scrollable && 'overflow-y: unset !important;'}
  ${({ theme }) => `
  height: 84vh;
  max-height: 860px;

  @media (max-width: ${theme.breakpoints.xl}px) {
    min-width: 100%;
  }

  @media (max-width: ${theme.breakpoints.m}px), (max-height: ${theme.breakpoints.m}px) {
    overflow-y: auto !important;
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

export const ScrollableModalContent = styled(ScrollableContainer)<{ heightOffset?: number }>(
  ({ theme, heightOffset = 21 }) => `
  height: calc(100% - ${heightOffset}px);
  @media (max-width: ${theme.breakpoints.m}px), (max-height: ${theme.breakpoints.m}px) {
    overflow-y: visible;
    height: auto;
  }
`,
);

export const AlignWithModalScroll = styled.div``;
