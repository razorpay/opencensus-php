import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import {
  fetchPayments,
  fetchRefunds,
  fetchSettlements,
} from 'rzp/modules/collection';
import { titleCase } from 'rzp/utils/rzp-utils';

import GenericPanel, {
  PanelBody,
  PanelTopbar,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import { tabs, tabsMeta } from './data';
import PaymentsList from 'merchant/components/Payments/PaymentsList';

import { trackTabClick, trackEntityClick, trackGoToLinks } from './ga';

const shouldDisplayCompact = () => window.outerWidth < 1186;

const Row = ({ record, tabName, tabTitle, sectionTitle, displayCompact }) => {
  const tabMeta = tabsMeta[tabName];

  return (
    <tr>
      {tabMeta.columns.map((columnMeta, index) => {
        if (displayCompact && index === 1) {
          return null;
        }

        let value = record[columnMeta.recordKey];

        value =
          typeof columnMeta.transfomer === 'function'
            ? columnMeta.transfomer(value, record, tabName, displayCompact)
            : value;

        if (columnMeta.recordKey === 'id') {
          value = (
            <value.type
              {...value.props}
              onClick={() => trackEntityClick(tabTitle, sectionTitle)}
            >
              {value.props.children}
            </value.type>
          );
        }

        return <td key={tabName + '-' + index}>{value}</td>;
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
      displayCompact: shouldDisplayCompact(),
    };

    this.handleTabClick = ::this.handleTabClick;
    this.handleResize = ::this.handleResize;
  }

  handleTabClick(e) {
    e.preventDefault();

    const tabName = e.target.getAttribute('name');

    this.setState({ selectedTab: tabName });
    trackTabClick(titleCase(tabName), this.props.sectionTitle);
  }

  handleResize() {
    this.setState({
      displayCompact: shouldDisplayCompact(),
    });
  }

  fetchData(params) {
    this.props.fetchPayments(params).then(data => {
      return (
        this.props.onFetchPayments &&
        this.props.onFetchPayments(data && data.data)
      );
    });
    this.props.fetchRefunds(params);
    this.props.fetchSettlements(params);
  }

  componentWillMount() {
    this.fetchData({ count: 5 });
  }

  componentDidMount() {
    window.addEventListener('resize', this.handleResize);
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.handleResize);
  }

  render() {
    const { selectedTab, displayCompact } = this.state,
      selectedTabData = this.props[selectedTab],
      numColumns = tabsMeta[selectedTab].columns.length,
      selectedTabTitle = titleCase(selectedTab);

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
        return (
          <Row
            key={index}
            record={record}
            tabName={selectedTab}
            tabTitle={selectedTabTitle}
            sectionTitle={this.props.sectionTitle}
            displayCompact={displayCompact}
          />
        );
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
                  (tabName === selectedTab ? 'active ' : '') + 'col-xs-4';

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
        <PanelFooter>
          <div className="clearfix">
            <div className="pull-right">
              <Link
                target="_blank"
                to={`/${selectedTab}`}
                onClick={() =>
                  trackGoToLinks(selectedTabTitle, this.props.sectionTitle)
                }
              >
                View all {selectedTabTitle} <i className="i i-chevron-right" />
              </Link>
            </div>
          </div>
        </PanelFooter>
      </GenericPanel>
    );
  }
}
