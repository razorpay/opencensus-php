import React from 'react';
import { Skeleton } from '@razorpay/blade/components';

import { TableRow, TableCell } from '../../components/styled';

const SkeletonTable = ({ rows, columns }) => {
  return (
    <>
      {Array.from({ length: rows }).map((_, rowIndex) => (
        <TableRow key={rowIndex} data-testid="skeleton-table-row">
          {Array.from({ length: columns }).map((_, columnIndex) => (
            <TableCell key={columnIndex}>
              <Skeleton width="85px" height="20px" />
            </TableCell>
          ))}
        </TableRow>
      ))}
    </>
  );
};

export default SkeletonTable;
