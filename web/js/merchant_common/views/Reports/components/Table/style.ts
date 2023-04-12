import styled from 'styled-components';
import { reportsTheme } from 'merchant_common/views/Reports/configs';
import { flexCentered } from 'merchant_common/views/Reports/components/styled';

export const TableCell = styled.td<{ centered: boolean }>(
  ({ centered, theme }) => `
  padding: ${theme.spacing[5]}px !important;
  text-align: left;
  border: none !important;
  ${centered && `text-align: center;`}
`,
);

export const TableRow = styled.tr<{ index?: number }>(({ theme, index }) => {
  return `
    background-color: ${
      typeof index === 'number' && index % 2 != 0
        ? reportsTheme(theme).TABLE_EVEN_BG_COLOR
        : '#ffffff'
    } !important;
    border-top: none !important;
    text-align: center;
  `;
});

export const TableHeader = styled.th<{ centered: boolean }>(
  ({ theme, centered }) => `
    align: center;
    background-color: ${reportsTheme(theme).TABLE_EVEN_BG_COLOR} !important; 
    border: none !important;
    padding: ${theme.spacing[4]}px ${theme.spacing[5]}px !important;
    ${centered && `text-align: center;`}
`,
);

export const TableBody = styled.tbody``;

export const TableHead = styled.thead<{ fixedHeaders: boolean }>(({ fixedHeaders }) => {
  const fixedHeadersConfig = `
  position: sticky;
  top: -1px;
  z-index: 1;
`;
  return `
   ${fixedHeaders && fixedHeadersConfig}
  `;
});

export const Table = styled.table(() => ``);

export const TableWrapper = styled.div<{ fixedHeaders: boolean; loading?: boolean }>(
  ({ fixedHeaders, loading }) => {
    const fixedHeadersConfig = `
    height: calc(100vh - 300px);
    z-index: 0;
    position: relative;
  `;
    return `
    min-height: calc(100vh - ${loading ? 195 : 260}px); 
    ${fixedHeaders && fixedHeadersConfig}
  `;
  },
);

export const EmptyComponentContainer = styled.div`
  height: calc(100vh - 260px);
  ${flexCentered}
`;
