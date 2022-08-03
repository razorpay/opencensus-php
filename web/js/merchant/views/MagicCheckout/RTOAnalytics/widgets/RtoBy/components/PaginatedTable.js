import { useState } from 'react';
import Pager from 'merchant/views/MagicCheckout/RTOAnalytics/common/Pager';

const LIMIT = 6;

const PaginatedTable = ({ rows, columns }) => {
  const [currPage, setCurrPage] = useState(1);
  const totalPages = Math.ceil(rows?.length / LIMIT);
  const currentRows = rows?.slice(LIMIT * (currPage - 1), LIMIT * currPage);

  const onPrev = () => {
    setCurrPage((prevPageNumber) => prevPageNumber - 1);
  };

  const onNext = () => {
    setCurrPage((prevPageNumber) => prevPageNumber + 1);
  };

  return (
    <>
      <table>
        <thead>
          <tr>
            {columns.map((column) => (
              <th key={column.title}>{column.title}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {currentRows?.map((rowData) => {
            return (
              <tr key={rowData.zipcode}>
                {columns.map((column) => (
                  <td key={column.title}>{column.value(rowData)}</td>
                ))}
              </tr>
            );
          })}
        </tbody>
      </table>
      {rows && <Pager current={currPage} totalPages={totalPages} onNext={onNext} onPrev={onPrev} />}
    </>
  );
};

export default PaginatedTable;
