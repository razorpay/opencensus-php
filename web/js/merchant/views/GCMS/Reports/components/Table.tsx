import React, { useState } from 'react';
import {
  Table,
  Box,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableRow,
  TableCell,
  Text,
  TableBody,
} from '@razorpay/blade/components';

import Spinner from 'common/ui/Spinner';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';
import { useTheme } from 'merchant_common/views/Reports/hooks';

import { ReportTablePropsType } from '../../../../../merchant_common/views/Reports/components/Table/types';
export const ReportsTable = <RowType, AdditionalInfoType = void>({
  template,
  rows = [],
  additionalInfo,
  onLoadingSkeletonTemplate,
  loading,
  totalRows,
  pageSize = 20,
}: ReportTablePropsType<RowType, AdditionalInfoType>): JSX.Element => {
  const { headers, cells } =
    loading && onLoadingSkeletonTemplate ? onLoadingSkeletonTemplate : template;
  const renderedRows =
    loading && onLoadingSkeletonTemplate ? ([1, 2, 3] as unknown as RowType[]) : rows;
  const { theme } = useTheme();
  const tableData: any = {
    nodes: rows,
  };
  const [skip, setSkip] = useState(0);
  const handleNext = () => {
    setSkip(skip + pageSize);
  };

  const handlePrev = () => {
    setSkip(skip - pageSize);
  };

  return (
    // eslint-disable-next-line react/jsx-no-useless-fragment
    <>
      {loading ? (
        <div className="page-spinner-container">
          <Spinner center={undefined} />
        </div>
      ) : Array.isArray(tableData.nodes) && tableData.nodes.length > 0 ? (
        <>
          <Table data={tableData} showStripedRows={true}>
            {(reportItems) => (
              <>
                <TableHeader>
                  <TableHeaderRow>
                    {headers.map((header) => (
                      <TableHeaderCell key={header}>{header}</TableHeaderCell>
                    ))}
                  </TableHeaderRow>
                </TableHeader>
                <TableBody>
                  {reportItems.map((report: any, index) => (
                    <TableRow key={index} item={report}>
                      {cells.map((cellData, headerIndex) => {
                        return (
                          <TableCell key={headerIndex}>
                            {cellData.render({ ...report }, index, theme, additionalInfo)}
                          </TableCell>
                        );
                      })}
                    </TableRow>
                  ))}
                </TableBody>
              </>
            )}
          </Table>
          <Box>
            <Box position="absolute" paddingLeft="spacing.5" paddingTop="spacing.1">
              <Text size="small" color="surface.text.gray.muted">{`Total ${
                totalRows || 0
              } Reports`}</Text>
            </Box>
            <Pagination
              next={handleNext}
              prev={handlePrev}
              listData={renderedRows || []}
              skip={skip}
              count={pageSize}
            />
          </Box>
        </>
      ) : (
        <Box display="flex" alignItems="center" justifyContent="center">
          <EmptyListWithTableRow
            colSpan={8}
            description={
              <React.Fragment>
                <div>No Reports Found !!</div>
                <div>Download reports for your business just in one click.</div>
              </React.Fragment>
            }
          />
        </Box>
      )}
    </>
  );
};
