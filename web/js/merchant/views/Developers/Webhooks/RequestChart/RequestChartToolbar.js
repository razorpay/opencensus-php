import React from 'react';
import moment from 'moment';
import { BtnGroup, Btn } from 'common/ui/BtnGroup/index';
import GenericTooltip from 'common/ui/Tooltip';
import RequestLegend from 'merchant/views/Developers/components/RequestChartLegend';

const legend = [
  { value: '5xx', title: '5xx', color: 'red' },
  { value: '4xx', title: '4xx', color: 'yellow' },
  { value: '3xx', title: '3xx', color: 'blue' },
  { value: '2xx', title: '2xx', color: 'green' },
  { value: 'all', title: 'All', color: 'black' },
];

export default function RequestChartToolbar(props) {
  const {
    selectedFilters,
    selectedAggregation,
    handleAggregateChange,
    filteredStatusCodeList,
    handleFilteredStatusCodeChange,
    isDataNotAvailable,
  } = props;
  const { duration, dateRange, eventType } = selectedFilters;
  return (
    <div className="request-chart-toolbar">
      <div className="flex">
        <div className="api-request">Webhook Requests</div>
        <div className="selected-filter-tag">
          <span>{dateRange}</span>
        </div>
        {eventType ? (
          <div className="selected-filter-tag">
            <span>{eventType}</span>
          </div>
        ) : null}
      </div>
      <div className="flex">
        <RequestLegend
          legend={legend}
          filteredStatusCodeList={filteredStatusCodeList}
          handleFilteredStatusCodeChange={handleFilteredStatusCodeChange}
          isDataNotAvailable={isDataNotAvailable}
        />
        {renderAggregationFilters({
          duration,
          selectedAggregation,
          handleAggregateChange,
          isDataNotAvailable,
        })}
      </div>
    </div>
  );
}

export const aggregations = [
  {
    value: 'minute',
    label: '15 Minutes',
    isEnabled: (startDate, endDate) => {
      return moment(endDate).diff(moment(startDate), 'hours') <= 24;
    },
    disabledText: 'Available for past 3 and 24 hours range',
  },
  {
    value: 'hour',
    label: 'Hourly',
    isEnabled: (startDate, endDate) => {
      return moment(endDate).diff(moment(startDate), 'hours') <= 24;
    },
    disabledText: 'Available for past 24 hours range',
  },
  {
    value: 'day',
    label: 'Daily',
    isEnabled: (startDate, endDate) => moment(endDate).diff(moment(startDate), 'hours') > 24,
    disabledText: 'Available for date range greater than 24 hours',
  },
];

function renderAggregationFilters(props) {
  const {
    selectedAggregation,
    handleAggregateChange,
    duration: { from, to },
    isDataNotAvailable,
  } = props;

  return (
    <BtnGroup value={selectedAggregation} onChange={handleAggregateChange}>
      {aggregations.map((aggregation) => {
        const isEnabled = aggregation.isEnabled(from, to);
        const shouldBeDisabled = isDataNotAvailable || !isEnabled;
        const key = aggregation.value;
        const buttonProps = {
          disabled: shouldBeDisabled,
          value: aggregation.value,
          key,
          className: 'btn-default',
          style: {
            height: '28px',
            fontSize: '12px',
            paddingInline: '0px',
            paddingBlock: '0px',
            border:
              !shouldBeDisabled && selectedAggregation.value === aggregation.value
                ? '1px solid #1583F1'
                : '1px solid #E4E5E6',
          },
        };

        return (
          <Btn key={key} {...buttonProps} className="aggregations-btn">
            <div className="aggregations">
              <span>{aggregation.label}</span>
              {!isEnabled && !isDataNotAvailable ? (
                <GenericTooltip align="top">{aggregation.disabledText}</GenericTooltip>
              ) : null}
            </div>
          </Btn>
        );
      })}
    </BtnGroup>
  );
}
