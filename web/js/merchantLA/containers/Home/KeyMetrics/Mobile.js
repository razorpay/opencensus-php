import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import Change from 'rzp/ui/Change';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
import {
  isDefined,
  getFormattedNumber,
  getFormattedAmountNew,
  getFixedNumber,
  getPercentage,
  paiseToRupees,
  titleCase,
} from 'rzp/utils/rzp-utils';
import {
  humanReadableIndian,
  humanReadableIndianCurrency,
} from 'rzp/utils/numerals';

import Tooltip from 'merchantLA/components/Home/Tooltip';

import { trackGoToLinks } from './ga';
import { tabsMeta } from './data';

const Tab = ({
  value,
  helpText,
  isCurrency,
  title,
  isLoading,
  error,
  percent,
  trend,
  startDate,
  endDate,
  sectionTitle,
  index,
}) => {
  let trendValue = 0,
    trendText = '',
    trendAbsValue = 0;

  let formattedValue = value;

  if (!isDefined(percent)) {
    formattedValue = isCurrency
      ? getFormattedAmountNew(value, true)
      : getFormattedNumber(value);
  } else {
    formattedValue = getFixedNumber(percent) + '%';
  }

  if (trend.show && !trend.loading) {
    const currentCount = trend.currentCount;

    trendValue = currentCount - trend.previousCount;
    trendAbsValue = Math.abs(trendValue);

    trendText = isCurrency
      ? humanReadableIndianCurrency(paiseToRupees(trendAbsValue))
      : humanReadableIndian(trendAbsValue);

    trendText +=
      ' (' +
      (trend.currentCount === 0
        ? trend.previousCount !== 0 ? 100 : 0
        : getPercentage(currentCount, trendAbsValue)) +
      '%)';
  }

  return (
    <div
      className={`card clearfix${
        !trend.show || trend.error ? ' no-trend' : ''
      }`}
    >
      <div className="pull-left">
        <div>{isLoading ? <PlaceholderLoader /> : <b>{formattedValue}</b>}</div>
        <div>{title}</div>
      </div>
      <div className="pull-right text-right">
        {trend.show &&
          !trend.error && (
            <div>
              {trend.loading ? (
                <PlaceholderLoader />
              ) : (
                <Change value={trendValue}>
                  <span>
                    {trendText}
                    <Tooltip value={trendAbsValue} isCurrency={isCurrency} />
                  </span>
                </Change>
              )}
            </div>
          )}
        <div>
          <Link
            target="_blank"
            to={`/${index}?from=${startDate.unix()}&to=${endDate.unix()}&ref=home`}
            onClick={() =>
              trackGoToLinks(titleCase(index), sectionTitle + ' | ' + title)
            }
          >
            <span>
              {`View ${index}`}
              <i className="i i-chevron-right" />
            </span>
          </Link>
        </div>
      </div>
    </div>
  );
};

class MobileKeyMetrics extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { tabsState, loading, startDate, endDate, sectionTitle } = this.props,
      visibleTabs = this.props.getVisibleTabs();

    return (
      <div className="keymetrics keymetrics--mobile">
        {visibleTabs.map((tabName, i) => {
          const tabData = tabsState[tabName].data,
            { isCurrency, title, helpText, index } = tabsMeta[tabName];

          return (
            <Tab
              key={i}
              value={tabData.count}
              helpText={helpText}
              isCurrency={isCurrency}
              title={title}
              isLoading={loading || tabData.loading}
              error={tabData.error}
              percent={tabData.percent}
              trend={tabData.trend}
              index={index}
              startDate={startDate}
              endDate={endDate}
              sectionTitle={sectionTitle}
            />
          );
        })}
      </div>
    );
  }
}

export default MobileKeyMetrics;
