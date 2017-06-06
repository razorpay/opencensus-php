export default ({ invoice, isSaving, onAddClick }) => {
  let status = invoice.status;
  let isNew = !invoice.id;
  let isPaid = status === 'paid';
  let isCancelled = status === 'cancelled';
  let isExpired = status === 'expired';
  let locked = isPaid || isExpired || isCancelled;

  if (Object.keys(invoice.notes || {}).length) {
    return (
      <div class="inv__info inv__addnote">
        <h4>Internal Notes</h4>
        <dl>
          {Object.keys(invoice.notes).map(key => (
            <div key={key}>
              <dt>{key}</dt>
              <dd>{invoice.notes[key]}</dd>
            </div>
          ))}
        </dl>
        {!(isNew || locked) &&
          <button
            type="button"
            class="btn btn-default btn-block btn-lg"
            onClick={onAddClick}
            disabled={isSaving}
          >
            <i class="icon icon-comment" />
            <span>Add Internal Note</span>
          </button>}
      </div>
    );
  } else if (!(isNew || locked)) {
    return (
      <div class="inv__cta">
        <button
          type="button"
          class="btn btn-default btn-block btn-lg"
          onClick={onAddClick}
          disabled={isSaving}
        >
          <i class="icon icon-comment" />
          <span>Add Internal Note</span>
        </button>
      </div>
    );
  } else {
    return null;
  }
};
