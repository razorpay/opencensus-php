import React from 'react';
import ScheduledDowntime from './ScheduledDowntime';

const UpcomingMaintenance = (props) => {
  const { paymentMethod, scheduledDowntimes } = props;
  return (
    <section>
      <p className="section-title">
        <img
          src={`${window.cdnBaseUrl}/static/assets/downtimes/maintenance.svg`}
          className="maintenance-icon"
          alt="Maintenance"
        />{' '}
        Upcoming Maintenance
      </p>
      {paymentMethod === 'Cards' ? (
        'card' in scheduledDowntimes ? (
          <div>
            {scheduledDowntimes?.card.map((scheduledDowntime) => (
              <ScheduledDowntime key={scheduledDowntime.id} scheduledDowntime={scheduledDowntime} />
            ))}
          </div>
        ) : (
          <p className="no-maintenance">No Upcoming Maintenance</p>
        )
      ) : paymentMethod === 'UPI' ? (
        'upi' in scheduledDowntimes ? (
          <div>
            {scheduledDowntimes?.upi.map((scheduledDowntime) => (
              <ScheduledDowntime key={scheduledDowntime.id} scheduledDowntime={scheduledDowntime} />
            ))}
          </div>
        ) : (
          <p className="no-maintenance">No Upcoming Maintenance</p>
        )
      ) : 'netbanking' in scheduledDowntimes ? (
        scheduledDowntimes?.netbanking.map((scheduledDowntime) => (
          <ScheduledDowntime key={scheduledDowntime.id} scheduledDowntime={scheduledDowntime} />
        ))
      ) : (
        <p className="no-maintenance">No Upcoming Maintenance</p>
      )}
    </section>
  );
};

export default UpcomingMaintenance;
