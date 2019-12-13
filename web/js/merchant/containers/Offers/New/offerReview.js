const progressionList = progression => {
  return (
    <ul class="Workflow-list">
      {progression.map((x, i) => (
        <li className="Workflow-list--item" key={i}>
          {x}
        </li>
      ))}
    </ul>
  );
};

const getDualColumnTable = (key, value, columnRatio = 0.25) => {
  return (
    <div
      className={'dual-column-table'}
      style={{ gridTemplateColumns: `${columnRatio}fr ${1 - columnRatio}fr` }}
    >
      {
        <span>
          {key}
          {key && ':'}
        </span>
      }
      <span>{value}</span>
    </div>
  );
};

export default ({ terms, discount_type }) => {
  return (
    <div class="Subscription--New-review">
      <div class="Payments">
        {progressionList([
          <div>
            <p>
              <strong>Description:</strong>
            </p>
            {/* make an intutive language for this */}
            {getDualColumnTable('Display Text', '10% off on all HDFC cards')}
            {getDualColumnTable('Offer Terms', terms)}
          </div>,
          <div>
            <p>
              <strong>Discount Type:</strong>
            </p>
            {/* make an intutive language for this */}
            {getDualColumnTable(
              discount_type + ' Discount',
              'Flat discount of 100 on a minimum purchase of 3000'
            )}
          </div>,
        ])}
      </div>
    </div>
  );
};
