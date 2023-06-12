import React from 'react';
import {
  EmptyComponentContainer,
  Table as T,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
  TableWrapper,
} from './style';
import { Box, Pagination, Text } from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { ReportTablePropsType } from './types';

export const Table = <RowType, AdditionalInfoType = void>({
  template,
  rows = [],
  fixedHeaders = false,
  onPageChange = () => {},
  additionalInfo,
  centeredHeaders = [],
  onLoadingSkeletonTemplate,
  renderOnEmpty,
  loading,
  totalRows,
  currentPage,
  pageSize = 20,
}: ReportTablePropsType<RowType, AdditionalInfoType>): JSX.Element => {
  const { headers, cells } =
    loading && onLoadingSkeletonTemplate ? onLoadingSkeletonTemplate : template;
  const renderedRows =
    loading && onLoadingSkeletonTemplate ? ([1, 2, 3] as unknown as RowType[]) : rows;
  const { theme } = useTheme();
  return (
    <Box>
      <TableWrapper className="table-responsive" fixedHeaders={fixedHeaders} loading={loading}>
        <T className="table table-hover">
          <TableHead theme={theme} fixedHeaders={fixedHeaders}>
            <TableRow theme={theme}>
              {headers.map((header, index) => (
                <TableHeader
                  key={header}
                  theme={theme}
                  centered={centeredHeaders.includes(index)}
                  aria-label={header}
                >
                  <Text variant="body" type="subdued" weight="bold" contrast="low">
                    {header}
                  </Text>
                </TableHeader>
              ))}
            </TableRow>
          </TableHead>
          <TableBody>
            {renderedRows.map((data, index) => (
              <TableRow key={index} theme={theme} index={index}>
                {cells.map((cellData, headerIndex) => {
                  return (
                    <TableCell
                      theme={theme}
                      centered={centeredHeaders.includes(headerIndex)}
                      key={headerIndex}
                      style={cellData.style}
                    >
                      {cellData.render({ ...data }, index, theme, additionalInfo)}
                    </TableCell>
                  );
                })}
              </TableRow>
            ))}
          </TableBody>
        </T>
        {!loading && rows.length === 0 && renderOnEmpty ? (
          <EmptyComponentContainer>{renderOnEmpty()}</EmptyComponentContainer>
        ) : null}
      </TableWrapper>

      {Boolean(!loading && rows.length != 0) && (
        <div
          style={{
            paddingBottom: 15,
          }}
        >
          <Pagination
            totalCount={totalRows}
            pageSize={pageSize}
            currentPage={currentPage}
            onPageChange={onPageChange}
          />
        </div>
      )}
    </Box>
  );
};
