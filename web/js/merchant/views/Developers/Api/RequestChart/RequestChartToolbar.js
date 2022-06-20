import React from 'react';
import moment from 'moment';
import { BtnGroup, Btn } from 'common/ui/BtnGroup/index';
import GenericTooltip from 'common/ui/Tooltip';
import RequestLegend from '../../components/RequestChartLegend';

const legend = [
  { value: '5xx', title: '5xx', color: 'red' },
  { value: '4xx', title: '4xx', color: 'yellow' },
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
  const { duration, dateRange } = selectedFilters;

  return (
    <div className="request-chart-toolbar">
      <div className="flex">
        <div className="api-request">API Requests</div>
        {dateRange && (
          <div className="selected-filter-tag">
            <span>{dateRange}</span>
          </div>
        )}
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
      return moment(endDate).diff(moment(startDate), 'hours') <= 48;
    },
    disabledText: 'Available for past 24 hours range',
  },
  {
    value: 'hour',
    label: 'Hourly',
    isEnabled: (startDate, endDate) => moment(endDate).diff(moment(startDate), 'days') <= 3,
    disabledText: 'Available for a date range within 3 days',
  },
  {
    value: 'day',
    label: 'Daily',
    isEnabled: (startDate, endDate) => moment(endDate).diff(moment(startDate), 'days') <= 7,
    disabledText: 'Available for 7 days date range',
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
        const isActive = !isDataNotAvailable && selectedAggregation.value === aggregation.value;
        const key = aggregation.value;
        const buttonProps = {
          disabled: !isActive,
          value: aggregation.value,
          key,
          className: 'btn-default',
          style: {
            height: '28px',
            fontSize: '12px',
            paddingInline: '0px',
            paddingBlock: '0px',
            border: isActive ? '1px solid #1583F1' : '1px solid #E4E5E6',
          },
        };

        const isEnabled = aggregation.isEnabled(from, to);
        if (!isEnabled) {
          buttonProps.disabled = 'disabled';
        }

        return (
          <Btn key={key} {...buttonProps} className="aggregations-btn">
            <div className="aggregations">
              <span>{aggregation.label}</span>
              {!isEnabled ? (
                <GenericTooltip align="top">{aggregation.disabledText}</GenericTooltip>
              ) : null}
            </div>
          </Btn>
        );
      })}
    </BtnGroup>
  );
}
