import React from 'react';
import { TableRow, TableCell, Skeleton } from '@razorpay/blade/components';

const SkeletonRows = ({ rows, columns }) => {
  return (
    <>
      {Array.from({ length: rows }).map((_, rowIndex) => (
        <TableRow key={rowIndex} item={{ id: (rowIndex + 1).toString() }}>
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

export default SkeletonRows;
