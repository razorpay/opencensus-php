import { Theme } from '@razorpay/blade/components';
import styled, { css, FlattenSimpleInterpolation } from 'styled-components';

interface StyledTabProps extends React.HTMLAttributes<HTMLDivElement> {
  active: boolean;
}

export const flexCentered = css`
  display: flex;
  justify-content: center;
  align-items: center;
`;

export const FlexCentered = styled.div`
  ${flexCentered}
`;

export const TabsHeader = styled.header`
  display: flex;
  flex-wrap: wrap;
  background: #ffffff;
  width: 100%;
  border-bottom: 1px solid #e2e8ea;
`;

export const StyledTab = styled(FlexCentered)<StyledTabProps>(({ theme, active }) => {
  return `
    padding: ${theme.spacing[4]}px;
    margin-inline: ${theme.spacing[4]}px;
    border-bottom: 2px solid ${active ? theme.colors.brand.primary['500'] : 'transparent'};      
    color: ${active ? theme.colors.brand.primary['500'] : 'unset'} !important; 
  `;
});

export const SectionWrapper = styled.div<FlattenSimpleInterpolation>`
  display: flex;
  flex-direction: column;
  background-color: ${({ theme }) => theme.colors.surface.background.level2.lowContrast};
  margin-top: ${({ theme }) => theme.spacing[5]}px;
  padding: ${({ theme }) =>
    [theme.spacing[5], theme.spacing[7], theme.spacing[5], theme.spacing[7]].join(' ')};
`;

export const StyledButtonText = styled.button`
  color: ${({ theme }) => theme.colors.brand.primary['500']};
  background: transparent;
  border: none;
  outline: none;
  cursor: pointer;
`;

export const TooltipWrapper = styled.span`
  & > div {
    vertical-align: middle;
    cursor: pointer;
  }
`;

export const StyledTabButton = styled.button(
  ({ theme, isActive }: { theme: Theme; isActive: boolean }) => `
    display: flex;
    flex: 1;
    flex-direction: column;
    padding: ${theme.spacing[7]}px ${theme.spacing[6]}px ${theme.spacing[7]}px ${
    theme.spacing[6]
  }px;
    height: 140px;
    background-color: ${
      isActive
        ? theme.colors.surface.background.level2.lowContrast
        : theme.colors.surface.background.level3.lowContrast
    };
    border-color: ${theme.colors.surface.border.subtle.lowContrast};
    border-width: 0px;
    border-right-width: ${theme.border.width.thick}px;
    border-style: solid;
    cursor: pointer;
    pointer-events: all;
    &:hover {
      background-color: ${theme.colors.surface.background.level2.lowContrast};
    }
    &:first-child {
      border-top-left-radius: ${theme.border.radius.medium}px;
      border-bottom-left-radius: ${theme.border.radius.medium}px;
    }

    &:last-child {
      border-top-right-radius: ${theme.border.radius.medium}px;
      border-bottom-right-radius: ${theme.border.radius.medium}px;
      border-right-width: 0px
    }
  `,
);

const getOverlayStyles = css`
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: #ffffff80;
  display: flex;
  align-items: center;
  justify-content: center;
`;

export const StyledChartLoader = styled.div(
  ({ theme }: { theme: Theme }) => `
    ${getOverlayStyles}

    &::after {
      content: 'Loading...';
      font-size: ${theme.typography.fonts.size[200]}px;
      font-weight: ${theme.typography.fonts.weight.bold};
      color: ${theme.colors.surface.text.normal.lowContrast};
    }
  `,
);

export const StyledChartError = styled.div(
  ({ theme }: { theme: Theme }) => `
    ${getOverlayStyles}

    &::after {
      content: 'Fetching failed! Try later';
      font-size: ${theme.typography.fonts.size[200]}px;
      font-weight: ${theme.typography.fonts.weight.bold};
      color: ${theme.colors.surface.text.normal.lowContrast};
    }
  `,
);

export const DownloadIconWrapper = styled.span(
  () => `
  & svg {
    width: 84px;
    height: 84px;
  }
`,
);
