import React, { Component } from 'react';
import { connect } from 'react-redux';

import {
  fetchPayments,
  fetchRefunds,
  fetchSettlements,
} from 'rzp/modules/collection';
import { titleCase } from 'rzp/utils/rzp-utils';

import GenericPanel, {
  PanelBody,
  PanelTopbar,
} from 'merchant/components/Home/GenericPanel';
import { tabs, tabsMeta } from './data';
import PaymentsList from 'merchant/components/Payments/PaymentsList';

const Row = ({ record, tabName }) => {
  const tabMeta = tabsMeta[tabName];

  return (
    <tr>
      {tabMeta.columns.map((columnMeta, index) => {
        let value = record[columnMeta.recordKey];

        value =
          typeof columnMeta.transfomer === 'function'
            ? columnMeta.transfomer(value, record, tabName)
            : value;

        return <td key={index}>{value}</td>;
      })}
    </tr>
  );
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

    this.handleTabClick = ::this.handleTabClick;
  }

  handleTabClick(e) {
    e.preventDefault();

    const tabName = e.target.getAttribute('name');

    this.setState({ selectedTab: tabName });
  }

  fetchData(params) {
    this.props.fetchPayments(params);
    this.props.fetchRefunds(params);
    this.props.fetchSettlements(params);
  }

  componentWillMount() {
    this.fetchData({ count: 10 });
  }

  render() {
    const { selectedTab } = this.state,
      selectedTabData = this.props[selectedTab],
      numColumns = tabsMeta[selectedTab].numColumns;

    let body = null;

    if (selectedTabData.loading || selectedTabData.items.length === 0) {
      body = (
        <tr>
          <td colSpan={numColumns}>
            <center>
              {selectedTabData.loading ? 'Please Wait...' : 'No Records found.'}
            </center>
          </td>
        </tr>
      );
    } else {
      body = selectedTabData.items.map((record, index) => {
        return <Row key={index} record={record} tabName={selectedTab} />;
      });
    }

    return (
      <GenericPanel
        className="recent-activity-cont"
        isLoading={selectedTabData.loading}
      >
        <PanelTopbar>
          <tabbed-container>
            <div className="row">
              {tabs.map((tabName, index) => {
                const className =
                  (tabName === selectedTab ? 'active ' : '') + 'col-sm-4';

                return (
                  <a
                    className={className}
                    key={index}
                    name={tabName}
                    onClick={this.handleTabClick}
                  >
                    {tabName.toUpperCase()}
                  </a>
                );
              })}
            </div>
          </tabbed-container>
        </PanelTopbar>
        <PanelBody>
          <table className="table table-striped">
            <tbody>{body}</tbody>
          </table>
        </PanelBody>
      </GenericPanel>
    );
  }
}
