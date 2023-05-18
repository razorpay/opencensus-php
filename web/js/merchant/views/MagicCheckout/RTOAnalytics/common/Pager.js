const Pager = ({ totalPages, current, onNext, onPrev, prevLabel = 'prev', nextLabel = 'next' }) => {
  return (
    <div className="paginate btn-group pull-right">
      <button
        type="button"
        className="btn btn-default btn-sm i"
        disabled={current === 1}
        onClick={onPrev}
      >
        <i className="i i-chevron-left" />
        <span>{prevLabel}</span>
      </button>
      <button
        type="button"
        className="btn btn-default btn-sm i"
        disabled={current === totalPages}
        onClick={onNext}
      >
        <span>{nextLabel}</span>
        <i className="i i-chevron-right" />
      </button>
    </div>
  );
};

export default Pager;
