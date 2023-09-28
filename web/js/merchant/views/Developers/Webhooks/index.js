import React, { useEffect, useState } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { compose } from 'redux';
import DateRangePicker from 'common/ui/DateRangePicker';
import { TypeAhead } from 'react-power-select';
import { NavLink } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import * as WebListActions from 'merchant/reducers/developers/webhookEventsList';
import RequestLogs from './RequestLogs/RequestLogs';
import RequestChart from './RequestChart/RequestChart';
import {
  trackWebhookLogsSearched,
  trackWebhookTabDateChange,
  trackWebhookTabDurationChange,
  trackWebhookTabEventTypeChange,
  trackWebhookTabOpened,
} from 'merchant/views/Developers/events';

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

const Webhooks = ({ loading, fetchWebhookEventList, items, match }) => {
  const [dateRange, setDateRange] = useState();
  const [selectedFilters, setSelectedFilter] = useState({
    dateRange: PAST_3_HOURS,
    duration: {
      from: startDate.valueOf(),
      to: endDate.valueOf(),
    },
    eventType: null,
  });

  useEffect(() => {
    trackWebhookTabOpened();

    fetchWebhookEventList({
      webhookId: match.params.id,
      from: selectedFilters.duration.from,
      to: selectedFilters.duration.to,
    });
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
      eventType: null,
      duration: {
        from: fromTimeStamp.valueOf(),
        to: toTimeStamp.valueOf(),
      },
    }));

    fetchWebhookEventList({
      webhookId: match.params.id,
      from: fromTimeStamp.valueOf(),
      to: toTimeStamp.valueOf(),
    });

    trackWebhookTabDateChange();
    trackWebhookLogsSearched();
  };

  const onSelectPreset = (preset) => {
    setDateRange(preset.name);
    setSelectedFilter((prevState) => ({
      ...prevState,
      dateRange: preset.name || PAST_3_HOURS,
    }));

    if (!preset.name.includes('Custom')) {
      trackWebhookTabDurationChange();
    }
  };

  const isOutsideRange = (day) => {
    return (
      day.isAfter(moment().endOf('day')) ||
      day.isBefore(moment().startOf('day').subtract(14, 'days').startOf('day'))
    );
  };

  const onEventTypeChange = (data) => {
    setSelectedFilter((prevState) => ({
      ...prevState,
      eventType: data.option?.route_name,
    }));

    trackWebhookTabEventTypeChange();
  };

  return (
    <div className="developers-container" style={{ padding: 20 }}>
      <div className="mb-20">
        <NavLink to="/developers/webhooks">
          <i class="i i-arrow-back" />
          Webhooks
        </NavLink>
      </div>
      <div className="filters-container content-wrapper mb-20">
        <div class="form-group datepicker-group">
          <label>Duration (can only be fetched for max. past 14 days)</label>
          <DateRangePicker
            presets={dateRangePresets}
            onDatesChange={onDatesChange}
            onSelectPreset={onSelectPreset}
            isOutsideRange={isOutsideRange}
            callPresetChangeOnCustomOption
          />
        </div>
        <div class="form-group list-filter-item">
          <label>Event Type</label>
          <TypeAhead
            options={items}
            disabled={loading}
            placeholder={`${loading ? 'Loading...' : 'payment.create'}`}
            showClear={true}
            selected={selectedFilters.eventType}
            optionLabelPath="route_name"
            onChange={onEventTypeChange}
            optionComponent={({ option }) => (
              <p style={{ paddingTop: 5, paddingBottom: 5 }}>{option.route_name}</p>
            )}
          />
        </div>
      </div>
      <RequestChart selectedFilters={selectedFilters} webhookId={match.params.id} />
      <RequestLogs selectedFilters={selectedFilters} webhookId={match.params.id} />
    </div>
  );
};

export default compose(
  withRouter,
  connect(
    (state) => ({
      ...state.webhookEventsList,
    }),
    {
      ...WebListActions,
    },
  ),
)(Webhooks);
