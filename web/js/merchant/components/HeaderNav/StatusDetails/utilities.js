export const getTimeinTwelveHourFormat = (dateObj) => {
  let hours = dateObj.getHours();
  let meridian = 'am';
  if (hours > 12) {
    hours = hours - 12;
    meridian = 'pm';
  }
  let minutes = dateObj.getMinutes();
  if (String(minutes).length === 1) {
    minutes = `0${minutes}`;
  }
  const time = `${hours}:${minutes} ${meridian}`;
  return time;
};

export const showWarningText = () => {
  return (
    <div class="status-details-warning">
      <span class="status-warning-asterix">{`* `}</span>We only detect downtime fluctuations for the
      instruments which have sufficient payment volume
    </div>
  );
};
