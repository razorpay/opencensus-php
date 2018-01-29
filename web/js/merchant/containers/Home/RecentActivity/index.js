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
import TabsContainer from 'rzp/ui/Tabs';
import { tabs, tabsMeta } from './data';

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

    let body = null;

    let tabContent = [];

    tabs.forEach(selectedTab => {
      const selectedTabData = this.props[selectedTab], numColumns = tabsMeta[selectedTab].numColumns;
      let body;

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

      tabContent.push(
        <table class="table table-striped table-activity">
          <tbody>
          {body}
          </tbody>
        </table>
      );
    });


    return (
      <TabsContainer
        tabNames={tabs}
        className="some-class panel recent-activity-cont"
      >
        {tabContent}
      </TabsContainer>
    );
  }
}
