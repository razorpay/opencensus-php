export default ({ invoice, isSaving, onAddClick }) => {
  let status = invoice.status;
  let isNew = !invoice.id;
  let isPaid = status === 'paid';
  let isCancelled = status === 'cancelled';
  let isExpired = status === 'expired';
  let locked = isPaid || isExpired || isCancelled;

  if (Object.keys(invoice.notes || {}).length) {
    return (
      <div className="inv__info inv__addnote">
        <h4>Internal Notes</h4>
        <dl>
          {Object.keys(invoice.notes).map(key => (
            <div key={key}>
              <dt>{key}</dt>
              <dd>{invoice.notes[key]}</dd>
            </div>
          ))}
        </dl>
        {!(isNew || locked) && (
          <button
            type="button"
            className="btn btn-default btn-block btn-lg"
            onClick={onAddClick}
            disabled={isSaving}
          >
            <i className="i i-comment" />
            <span>Add Internal Note</span>
          </button>
        )}
      </div>
    );
  } else if (!(isNew || locked)) {
    return (
      <div className="inv__cta">
        <button
          type="button"
          className="btn btn-default btn-block btn-lg"
          onClick={onAddClick}
          disabled={isSaving}
        >
          <i className="i i-comment" />
          <span>Add Internal Note</span>
        </button>
      </div>
    );
  } else {
    return null;
  }
};
