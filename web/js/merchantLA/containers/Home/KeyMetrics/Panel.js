import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import Chart from 'chart.js';
import { Line } from 'react-chartjs-2';
import { PowerSelect } from 'react-power-select';
import Definition from 'common/ui/Definition';
import Change from 'common/ui/Change';
import { BtnGroup, Btn } from 'common/ui/BtnGroup/index';
import { namedColors } from 'common/utils/chart/colors';
import { isDefined, titleCase, paiseToRupees, getPercentage } from 'common/utils/rzp-utils';
import debounce from 'common/utils/debounce';
import { timeScale } from 'common/utils/chart/new';
import Group, { GroupItem } from 'common/ui/Group';
import { humanReadableIndian, humanReadableIndianCurrency } from 'common/utils/numerals';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import GenericTooltip from 'common/ui/Tooltip';
import { tabsMeta, breakdownVals, breakdownValsMap, PLATFORM, CUMULATIVE } from './data';
import GroupingDropdown from 'merchantLA/containers/Home/GroupingDropdown';
import FilteringDropdown from 'merchantLA/components/Home/FilteringDropdown';
import Legend from 'merchantLA/components/Home/Legend';
import LastUpdated from 'merchantLA/components/Home/LastUpdated';
import MoreOptionsButton from 'merchantLA/containers/Home/MoreOptionsButton';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchantLA/components/Home/GenericPanel';
import Tooltip from 'merchantLA/components/Home/Tooltip';

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
      bottom: 0,
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

const _getChartData = (data, selectedGrouping, canvas) => {
  /*
   * Used to specify the color of the series,
   * For Cumulative graph, we need to render gradient
   */

  if (!data.histogram || !selectedGrouping || selectedGrouping.value !== CUMULATIVE) {
    return data.histogram;
  }

  const ctx = canvas.getContext('2d');
  const gradient = ctx.createLinearGradient(0, 0, 0, 250);
  // reducing opacity of primary color
  const startColor = namedColors.primaryColor.replace(/1\)$/, '0.5)');

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

/*
 * This component is responsible to show tab content in `KeyMetrics`
 * component.
 */

class Panel extends Component {
  constructor(props) {
    super(props);

    this.meta = tabsMeta[props.tabName];

    this.state = {
      visibleGroups: this.getVisibleGroups(props.showGroupingByPtfm),
      hideGraph: false,
    };

    this.handleGroupingChange = ::this.handleGroupingChange;
    this.handleBreakdownChange = ::this.handleBreakdownChange;
    this.handleBreakdownSelectChange = ::this.handleBreakdownSelectChange;
    this.handleImageExportClick = ::this.handleImageExportClick;
    this.handleFilterChange = ::this.handleFilterChange;
    this.handleResize = debounce(::this.handleResize, 250);
  }

  getVisibleGroups(showGroupingByPtfm) {
    const { grouping = [] } = this.meta;

    return showGroupingByPtfm || grouping.length === 0
      ? grouping
      : grouping.filter((groupItem) => {
          return groupItem.value !== PLATFORM;
        });
  }

  setVisibleGroups(showGroupingByPtfm) {
    showGroupingByPtfm = isDefined(showGroupingByPtfm)
      ? showGroupingByPtfm
      : this.props.showGroupingByPtfm;

    this.setState({
      visibleGroups: this.getVisibleGroups(showGroupingByPtfm),
    });
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

  handleBreakdownSelectChange({ option: selectedBreakdown }) {
    const { value } = selectedBreakdown;

    return this.handleBreakdownChange(value);
  }

  handleImageExportClick(e) {
    const a = e.target;

    const { tabName, data } = this.props;
    const { png } = data;

    if (!png.url) {
      e.preventDefault();

      import('common/utils/screenshot').then((module) => {
        const takeScreenshot = module.default;
        takeScreenshot(this.panelBody).then((url) => {
          this.props.onScreenshot(tabName, url, () => {
            a.click();
          });
        });
      });
    }
  }

  handleResize() {
    if (!this.chartInstance || !this.chartInstance.chartInstance) {
      return;
    }

    this.setState(
      {
        hideGraph: true,
      },
      () => {
        this.setState({
          hideGraph: false,
        });
      },
    );
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.showGroupingByPtfm !== this.props.showGroupingByPtfm) {
      this.setVisibleGroups(nextProps.showGroupingByPtfm);
    }
  }

  componentDidMount() {
    window.addEventListener('resize', this.handleResize);
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.handleResize);
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
      sectionTitle,
    } = this.props;
    const { visibleGroups: grouping } = this.state;
    const dateFormat = 'DD MMM YYYY';
    const { filters } = this.meta;
    const { loading, histogram, trend } = data;

    const hasNoData = !histogram || histogram.datasets.length === 0;

    const noGrouping =
      (selectedGrouping && selectedGrouping.value === CUMULATIVE) || this.meta.noGrouping;

    let trendValue = 0;
    let trendText = '';
    let trendAbsValue = 0;

    if (trend.show && !trend.loading) {
      const currentCount = trend.currentCount;

      trendValue = currentCount - trend.previousCount;
      trendAbsValue = Math.abs(trendValue);

      trendText = isCurrency
        ? humanReadableIndianCurrency(paiseToRupees(trendAbsValue))
        : humanReadableIndian(trendAbsValue);

      trendText += ` (${
        trend.currentCount === 0
          ? trend.previousCount !== 0
            ? 100
            : 0
          : getPercentage(currentCount, trendAbsValue)
      }%)`;
    }

    let chartOptions = { ...globalChartOptions };

    chartOptions = {
      ...chartOptions,
      ...timeScale({
        breakdown: selectedBreakdown,
        startDate,
      }),
    };

    // following chart options will be used by cutomTooltip.js
    chartOptions.isCurrency = isCurrency;
    chartOptions.externalUrl = externalUrl;
    chartOptions.breakdown = selectedBreakdown;
    chartOptions.graphStartDate = startDate.toDate();
    chartOptions.graphEndDate = endDate.toDate();
    chartOptions.noGrouping = noGrouping;

    const getChartData = _getChartData.bind(null, data, selectedGrouping);

    return (
      <GenericPanel
        className={`key-metrics-container${!loading && noGrouping ? ' no-legends' : ''}`}
        isLoading={data.loading}
        hasNoData={hasNoData}
        error={data.error}
      >
        <PanelTopbar className="clearfix">
          {data.trend.show && !data.trend.error && (
            <div className={`pull-left ${data.trend.loading ? ' trend-loading' : ''}`}>
              <div>
                <Change value={trendValue}>
                  {trend.loading ? (
                    <PlaceholderLoader />
                  ) : (
                    <span>
                      {trendText}
                      <Tooltip value={trendAbsValue} isCurrency={isCurrency} />
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
                        -<span> {trend.endDate.format(dateFormat)} </span>
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
                };
                const isEnabled = item.isEnabled(startDate, endDate);

                if (!isEnabled) {
                  btnProps.disabled = 'disabled';
                }

                return (
                  <Btn key={`${item.title}_index`} {...btnProps}>
                    <span>{item.title}</span>
                    {!isEnabled && <GenericTooltip align="top">{item.disabledText}</GenericTooltip>}
                  </Btn>
                );
              })}
            </BtnGroup>
            <div className="panel-action-item time-breakdown-select">
              <Group>
                <GroupItem>
                  <i className="i i-sort grouping-icon" />
                </GroupItem>
                <GroupItem className="time-breakdown">
                  <PowerSelect
                    className="react-normal-select"
                    onChange={this.handleBreakdownSelectChange}
                    options={breakdownVals}
                    optionLabelPath="title"
                    searchEnabled={false}
                    selected={breakdownValsMap[selectedBreakdown]}
                  />
                </GroupItem>
              </Group>
            </div>
            {grouping.length > 0 && (
              <div id="keymetrics-grouping" className="panel-action-item">
                <GroupingDropdown
                  onGroupChange={this.handleGroupingChange}
                  grouping={grouping}
                  selectedGrouping={selectedGrouping}
                  sectionTitle={`${sectionTitle} | ${this.meta.title}`}
                />
              </div>
            )}
            {filters && filters.length > 0 && (
              <div className="panel-action-item">
                <FilteringDropdown
                  onFilterChange={this.handleFilterChange}
                  filters={filters}
                  selectedFilters={selectedFilters}
                />
              </div>
            )}
            <div id="keymetrics-download" className="panel-action-item">
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
          <div className="panel-body-content" ref={(node) => (this.panelBody = node)}>
            <div className="chart-container">
              {!data.loading && data.histogram && !this.state.hideGraph && (
                <Line
                  options={chartOptions}
                  data={getChartData}
                  ref={(node) => (this.chartInstance = node)}
                />
              )}
            </div>
            {!noGrouping && !data.loading && data.legendData && (
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
              rel="noreferrer noopener"
              to={`/${this.meta.index}?from=${startDate.unix()}&to=${endDate.unix()}&ref=home`}
              onClick={() =>
                trackGoToLinks(titleCase(this.meta.index), `${sectionTitle} | ${this.meta.title}`)
              }
            >
              {`View all ${this.meta.index} from this date range`}
            </Link>
          </div>
        </PanelFooter>
      </GenericPanel>
    );
  }
}

export default Panel;
