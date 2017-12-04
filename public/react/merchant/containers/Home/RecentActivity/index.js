import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import {
  fetchPayments,
  fetchRefunds,
  fetchSettlements,
} from 'rzp/modules/collection';

import PaymentsList from 'merchant/components/Payments/PaymentsList';

const tabs = ['payments', 'settlements', 'refunds'],
  tabsMeta = {
    [tabs[0]]: {
      columns: [
        {
          recordKey: 'amount',
          transfomer: value => {
            return <Amount value={value} />;
          },
        },
        {
          recordKey: 'id',
          transfomer: value => {
            return (
              <Link to={`/payments/${value}`}>
                <code>{value}</code>
              </Link>
            );
          },
        },
      ],
    },
  };

@connect(
  state => {
    return {
      payments: state.payments,
      refunds: state.refunds,
      settlements: state.settlements,
    };
  },
  {
    fetchPayments,
    fetchRefunds,
    fetchSettlements,
  }
)
export default class RecentActivity extends Component {
  constructor(props) {
    super(props);

    this.state = {
      selectedTab: tabs[0],
    };
  }

  componentWillMount() {
    const numRows = 6;

    this.props.fetchPayments(numRows);
    this.props.fetchRefunds(numRows);
    this.props.fetchSettlements(numRows);
  }

  render() {
    const { selectedTab } = this.state,
      { payments, settlements, refunds } = this.props;

    return (
      <div className="panel">
        <tabbed-container>
          <header className="row">
            {tabs.map((tabName, index) => {
              const className =
                (tabName === selectedTab ? 'active ' : '') + 'col-sm-4';

              return (
                <a className={className} key={index}>
                  {tabName}
                </a>
              );
            })}
          </header>
        </tabbed-container>
        <table className="table table-striped" />
      </div>
    );
  }
}
