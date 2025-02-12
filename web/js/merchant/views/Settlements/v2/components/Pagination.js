import React from 'react';

const Pagination = ({ next, prev, listData, skip, count, showActualValues = false }) => {
  const totalRecords = listData?.length ?? 0;
  const endRange = Math.min(skip + count, totalRecords);
  const startRange = totalRecords >= count ? skip + 1 : 1;

  return (
    <div
      className="clearfix text-center"
      style={{
        margin: '20px',
      }}
    >
      <div className="btn-group pull-right">
        <button
          type="button"
          aria-label="previous"
          className="btn btn-default btn-sm i"
          disabled={skip === 0}
          onClick={prev}
          style={{
            marginRight: showActualValues ? '4px' : '',
          }}
        >
          <i className="i i-chevron-left" />
        </button>
        <button
          type="button"
          aria-label="next"
          className="btn btn-default btn-sm i"
          disabled={listData.length < count}
          onClick={next}
        >
          <i className="i i-chevron-right" />
        </button>
      </div>
      {showActualValues ? (
        <small className="text-muted">
          {totalRecords > 0 ? `Showing ${startRange} - ${endRange}` : `No records found`}
        </small>
      ) : (
        <small className="text-muted">
          Showing {skip + 1} - {skip + count}
        </small>
      )}
    </div>
  );
};

export default Pagination;
