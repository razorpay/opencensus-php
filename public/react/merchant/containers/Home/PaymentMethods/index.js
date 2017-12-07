import React, { Component } from 'react';
import Treemap from './Treemap';
import { fetch } from 'merchant/modules/pokedex';
import { getQuery } from './data';

class PaymentMethods extends Component {
  constructor(props) {
    super(props);

    this.state = {
      data: null,
    };
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
    return (
      <div className="panel">
        <div className="clearfix">
          <div className="pull-left">Showing: All Payment Methods</div>
          <div className="pull-right">...</div>
          <div className="pull-right">
            <select>
              <option>By Transaction Volume</option>
              <option>By Issuer</option>
            </select>
          </div>
        </div>
        <div>
          <Treemap data={this.state.data} />
        </div>
      </div>
    );
  }
}

export default PaymentMethods;
