import Amount from 'rzp/ui/Amount';

export default function UpdateSubscriptionLinkReview(props) {
  const { changes, summary } = changeData(props);
  return (
    <div class="SubscriptionLinks--Update-review">
      {changes.map(e => <ChangeValue {...e} />)}
      <Summary data={summary} />
    </div>
  );
}

const ChangeValue = ({ heading, changes }) => (
  <div class="changed-values">
    <span class="big-dot-separator" />
    <div>
      <strong>{heading}</strong>
      {changes.map(e => (
        <div class="current-change" key={e.current}>
          {e.current}
          <b>
            <i class="i i-arrow-forward" />
            {e.change}
          </b>
        </div>
      ))}
    </div>
  </div>
);

const Summary = ({ data }) => {
  return (
    <div class="summary">
      <strong>Summary</strong>
      <ul>{data}</ul>
    </div>
  );
};

export function changeData({
  fields,
  previousSubscription,
  plans,
  updatedPlan,
  prevPlan,
  internals,
}) {
  const currSelectedPlan = updatedPlan
      ? updatedPlan
      : plans.find(({ id }) => id === fields.plan_id),
    prevSelectedPlan = prevPlan
      ? prevPlan
      : plans.find(({ id }) => id === previousSubscription.plan_id),
    refund =
      currSelectedPlan.item.amount * fields.quantity -
      prevSelectedPlan.item.amount * previousSubscription.quantity;

  const changes = [];

  if (prevSelectedPlan.item.name !== currSelectedPlan.item.name) {
    changes.push({
      heading: 'Plan Change',
      changes: [
        {
          current: prevSelectedPlan.item.name,
          change: currSelectedPlan.item.name,
        },
      ],
    });
  }

  if (previousSubscription.quantity !== fields.quantity) {
    changes.push({
      heading: 'Quantity',
      changes: [
        {
          current: previousSubscription.quantity,
          change: fields.quantity,
        },
      ],
    });
  }

  if (previousSubscription.total_count !== fields.total_count) {
    changes.push({
      heading: 'Count (No of cycles)',
      changes: [
        {
          current: previousSubscription.total_count,
          change: fields.total_count,
        },
      ],
    });
  }

  if (
    previousSubscription.start_at !== fields.start_at ||
    (previousSubscription.start_at && internals._startsImmediately)
  ) {
    changes.push({
      heading: 'Start Date',
      changes: [
        {
          current: getTimeInFormat(previousSubscription.start_at),
          change: getTimeInFormat(fields.start_at),
        },
      ],
    });
  }

  const summary = [
    <li>
      <Amount
        value={prevSelectedPlan.item.amount}
        currency={prevSelectedPlan.item.currency}
      />
      {previousSubscription.quantity > 1
        ? ` charged every ${previousSubscription.quantity} monthly`
        : ' changed for month'}
      <b>
        <i class="i i-arrow-forward" />
        <Amount
          value={currSelectedPlan.item.amount}
          currency={currSelectedPlan.item.currency}
        />{' '}
        {fields.quantity > 1 ? (
          <>charged every {fields.quantity} monthly</>
        ) : (
          <>changed for month </>
        )}
      </b>
    </li>,
    !Number(fields.update_at_cycle_end) || internals._startsImmediately ? (
      <li>
        The changes will take into effect <b>immediately.</b>
      </li>
    ) : (
      <li>
        The changes will be applied from the next billing cycle i.e{' '}
        {moment.unix(previousSubscription.end_at).format('DD MMM, YYYY')}
      </li>
    ),
    refund ? (
      <li>
        {' '}
        Refund of{' '}
        <Amount value={refund} currency={currSelectedPlan.item.currency} /> has
        been initiated.{' '}
      </li>
    ) : (
      ''
    ),
  ];

  return {
    changes,
    summary,
  };
}

const getTimeInFormat = date => moment.unix(date).format('DD MMM, YYYY');
