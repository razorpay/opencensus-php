import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import Chart from 'chart.js';
import { Line } from 'react-chartjs-2';
import { PowerSelect } from 'react-power-select';

import Definition from 'rzp/ui/Definition';
import Change from 'rzp/ui/Change';
import { BtnGroup, Btn } from 'rzp/ui/BtnGroup/index.js';
import { namedColors } from 'rzp/utils/chart/colors';
import { titleCase, paiseToRupees, getPercentage } from 'rzp/utils/rzp-utils';
import { timeScale } from 'rzp/utils/chart/new.js';
import takeScreenshot from 'rzp/utils/screenshot';
import Group, { GroupItem } from 'rzp/ui/Group';
import {
  humanReadableIndian,
  humanReadableIndianCurrency,
} from 'rzp/utils/numerals';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
import GenericTooltip from 'rzp/ui/Tooltip';

import { tabsMeta, breakdownVals } from './data';
import GroupingDropdown from 'merchant/containers/Home/GroupingDropdown';
import FilteringDropdown from 'merchant/components/Home/FilteringDropdown';
import Legend from 'merchant/components/Home/Legend';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import MoreOptionsButton from 'merchant/containers/Home/MoreOptionsButton';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import Tooltip from 'merchant/components/Home/Tooltip';
import { CUMULATIVE } from 'merchant/containers/Home/KeyMetrics/data';

import { trackGoToLinks } from './ga';
import customToolTip, { positioner } from './customTooltip';

Chart.Tooltip.positioners.custom = positioner;

const globalChartOptions = {
  ...timeScale({}),
  layout: {
    padding: {
      top: 0,
      left: 0,
      right: 0,
    },
  },
  tooltips: {
    enabled: false,
    position: 'custom',
    caretPadding: 0,
    yPadding: 0,
    xPadding: 0,
    /* custom tooltip */
    custom: customToolTip,
  },
};

/*
 * This component is responsible to show tab content in `KeyMetrics`
 * component.
 */

class Panel extends Component {
  constructor(props) {
    super(props);

    this.meta = tabsMeta[props.tabName];
    this.handleGroupingChange = ::this.handleGroupingChange;
    this.handleBreakdownChange = ::this.handleBreakdownChange;
    this.handleImageExportClick = ::this.handleImageExportClick;
    this.handleFilterChange = ::this.handleFilterChange;
  }

  handleGroupingChange({ option }) {
    const { tabName, onGroupingChange } = this.props;

    return onGroupingChange && onGroupingChange(tabName, option);
  }

  handleFilterChange(option) {
    const { tabName, onFilterChange } = this.props;

    return onFilterChange && onFilterChange(tabName, option);
  }

  handleBreakdownChange(value) {
    const { tabName, onBreakdownChange } = this.props;

    return onBreakdownChange && onBreakdownChange(tabName, value);
  }

  handleImageExportClick(e) {
    const a = e.target;

    const { tabName, data } = this.props,
      { png } = data;

    if (!png.url) {
      e.preventDefault();

      takeScreenshot(this.panelBody).then(url => {
        this.props.onScreenshot(tabName, url, () => {
          a.click();
        });
      });
    }
  }

  render() {
    const {
        selectedGrouping,
        selectedFilters,
        data,
        startDate,
        endDate,
        selectedBreakdown,
        lastUpdatedAt,
        isCurrency,
        externalUrl,
        showGrouping,
        tabName,
        sectionTitle,
      } = this.props,
      dateFormat = 'DD MMM YYYY',
      { grouping, options, filters } = this.meta,
      { loading, histogram, trend } = data;

    const hasNoData = !histogram || histogram.datasets.length === 0;

    const noGrouping = selectedGrouping &&
	                   selectedGrouping.value === CUMULATIVE ||
					   this.meta.noGrouping;

    let trendValue = 0,
      trendText = '',
      trendAbsValue = 0;

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

    let chartOptions = { ...globalChartOptions };

    if (selectedBreakdown === 'hourly') {
      chartOptions = {
        ...chartOptions,
        ...timeScale({ breakdown: selectedBreakdown }),
      };
    }

    // following chart options will be used by cutomTooltip.js
    chartOptions.isCurrency = isCurrency;
    chartOptions.externalUrl = externalUrl;
    chartOptions.breakdown = selectedBreakdown;
    chartOptions.graphStartDate = startDate.toDate();
    chartOptions.graphEndDate = endDate.toDate();
    chartOptions.noGrouping = noGrouping;

    const getChartData = (canvas) => {

      if (!data.histogram ||
          !selectedGrouping ||
          selectedGrouping.value !== CUMULATIVE) {

        return data.histogram;
      }

      const ctx      = canvas.getContext("2d"),
            gradient = ctx.createLinearGradient(0,0,0,250),
			// reducing opacity of primary color
			startColor = namedColors.primaryColor.replace(/1\)$/, "0.5)");

      gradient.addColorStop(0, startColor);
      gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');

      const { datasets } = data.histogram;

      if (datasets && datasets[0]) {

        datasets[0].backgroundColor = gradient;
        datasets[0].borderColor = namedColors.primaryColor;
		datasets[0].borderWidth = 2;
      }

      return data.histogram;
    };

    return (
      <GenericPanel
        className="key-metrics-container"
        isLoading={data.loading}
        hasNoData={hasNoData}
        error={data.error}
      >
        <PanelTopbar className="clearfix">
          {data.trend.show &&
            !data.trend.error && (
              <div
                className={`pull-left ${
                  data.trend.loading ? ' trend-loading' : ''
                }`}
              >
                <div>
                  <Change value={trendValue}>
                    {trend.loading ? (
                      <PlaceholderLoader />
                    ) : (
                      <span>
                        {trendText}
                        <Tooltip
                          value={trendAbsValue}
                          isCurrency={isCurrency}
                        />
                      </span>
                    )}
                  </Change>
                  <Definition>
                    <span className="text-fade">
                      {trend.loading ? (
                        <PlaceholderLoader />
                      ) : (
                        <span>
                          Compared to:
                          <span>
                            {<span>&nbsp;&nbsp;</span>}
                            {trend.startDate.format(dateFormat)}{' '}
                          </span>
                          -
                          <span> {trend.endDate.format(dateFormat)} </span>
                        </span>
                      )}
                    </span>
                  </Definition>
                </div>
              </div>
            )}
          <div className="panel-actions pull-right">
            <BtnGroup
              className="panel-action-item time-breakdown"
              value={selectedBreakdown}
              onChange={this.handleBreakdownChange}
            >
              {breakdownVals.map((item, index) => {
                const btnProps = {
                    value: item.value,
                    key: index,
                    className: 'btn-default',
                  },
                  isEnabled = item.isEnabled(startDate, endDate);

                if (!isEnabled) {
                  btnProps.disabled = 'disabled';
                }

                return (
                  <Btn {...btnProps}>
                    <span>{item.title}</span>
                    {!isEnabled && (
                      <GenericTooltip align="top">
                        {item.disabledText}
                      </GenericTooltip>
                    )}
                  </Btn>
                );
              })}
            </BtnGroup>
            {showGrouping &&
              grouping.length > 0 && (
                <div className="panel-action-item">
                  <GroupingDropdown
                    onGroupChange={this.handleGroupingChange}
                    grouping={grouping}
                    selectedGrouping={selectedGrouping}
                    sectionTitle={`${sectionTitle} | ${this.meta.title}`}
                  />
                </div>
              )}
            {filters &&
              filters.length > 0 && (
                <div className="panel-action-item">
                  <FilteringDropdown
                    onFilterChange={this.handleFilterChange}
                    filters={filters}
                    selectedFilters={selectedFilters}
                  />
                </div>
              )}
            <div className="panel-action-item">
              <MoreOptionsButton
                csvData={data.csv}
                pngData={data.png}
                sectionTitle={sectionTitle}
                tabName={this.meta.title}
                handleImageDownload={this.handleImageExportClick}
              />
            </div>
          </div>
        </PanelTopbar>

        <PanelBody>
          <div
            className="panel-body-content"
            ref={node => (this.panelBody = node)}
          >
            <div className="chart-container">
              {!data.loading &&
                data.histogram && (
                  <Line options={chartOptions} data={getChartData} />
                )}
            </div>
            {!noGrouping &&
              !data.loading &&
              data.legendData && (
                <div>
                  <Legend data={data.legendData} isCurrency={isCurrency} />
                </div>
              )}
          </div>
        </PanelBody>

        <PanelFooter>
          <div className="pull-left">
            <LastUpdated at={lastUpdatedAt} />
          </div>
          <div className="pull-right">
            <Link
              target="_blank"
              to={`/${
                this.meta.index
              }?from=${startDate.unix()}&to=${endDate.unix()}&ref=home`}
              onClick={() =>
                trackGoToLinks(
                  titleCase(this.meta.index),
                  sectionTitle + ' | ' + this.meta.title
                )
              }
            >
              {`View these ${titleCase(this.meta.index)} `}
              <i className="i i-chevron-right" />
            </Link>
          </div>
        </PanelFooter>
      </GenericPanel>
    );
  }
}

export default Panel;
