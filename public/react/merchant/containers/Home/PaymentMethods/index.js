import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Breadcrumb, { BreadcrumbItem } from 'rzp/ui/Breadcrumb';

import Treemap from 'merchant/containers/Home/PaymentMethods/Treemap';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import MoreOptionsButton from 'merchant/components/Home/MoreOptionsButton';
import { fetch } from 'merchant/modules/pokedex';

import { getQuery } from './data';
import './styles.styl';

function getLevels(hierarchy, levels = []) {
  if (hierarchy.parent) {
    getLevels(hierarchy.parent, levels);
  }

  levels.push({
    name: hierarchy.displayText,
    data: hierarchy,
  });

  return levels;
}

@connect(null, null)
class PaymentMethods extends Component {
  constructor(props) {
    super(props);

    this.state = {
      data: null,
      levels: [],
      currentLevel: null,
      csvData: null,
    };

    this.onLevelChange = ::this.onLevelChange;
    this.onCSVData = ::this.onCSVData;
  }

  fetchData(startDate, endDate) {
    fetch(
      getQuery({
        merchantId: '10000000000000',
        startTime: startDate.unix(),
        endTime: endDate.unix(),
      })
    ).then(({ data: { agg } }) => {
      this.setState({
        data: agg.result,
        lastUpdatedAt: agg.last_updated_at,
      });
    });
  }

  onCSVData(csvUrl) {
    this.setState({
      csvData: {
        name: 'Payment Methods Data.csv',
        url: csvUrl,
      },
    });
  }

  onLevelChange(hierarchy) {
    this.setState({
      levels: getLevels(hierarchy),
      currentLevel: hierarchy,
    });
  }

  componentWillMount() {
    const { startDate, endDate } = this.props;

    this.fetchData(startDate, endDate);
  }

  componentWillReceiveProps(nextProps) {
    const { startDate, endDate } = nextProps;

    if (
      startDate.unix() !== this.props.startDate.unix() ||
      endDate.unix() !== this.props.endDate.unix()
    ) {
      this.fetchData(startDate, endDate);
    }
  }

  render() {
    const { levels, csvData } = this.state,
      { startDate, endDate } = this.props,
      levelsLength = levels.length;

    return (
      <div className="panel p-all payment-methods-container">
        <div className="clearfix">
          <div className="panel-actions p-b pull-left">
            <span>Showing:</span>
            {levelsLength > 0 && (
              <Breadcrumb>
                {levels.map((level, index) => (
                  <BreadcrumbItem
                    key={index}
                    onClick={() =>
                      index + 1 !== levelsLength &&
                      this.onLevelChange(level.data)}
                  >
                    {level.name}
                  </BreadcrumbItem>
                ))}
              </Breadcrumb>
            )}
          </div>
          <div className="panel-actions p-b pull-right">
            <div className="panel-action-item">
              <MoreOptionsButton csvData={csvData} />
            </div>
          </div>
        </div>
        <div>
          <Treemap
            data={this.state.data}
            onLevelChange={this.onLevelChange}
            currentLevel={this.state.currentLevel}
            onCSVData={this.onCSVData}
          />
        </div>
        <div className="panel-footer clearfix">
          <div className="pull-left">
            <LastUpdated at={this.state.lastUpdatedAt} />
          </div>
          <div className="pull-right">
            <Link
              to={`/payments?from=${startDate.unix()}&to=${endDate.unix()}`}
            >
              View all Payments &gt;
            </Link>
          </div>
        </div>
      </div>
    );
  }
}

export default PaymentMethods;
