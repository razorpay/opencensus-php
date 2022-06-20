import React, { useEffect, useState } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { compose } from 'redux';
import DateRangePicker from 'common/ui/DateRangePicker';
import * as ApiListActions from 'merchant/reducers/developers/apiList';
import RequestLogs from './RequestLogs/RequestLogs';
import RequestChart from './RequestChart/RequestChart';
import ApiKeys from './ApiKeys';
import {
  trackApiLogsSearched,
  trackApiTabOpened,
  trackDateChange,
  trackDurationChange,
} from './events';

const PAST_3_HOURS = 'Past 3 Hours';
const PAST_24_HOURS = 'Past 24 Hours';
const PAST_3_DAYS = 'Past 3 Days';
const PAST_7_DAYS = 'Past 7 Days';

const dateRangePresets = [
  [PAST_3_HOURS, -3, 'hours'],
  [PAST_24_HOURS, -24, 'hours'],
  [PAST_3_DAYS, -3, 'days'],
  [PAST_7_DAYS, -7, 'days'],
];
const endDate = moment();
const startDate = endDate.clone().subtract(10800, 'seconds');

const Api = ({ items, fetchApiList }) => {
  const [dateRange, setDateRange] = useState();
  const [selectedFilters, setSelectedFilter] = useState({
    endPoint: items[0],
    dateRange: PAST_3_HOURS,
    duration: {
      from: startDate.valueOf(),
      to: endDate.valueOf(),
    },
  });

  useEffect(() => {
    fetchApiList({
      from: selectedFilters.duration.from,
      to: selectedFilters.duration.to,
    });
    trackApiTabOpened();
  }, []);

  const onDatesChange = (from, to) => {
    let fromTimeStamp, toTimeStamp;

    switch (dateRange) {
      case PAST_3_HOURS: {
        fromTimeStamp = moment().subtract(10800, 'seconds');
        toTimeStamp = moment();
        break;
      }
      case PAST_24_HOURS: {
        fromTimeStamp = moment().subtract(86400, 'seconds');
        toTimeStamp = moment();
        break;
      }
      default: {
        fromTimeStamp = from;
        toTimeStamp = to;
      }
    }

    setSelectedFilter((prevState) => ({
      ...prevState,
      duration: {
        from: fromTimeStamp.valueOf(),
        to: toTimeStamp.valueOf(),
      },
    }));

    fetchApiList({
      from: fromTimeStamp.valueOf(),
      to: toTimeStamp.valueOf(),
    });
    trackDateChange();
    trackApiLogsSearched();
  };

  const onSelectPreset = (preset) => {
    setDateRange(preset.name);
    setSelectedFilter((prevState) => ({
      ...prevState,
      dateRange: preset.name || PAST_3_HOURS,
    }));

    if (!preset.name.includes('Custom')) {
      trackDurationChange();
    }
  };

  return (
    <div className="developers-container">
      <ApiKeys />
      <div className="filters-container content-wrapper mb-20">
        <div className="form-group datepicker-group">
          <label>Duration</label>
          <DateRangePicker
            presets={dateRangePresets}
            onDatesChange={onDatesChange}
            onSelectPreset={onSelectPreset}
            callPresetChangeOnCustomOption
          />
        </div>
      </div>
      <RequestChart selectedFilters={selectedFilters} />
      <RequestLogs selectedFilters={selectedFilters} />
    </div>
  );
};

export default compose(
  connect(
    (state) => ({
      ...state.appList,
    }),
    {
      ...ApiListActions,
    },
  ),
)(Api);
