const Pager = ({ totalPages, current, onNext, onPrev }) => {
  return (
    <div className="paginate btn-group pull-right">
      <button
        type="button"
        className="btn btn-default btn-sm i"
        disabled={current === 1}
        onClick={onPrev}
      >
        <i className="i i-chevron-left" />
        <span>prev</span>
      </button>
      <button
        type="button"
        className="btn btn-default btn-sm i"
        disabled={current === totalPages}
        onClick={onNext}
      >
        <span>next</span>
        <i className="i i-chevron-right" />
      </button>
    </div>
  );
};

export default Pager;
