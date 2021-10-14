import ScheduledDowntime from './ScheduledDowntime';

const UpcomingMaintenance = (props) => {
  return (
    <>
      <div class="upcoming-title">
        <img
          src={`${window.cdnBaseUrl}/static/assets/downtimes/maintenance.svg`}
          class="maintenance-icon"
        />{' '}
        Upcoming Maintenance
      </div>
      {props.paymentMethod === 'Cards' ? (
        'card' in props.scheduledDowntimes ? (
          <div>
            {props.scheduledDowntimes?.card.map((scheduledDowntime) => (
              <ScheduledDowntime key={scheduledDowntime.id} scheduledDowntime={scheduledDowntime} />
            ))}
          </div>
        ) : (
          <div class="no-maintenance">No Upcoming Maintenance</div>
        )
      ) : props.paymentMethod === 'UPI' ? (
        'upi' in props.scheduledDowntimes ? (
          <div>
            {props.scheduledDowntimes?.upi.map((scheduledDowntime) => (
              <ScheduledDowntime key={scheduledDowntime.id} scheduledDowntime={scheduledDowntime} />
            ))}
          </div>
        ) : (
          <div class="no-maintenance">No Upcoming Maintenance</div>
        )
      ) : 'netbanking' in props.scheduledDowntimes ? (
        props.scheduledDowntimes?.netbanking.map((scheduledDowntime) => (
          <ScheduledDowntime key={scheduledDowntime.id} scheduledDowntime={scheduledDowntime} />
        ))
      ) : (
        <div class="no-maintenance">No Upcoming Maintenance</div>
      )}
    </>
  );
};

export default UpcomingMaintenance;
