import moment from 'moment';

export default function UpdateSubscriptionLinkReview(props) {
  const changes = changeData(props);

  let updateSubsStatusDesc = null;

  if (props.fields.schedule_change_at) {
    if (props.fields.schedule_change_at === 'now') {
      updateSubsStatusDesc = (
        <div>
          The changes will take into effect <b>immediately.</b>
        </div>
      );
    } else {
      updateSubsStatusDesc = (
        <div>
          The changes will be applied from the next billing cycle on{' '}
          {moment.unix(props.prevSubscription.charge_at).format('DD MMM, YYYY')}
        </div>
      );
    }
  }

  return (
    <div class="SubscriptionLinks--Update-review">
      {changes.map((e) => (
        <ChangeValue key={e.heading} {...e} />
      ))}
      {updateSubsStatusDesc}
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

  let currSelectedPlan = updatedPlan;
  let prevSelectedPlan = prevPlan;

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

  if (fields.total_count !== prevSubscription.total_count) {
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
    (fields.start_at && prevSubscription.start_at !== fields.start_at) ||
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

  if (prevSubscription.customer_notify !== fields.customer_notify) {
    changes.push({
      heading: 'Notify to Customer',
      changes: [
        {
          current: String(prevSubscription.customer_notify).toUpperCase(),
          change: String(fields.customer_notify).toUpperCase(),
        },
      ],
    });
  }

  if (prevSubscription.offer_id !== fields.offer_id) {
    changes.push({
      heading: 'Offer',
      changes: [
        {
          current: prevSubscription.offer_id || 'No Offer Applied',
          change: fields.offer_id || 'Offer Removed',
        },
      ],
    });
  }

  return changes;
}

const ChangeValue = ({ heading, changes }) => (
  <div class="changed-values">
    <span class="big-dot-separator" />
    <div>
      <strong>{heading}</strong>
      {changes.map((change) => (
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

function getTimeInFormat(date) {
  return moment.unix(date).format('DD MMM, YYYY, hh:mm a');
}
