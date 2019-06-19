import Amount from 'rzp/ui/Amount';

export default function UpdateSubscriptionLinkReview(props) {
  const changes = changeData(props),
    summary = changeSummary(props);

  return (
    <div class="SubscriptionLinks--Update-review">
      {changes.map(e => <ChangeValue {...e} />)}
      {!!summary.length && <Summary data={summary} />}
    </div>
  );
}

export function changeData({
  plans,
  fields,
  prevPlan,
  internals = {},
  updatedPlan,
  prevSubscription,
}) {
  const changes = [];

  let currSelectedPlan = updatedPlan,
    prevSelectedPlan = prevPlan;

  if (!updatedPlan) {
    currSelectedPlan = plans.find(({ id }) => id === fields.plan_id);
  }

  if (!prevPlan) {
    prevSelectedPlan = plans.find(({ id }) => id === prevSubscription.plan_id);
  }

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

  if (prevSubscription.quantity !== fields.quantity) {
    changes.push({
      heading: 'Quantity',
      changes: [
        {
          current: prevSubscription.quantity,
          change: fields.quantity,
        },
      ],
    });
  }

  if (prevSubscription.total_count !== fields.total_count) {
    changes.push({
      heading: 'Count (No of cycles)',
      changes: [
        {
          current: prevSubscription.total_count,
          change: fields.total_count,
        },
      ],
    });
  }

  if (
    prevSubscription.start_at !== fields.start_at ||
    (prevSubscription.start_at && internals._startsImmediately)
  ) {
    changes.push({
      heading: 'Start Date',
      changes: [
        {
          current: prevSubscription.start_at
            ? getTimeInFormat(prevSubscription.start_at)
            : 'Immediately',
          change: getTimeInFormat(fields.start_at),
        },
      ],
    });
  }

  return changes;
}

export function changeSummary({
  plans,
  fields,
  prevPlan,
  updatedPlan,
  prevSubscription,
}) {
  const currSelectedPlan = updatedPlan
      ? updatedPlan
      : plans.find(({ id }) => id === fields.plan_id),
    prevSelectedPlan = prevPlan
      ? prevPlan
      : plans.find(({ id }) => id === prevSubscription.plan_id);

  const review = [];

  if (
    currSelectedPlan.item.amount !== prevSelectedPlan.item.amount ||
    currSelectedPlan.item.currency !== prevSelectedPlan.item.currency ||
    prevSubscription.quantity !== fields.quantity
  ) {
    review.push(
      <li>
        <Amount
          value={prevSelectedPlan.item.amount}
          currency={prevSelectedPlan.item.currency}
        />
        {prevSubscription.quantity > 1
          ? ` charged every ${prevSubscription.quantity} monthly`
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
      </li>
    );
  }

  if (fields.schedule_change_at) {
    if (fields.schedule_change_at === 'now') {
      review.push(
        <li>
          The changes will take into effect <b>immediately.</b>
        </li>
      );
    } else {
      review.push(
        <li>
          The changes will be applied from the next billing cycle i.e{' '}
          {moment.unix(prevSubscription.charge_at).format('DD MMM, YYYY')}
        </li>
      );
    }
  }

  return review;
}

const ChangeValue = ({ heading, changes }) => (
  <div class="changed-values">
    <span class="big-dot-separator" />
    <div>
      <strong>{heading}</strong>
      {changes.map(change => (
        <div class="current-change" key={change.current}>
          {change.current}
          <b>
            <i class="i i-arrow-forward" />
            {change.change}
          </b>
        </div>
      ))}
    </div>
  </div>
);

const Summary = ({ data }) => (
  <div class="summary">
    <strong>Summary</strong>
    <ul>{data}</ul>
  </div>
);

const getTimeInFormat = date => moment.unix(date).format('DD MMM, YYYY');
