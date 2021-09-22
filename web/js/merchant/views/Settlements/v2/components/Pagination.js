import React from 'react';

const Pagination = ({ next, prev, listData, skip, count }) => {
  return (
    <div
      class="clearfix text-center"
      style={{
        margin: '20px',
      }}
    >
      <div class="btn-group pull-right">
        <button
          type="button"
          aria-label="previous"
          class="btn btn-default btn-sm i"
          disabled={skip === 0 && true}
          onClick={prev}
        >
          <i class="i i-chevron-left" />
        </button>
        <button
          type="button"
          aria-label="next"
          class="btn btn-default btn-sm i"
          disabled={listData.length < count && true}
          onClick={next}
        >
          <i class="i i-chevron-right" />
        </button>
      </div>
      <small class="text-muted">
        Showing {skip + 1} - {skip + count}
      </small>
    </div>
  );
};

export default Pagination;
