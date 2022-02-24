import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import {
  fetchTransfers,
  fetchReversals,
  fetchSettlements,
} from 'merchantLA/reducers/collection';
import { titleCase } from 'common/utils/rzp-utils';

import GenericPanel, {
  PanelBody,
  PanelTopbar,
  PanelFooter,
} from 'merchantLA/components/Home/GenericPanel';
import { tabs, tabsMeta } from './data';

import { trackTabClick, trackEntityClick, trackGoToLinks } from './ga';

const shouldDisplayCompact = windowWidth => {
  return windowWidth < 480;
};

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
      transfers: state.transfers,
      reversals: state.reversals,
      settlements: state.settlements,
      windowWidth: state.app.windowWidth,
    };
  },
  {
    fetchTransfers,
    fetchReversals,
    fetchSettlements,
  }
)
export default class RecentActivity extends Component {
  constructor(props) {
    super(props);

    this.state = {
      selectedTab: tabs[0],
      displayCompact: shouldDisplayCompact(props.windowWidth),
    };

    this.handleTabClick = ::this.handleTabClick;
  }

  handleTabClick(e) {
    e.preventDefault();

    const tabName = e.target.getAttribute('name');

    this.setState({ selectedTab: tabName });
    trackTabClick(titleCase(tabName), this.props.sectionTitle);
  }

  handleResize(props = this.props) {
    this.setState({
      displayCompact: shouldDisplayCompact(props.windowWidth),
    });
  }

  fetchData(params) {
    this.props.fetchTransfers(params).then(data => {
      return (
        this.props.onFetchTransfers &&
        this.props.onFetchTransfers(data && data.data)
      );
    });
    this.props.fetchReversals(params);
    this.props.fetchSettlements(params);
  }

  UNSAFE_componentWillMount() {
    this.fetchData({ count: 5 });
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.windowWidth !== nextProps.windowWidth) {
      this.handleResize(nextProps);
    }
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
        className={`recent-activity-cont${displayCompact ? ' compact' : ''}`}
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
