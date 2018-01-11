import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import Chart from 'chart.js';
import { Line } from 'react-chartjs-2';
import { PowerSelect } from 'react-power-select';

import Definition from 'rzp/ui/Definition';
import ChangeRange from 'rzp/ui/ChangeRange';
import { BtnGroup, Btn } from 'rzp/ui/BtnGroup';
import { titleCase } from 'rzp/utils/rzp-utils';
import { timeScale } from 'rzp/utils/chart/new.js';
import takeScreenshot from 'rzp/utils/screenshot';
import Group, { GroupItem } from 'rzp/ui/Group';
import {
  humanReadableIndian,
  humanReadableIndianCurrency,
} from 'rzp/utils/numerals';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';

import { tabsMeta, breakdownVals } from './data';
import GroupingDropdown from 'merchant/components/Home/GroupingDropdown';
import Legend from 'merchant/components/Home/Legend';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import MoreOptionsButton from 'merchant/components/Home/MoreOptionsButton';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import customToolTip, { positioner } from './customTooltip';

Chart.Tooltip.positioners.custom = positioner;

const chartOptions = {
  ...timeScale({}),
  layout: {
    padding: {
      top: 21,
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
  }

  handleGroupingChange({ option }) {
    const { tabName, onGroupingChange } = this.props;

    return onGroupingChange && onGroupingChange(tabName, option);
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
        data,
        startDate,
        endDate,
        selectedBreakdown,
        lastUpdatedAt,
        isCurrency,
        externalUrl,
      } = this.props,
      dateFormat = 'DD MMM YYYY',
      { grouping, options } = this.meta,
      { loading, histogram, trend } = data;

    const hasNoData = !histogram || histogram.datasets.length === 0;

    // following chart options will be used by cutomTooltip.js
    chartOptions.isCurrency = isCurrency;
    chartOptions.externalUrl = externalUrl;
    chartOptions.breakdown = selectedBreakdown;

    return (
      <GenericPanel
        className="key-metrics-container"
        isLoading={data.loading}
        hasNoData={hasNoData}
      >
        <PanelTopbar className="clearfix">
          {data.trend.show && (
            <div className="pull-left">
              {data.trend.loading ? (
                <div>
                  <div>
                    <PlaceholderLoader />
                  </div>
                  <div className="text-fade">
                    <PlaceholderLoader />
                  </div>
                </div>
              ) : (
                <div>
                  <ChangeRange
                    previous={trend.previousCount}
                    current={trend.currentCount}
                  />
                  <Definition>
                    <span className="text-fade">
                      Compared to
                      <strong> {trend.startDate.format(dateFormat)} </strong>
                      -
                      <strong> {trend.endDate.format(dateFormat)} </strong>
                    </span>
                  </Definition>
                </div>
              )}
            </div>
          )}
          <div className="panel-actions pull-right">
            {grouping.length > 0 && (
              <div className="panel-action-item">
                <GroupingDropdown
                  onGroupChange={this.handleGroupingChange}
                  grouping={grouping}
                  selectedGrouping={selectedGrouping}
                />
              </div>
            )}
            <BtnGroup
              className="panel-action-item"
              value={selectedBreakdown}
              onChange={this.handleBreakdownChange}
            >
              {breakdownVals.map((item, index) => {
                return (
                  <Btn value={item} key={index} className="btn-default">
                    {titleCase(item)}
                  </Btn>
                );
              })}
            </BtnGroup>
            <div className="panel-action-item">
              <MoreOptionsButton
                csvData={data.csv}
                pngData={data.png}
                onImageExport={this.handleImageExportClick}
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
                  <Line options={chartOptions} data={data.histogram} />
                )}
            </div>
            {!data.loading &&
              data.legendData && (
                <div>
                  <Legend
                    data={data.legendData}
                    valueTransformer={
                      isCurrency
                        ? humanReadableIndianCurrency
                        : humanReadableIndian
                    }
                  />
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
              to={`/${this.meta
                .index}?from=${startDate.unix()}&to=${endDate.unix()}`}
            >
              {`View all ${titleCase(this.meta.index)}`}
            </Link>
          </div>
        </PanelFooter>
      </GenericPanel>
    );
  }
}

export default Panel;
