import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Change from 'common/ui/Change';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import {
  i18HumanReadableCurrency,
  i18HumanReadableNumerals,
  i18nifyHumanReadable,
} from 'common/utils/numerals';
import {
  isDefined,
  getFixedNumber,
  getPercentage,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
  titleCase,
} from 'common/utils/rzp-utils';
import Tooltip from 'merchant/components/Home/Tooltip';
import { isJKOfflineMerchant } from 'merchant/components/Sidebar/helpers';

import { TABS_FOR_JK_ORG, tabsMeta } from './data';
import { trackGoToLinks } from './ga';

const Tab = ({
  value,
  isCurrency,
  title,
  isLoading,
  percent,
  trend,
  startDate,
  endDate,
  sectionTitle,
  index,
  user,
  shouldHideTabs,
}) => {
  let trendValue = 0;
  let trendText = '';
  let trendAbsValue = 0;

  let formattedValue = value;
  const currentMerchantCurrency = user.merchant.currency;

  if (!isDefined(percent)) {
    if (isCurrency) {
      const convertedAmount = i18CurrencyConversionFromMinorUnitToCommonUnit(
        value,
        currentMerchantCurrency,
      );
      formattedValue = i18nifyHumanReadable(convertedAmount, currentMerchantCurrency);
    } else {
      formattedValue = i18nifyHumanReadable(value);
    }
  } else {
    formattedValue = `${getFixedNumber(percent)}%`;
  }

  if (trend.show && !trend.loading) {
    const currentCount = trend.currentCount;

    trendValue = currentCount - trend.previousCount;
    trendAbsValue = Math.abs(trendValue);

    const convertedAmount = i18CurrencyConversionFromMinorUnitToCommonUnit(
      trendAbsValue,
      currentMerchantCurrency,
    );

    trendText = isCurrency
      ? i18HumanReadableCurrency(convertedAmount, currentMerchantCurrency)
      : i18HumanReadableNumerals(trendAbsValue, currentMerchantCurrency);

    trendText += ` (${
      trend.currentCount === 0
        ? trend.previousCount !== 0
          ? 100
          : 0
        : getPercentage(currentCount, trendAbsValue)
    }%)`;
  }

  return (
    <div className={`card clearfix${!trend.show || trend.error ? ' no-trend' : ''}`}>
      <div className="pull-left">
        <div>{isLoading ? <PlaceholderLoader /> : <b>{formattedValue}</b>}</div>
        <div>{title}</div>
      </div>
      <div className="pull-right text-right">
        {trend.show && !trend.error && (
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
            target={shouldHideTabs ? '_self' : '_blank'}
            rel="noreferrer noopener"
            to={`/${index}?from=${startDate.unix()}&to=${endDate.unix()}&ref=home`}
            onClick={() => trackGoToLinks(titleCase(index), `${sectionTitle} | ${title}`)}
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

@connect((state) => ({
  user: state.session.user,
  org: state.session.org,
}))
class MobileKeyMetrics extends Component {
  // eslint-disable-next-line no-useless-constructor
  constructor(props) {
    super(props);
  }

  render() {
    const { tabsState, loading, startDate, endDate, sectionTitle, user, org } = this.props;
    let visibleTabs = this.props.getVisibleTabs();

    const shouldHideTabs = isJKOfflineMerchant(org, user);
    if (shouldHideTabs) {
      visibleTabs = TABS_FOR_JK_ORG;
    }

    return (
      <div className="keymetrics keymetrics--mobile">
        {visibleTabs.map((tabName, i) => {
          const tabData = tabsState[tabName].data;
          const { isCurrency, title, helpText, index } = tabsMeta[tabName];

          return (
            <Tab
              user={user}
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
              shouldHideTabs={shouldHideTabs}
            />
          );
        })}
      </div>
    );
  }
}

export default MobileKeyMetrics;
