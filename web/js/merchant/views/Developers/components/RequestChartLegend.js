import React from 'react';
import Legend, { LegendItem } from 'common/ui/Legend';

export default function RequestLegend(props) {
  const {
    filteredStatusCodeList,
    handleFilteredStatusCodeChange,
    legend,
    isDataNotAvailable,
  } = props;

  return (
    <div className="status-legend-container">
      <Legend alignment="horizontal">
        {legend.map(({ value, title, color }) => (
          <LegendItem key={value}>
            <label htmlFor={`status-checkbox-${value}`}>
              <input
                name="status-checkbox"
                value={value}
                type="checkbox"
                checked={!filteredStatusCodeList.includes(value)}
                onChange={() => handleFilteredStatusCodeChange(value)}
                id={`status-checkbox-${value}`}
                disabled={isDataNotAvailable}
              />
              <div
                className={
                  isDataNotAvailable
                    ? `status-checkbox status-checkbox__disabled`
                    : `status-checkbox status-checkbox__${color}`
                }
              />
              {title}
            </label>
          </LegendItem>
        ))}
      </Legend>
    </div>
  );
}
