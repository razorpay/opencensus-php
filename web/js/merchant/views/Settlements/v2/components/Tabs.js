import React from 'react';
import { sanitizeTabName, removeUnreconciledEntity } from 'merchant/views/Settlements/v2/util';
import { titleCase } from 'common/utils/rzp-utils';
import { connect } from 'react-redux';

const Tabs = (props) => {
  let { items } = props.breakupDetails;
  const { activeTab } = props;

  const renderTabNames = () => {
    items = removeUnreconciledEntity(items);

    const tabNamesObj = items.reduce((acc, tab) => {
      if (!tab.count) return acc;

      const tabName = sanitizeTabName(tab.component);
      if (!acc[tabName]) {
        acc[tabName] = tab.count;
      } else {
        acc[tabName] = acc[tabName] + tab.count;
      }

      return acc;
    }, {});

    return Object.keys(tabNamesObj).map((tabKey, index) => {
      return (
        <div className={`tab ${tabKey === sanitizeTabName(activeTab) ? 'active' : ''}`} key={index}>
          <span id="source_type" onClick={props.handleTabChange}>
            {titleCase(tabKey)}{' '}
          </span>
          <b className="count">({tabNamesObj[tabKey]})</b>
        </div>
      );
    });
  };

  return <div className="entity-tabs">{renderTabNames()}</div>;
};

const mapStateToProps = (state) => ({ user: state.session.user });

export default connect(mapStateToProps, null)(Tabs);
