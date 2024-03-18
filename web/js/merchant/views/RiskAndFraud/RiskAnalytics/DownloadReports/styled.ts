import styled from 'styled-components';

export const DownloadIconWrapper = styled.span(
  () => `
  & svg {
    width: 84px;
    height: 84px;
  }
`,
);

export const ReportModalWrapper = styled.div<{
  scrollable?: boolean;
  width?: number;
  height?: number | 'auto';
}>`
  ${({ width }) => (width ? `min-width: ${width}px;` : 'max-width: 750px;')}
  background: white;
  padding: ${({ width }) => (width && width > 750 ? '30px' : '60px')};
  ${({ scrollable = true }) => !scrollable && 'overflow-y: unset !important;'}
  ${({ theme, height }) => `
  height: ${typeof height === 'number' ? `${height}px` : height === 'auto' ? 'auto' : '84vh'};
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
