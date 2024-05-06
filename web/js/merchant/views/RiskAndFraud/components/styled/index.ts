import { Theme } from '@razorpay/blade/components';
import styled, { css, FlattenSimpleInterpolation } from 'styled-components';

interface StyledTabProps extends React.HTMLAttributes<HTMLDivElement> {
  active: boolean;
}

interface TableRowProps {
  height?: number;
}

interface TableCellProps {
  align?: 'center' | 'left' | 'right';
}

interface GridItemProps {
  columns?: number;
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
  justify-content: space-between;
  background: #ffffff;
  width: 100%;
  border-bottom: 1px solid #e2e8ea;
`;

export const StyledTab = styled(FlexCentered)<StyledTabProps>(({ theme, active }) => {
  return `
    padding: ${theme.spacing[4]}px;
    margin-inline: ${theme.spacing[4]}px;
    border-bottom: 2px solid ${
      active ? theme.colors.surface.background.primary.intense : 'transparent'
    };      
    color: ${active ? theme.colors.surface.background.primary.intense : 'unset'} !important; 
  `;
});

export const SectionWrapper = styled.div<FlattenSimpleInterpolation>`
  display: flex;
  flex-direction: column;
  background-color: ${({ theme }) => theme.colors.surface.background.gray.intense};
  margin-top: ${({ theme }) => theme.spacing[5]}px;
  padding: ${({ theme }) =>
    [theme.spacing[5], theme.spacing[7], theme.spacing[5], theme.spacing[7]].join(' ')};
`;

export const StyledButtonText = styled.button`
  color: ${({ theme }) => theme.colors.surface.background.primary.intense};
  padding: ${({ theme }) => theme.spacing[2]}px;
  background: transparent;
  border: none;
  outline: none;
  cursor: pointer;
`;

export const TooltipWrapper = styled.span`
  z-index: 2;

  & > div {
    vertical-align: middle;
    cursor: pointer;
  }
`;

export const StyledTabCard = styled.div(
  ({ theme, isActive }: { theme: Theme; isActive: boolean }) => `
    position: relative;
    display: flex;
    flex: 1;
    flex-direction: column;
    padding: ${theme.spacing[7]}px ${theme.spacing[6]}px ${theme.spacing[7]}px ${
    theme.spacing[6]
  }px;
    height: 140px;
    background-color: ${
      isActive
        ? theme.colors.surface.background.gray.intense
        : theme.colors.surface.background.gray.moderate
    };
    border-color: ${
      isActive ? theme.colors.surface.border.primary.normal : theme.colors.surface.border.gray.muted
    };
    border-width: ${theme.border.width.thick}px;
    border-style: solid;
    cursor: pointer;
    pointer-events: all;
    border-radius: ${theme.border.radius.medium}px;
    transition: border-color 0.2s ease, transform 0.3s ease;
    &:hover {
      background-color: ${theme.colors.surface.background.gray.intense};
      transform: scale(1.02);
    }
  `,
);

export const StyledDateRangePicker = styled.div`
  display: flex;
  align-items: center;
  margin-right: ${({ theme }) => theme.spacing[7]}px;

  .risk-daterange-picker {
    > .daterange-container {
      margin-top: ${({ theme }) => theme.spacing[3]}px;
    }

    [role='loader'] {
      margin-left: ${({ theme }) => theme.spacing[3]}px;
    }
  }
`;

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
      font-weight: ${theme.typography.fonts.weight.medium};
      color: ${theme.colors.surface.text.gray.normal};
    }
  `,
);

export const StyledChartError = styled.div(
  ({ theme }: { theme: Theme }) => `
    ${getOverlayStyles}

    &::after {
      content: 'Fetching failed! Try later';
      font-size: ${theme.typography.fonts.size[200]}px;
      font-weight: ${theme.typography.fonts.weight.medium};
      color: ${theme.colors.surface.text.gray.normal};
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

export const Grid = styled.div`
  display: grid;
  grid-gap: ${({ theme }) => theme.spacing[4]}px;
  grid-template-columns: repeat(8, 1fr);
  background-color: ${({ theme }) => theme.colors.surface.background.gray.intense};

  @media (max-width: 768px) {
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
    column-gap: 0;
  }
`;

export const GridItem = styled.div<GridItemProps>`
  grid-column: span ${({ columns }) => columns || 1};

  @media (max-width: 768px) {
    grid-column: span 1fr; /* Override on smaller screens */
  }
`;

export const AnalyticsTableWrapper = styled.div`
  background-color: ${({ theme }) => theme.colors.surface.background.gray.intense};

  @media (max-width: 700px) {
    grid-row: 1;
    grid-column: 1;
  }
`;

export const DownloadReportsContainer = styled.div`
  background-color: #c0c0c0;
`;

export const BlocklistContainer = styled.div`
  background-color: #a0a0a0;

  @media (max-width: 700px) {
    grid-row: 3;
    grid-column: 1;
  }
`;

export const TableContainer = styled.div`
  width: 100%;
  background-color: ${({ theme }) => theme.colors.surface.background.gray.intense};
  padding: ${({ theme }) => `${theme.spacing[5]}px ${theme.spacing[8]}px`};
  border: 1px solid #e0e8f4;
  border-radius: ${({ theme }) => theme.border.radius.medium}px;
`;

export const Table = styled.table`
  width: 100%;
  border: none;
  border-collapse: collapse;
`;

export const TableHead = styled.thead`
  background-color: ${({ theme }) => theme.colors.surface.background.gray.intense};
  border-bottom: 1px solid #e0e8f4;
`;

export const TableBody = styled.tbody`
  background-color: ${({ theme }) => theme.colors.surface.background.gray.intense};
`;

export const TableRow = styled.tr<TableRowProps>`
  border-bottom: 1px solid #e0e8f4;
  height: ${({ height }) => (height ? `${height}px` : '45px')};
`;

const TableCellBase = css`
  padding: ${({ theme }) => `${theme.spacing[4]}px ${theme.spacing[3]}px`};
  color: #262d3a;

  &:first-child {
    padding-left: 0; /* No left padding for the first child */
  }
`;

export const TableHeaderCell = styled.th`
  ${TableCellBase}
  font-weight: ${({ theme }) => theme.typography.fonts.weight.regular};
  text-align: ${({ align = 'left' }) => align};
`;

export const TableCell = styled.td<TableCellProps>`
  ${TableCellBase}
  font-weight: ${({ theme }) => theme.typography.fonts.weight.medium};
  text-align: ${({ align = 'left' }) => align};
`;

export const StyledButtonIcon = styled.button`
  display: flex;
  background: transparent;
  border: none;
  outline: none;
  padding: 0;
  cursor: pointer;
  z-index: 2;
`;

export const InvisibleButton = styled.button`
  all: unset;
  cursor: pointer;
  appearance: none;
  position: static;
  border-width: 0;
  padding: 0;
  border-style: initial;
  border-color: initial;
  border-image: initial;

  &::before {
    content: '';
    cursor: inherit;
    display: block;
    position: absolute;
    top: 0px;
    left: 0px;
    z-index: 1;
    width: 100%;
    height: 100%;
  }
`;
