import React, { Component } from 'react';
import { connect } from 'react-redux';

import Breadcrumb, { BreadcrumbItem } from 'rzp/ui/Breadcrumb';

import Treemap from './Treemap';
import { fetch } from 'merchant/modules/pokedex';
import { getQuery } from './data';

function getLevels(hierarchy, levels = []) {
  if (hierarchy.parent) {
    getLevels(hierarchy.parent, levels);
  }

  levels.push({
    name: hierarchy.key,
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
    ).then(resp => {
      this.setState({ data: resp.data.agg });
    });
  }

  onLevelChange(hierarchy) {
    this.setState({
      levels: getLevels(hierarchy),
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
    const { levels } = this.state;

    return (
      <div className="panel">
        <div className="clearfix">
          <div className="pull-left">
            <span>Showing:</span>
            <Breadcrumb>
              {levels.length > 0 ? (
                levels.map((level, index) => (
                  <BreadcrumbItem key={index}>{level.name}</BreadcrumbItem>
                ))
              ) : (
                <BreadcrumbItem>All Methods</BreadcrumbItem>
              )}
            </Breadcrumb>
          </div>
          <div className="pull-right">...</div>
          <div className="pull-right">
            <select>
              <option>By Transaction Volume</option>
              <option>By Issuer</option>
            </select>
          </div>
        </div>
        <div>
          <Treemap data={this.state.data} onLevelChange={this.onLevelChange} />
        </div>
      </div>
    );
  }
}

export default PaymentMethods;
