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

  const changes = [
    {
      heading: 'Plan Change',
      changes: [
        {
          current: prevSelectedPlan.item.name,
          change: currSelectedPlan.item.name,
        },
      ],
    },
    {
      heading: 'Quantity',
      changes: [
        {
          current: previousSubscription.quantity,
          change: fields.quantity,
        },
      ],
    },
    {
      heading: 'Count (No of cycles)',
      changes: [
        {
          current: previousSubscription.total_count,
          change: fields.total_count,
        },
      ],
    },
    {
      heading: 'Start Date',
      changes: [
        {
          current: moment
            .unix(previousSubscription.start_at)
            .format('DD MMM, YYYY'),
          change: moment.unix(fields.start_at).format('DD MMM, YYYY'),
        },
      ],
    },
  ];

  const summary = [
    <li>
      <Amount
        value={prevSelectedPlan.item.amount}
        currency={prevSelectedPlan.item.currency}
      />{' '}
      {previousSubscription.quantity > 1 ? (
        <>charged every {previousSubscription.quantity} monthly</>
      ) : (
        <>changed for month </>
      )}
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
    !Number(fields.update_at_cycle_end) ? (
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
