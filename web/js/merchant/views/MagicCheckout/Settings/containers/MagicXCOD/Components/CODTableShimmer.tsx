import React from 'react';
import {
  Skeleton,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  Spinner,
} from '@razorpay/blade/components';
import {
  SyncInProgressBadge,
  RelativePositionContainer,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/styled';

const ROWS = 4;
const COLS = 6;

export const CODTableShimmer = () => {
  return (
    <RelativePositionContainer data-testid="Table-Shimmer">
      <Table data={{ nodes: [] }}>
        {(_tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {Array.from({ length: COLS }).map((_, headerIndex) => (
                  <TableHeaderCell key={headerIndex}>
                    <Skeleton />
                  </TableHeaderCell>
                ))}
              </TableHeaderRow>
            </TableHeader>
            <TableBody>
              {Array.from({ length: ROWS }).map((_, rowIndex) => (
                <TableRow key={rowIndex} item={{ id: (rowIndex + 1).toString() }}>
                  {Array.from({ length: COLS }).map((_, colIndex) => (
                    <TableCell key={`${rowIndex}${colIndex}`}>
                      <Skeleton width="85px" height="20px" margin="spacing.2" />
                    </TableCell>
                  ))}
                </TableRow>
              ))}
            </TableBody>
          </>
        )}
      </Table>
      <SyncInProgressBadge>
        <Spinner accessibilityLabel="Fetching profiles from shopify" marginRight="spacing.2" />{' '}
        Please wait until sync is complete
      </SyncInProgressBadge>
    </RelativePositionContainer>
  );
};
