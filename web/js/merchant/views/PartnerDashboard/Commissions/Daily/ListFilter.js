import DateRangePicker from 'common/ui/DateRangePicker';
import React from "react";

export default props => {
  const onDatesChange = (from, to) => {
    props.onDatesChange(Number(from.format('X')), Number(to.format('X')));
  };

  return (
    <div className="list-filter-container">
      <div className="form-group list-filter-item">
        <DateRangePicker
          presets={getDateRangePresets()}
          onDatesChange={onDatesChange}
          defaultPreset={1}
        />
      </div>
    </div>
  );
};

function getDateRangePresets() {
  return [
    ['Past 7 Days', -7, 'days'],
    ['Past 30 Days', -30, 'days'],
    ['Past 90 Days', -90, 'days'],
  ];
}
