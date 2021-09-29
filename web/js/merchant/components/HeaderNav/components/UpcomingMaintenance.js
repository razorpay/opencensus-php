import maintenance from '../../../../../icons/merchant/maintenance.svg';
import { showScheduledDowntime } from './utilities';

const UpcomingMaintenance = (props) => {
  return (
    <>
      <div class="upcoming-title">
        <img src={maintenance} class="maintenance-icon" /> Upcoming Maintenance
      </div>
      {props.paymentMethod === 'Cards' ? (
        'card' in props.scheduledDowntimes ? (
          <div>
            {props.scheduledDowntimes?.card.map((scheduledDowntime) =>
              showScheduledDowntime(scheduledDowntime),
            )}
          </div>
        ) : (
          <div class="no-maintenance">No Upcoming Maintenance</div>
        )
      ) : props.paymentMethod === 'UPI' ? (
        'upi' in props.scheduledDowntimes ? (
          <div>
            {props.scheduledDowntimes?.upi.map((scheduledDowntime) =>
              showScheduledDowntime(scheduledDowntime),
            )}
          </div>
        ) : (
          <div class="no-maintenance">No Upcoming Maintenance</div>
        )
      ) : 'netbanking' in props.scheduledDowntimes ? (
        props.scheduledDowntimes?.netbanking.map((scheduledDowntime) =>
          showScheduledDowntime(scheduledDowntime),
        )
      ) : (
        <div class="no-maintenance">No Upcoming Maintenance</div>
      )}
    </>
  );
};

export default UpcomingMaintenance;
