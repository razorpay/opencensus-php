import React, { Component } from 'react';
import { connect } from 'react-redux';

import Breadcrumb, { BreadcrumbItem } from 'rzp/ui/Breadcrumb';

import Treemap from 'merchant/containers/Home/PaymentMethods/Treemap';
import LastUpdated from 'merchant/components/Home/LastUpdated';
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
    };

    this.onLevelChange = ::this.onLevelChange;
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
    const { levels } = this.state,
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
              <button className="btn btn-default">...</button>
            </div>
          </div>
        </div>
        <div>
          <Treemap
            data={this.state.data}
            onLevelChange={this.onLevelChange}
            currentLevel={this.state.currentLevel}
          />
        </div>
        <div className="panel-footer">
          <LastUpdated at={this.state.lastUpdatedAt} />
        </div>
      </div>
    );
  }
}

export default PaymentMethods;
