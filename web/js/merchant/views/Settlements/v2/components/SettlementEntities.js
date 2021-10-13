import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import Tabs from './Tabs';
import EntityList from './EntityList';
import Spinner from 'common/ui/Spinner';
import LoaderDots from 'common/ui/LoaderDots';

const SettlementEntities = (props) => {
  const [activeTab, setactiveTab] = useState(null);

  useEffect(() => {
    // sets the first item as active tab by default
    if (props.breakupDetails.items.length > 0)
      setactiveTab(props.breakupDetails.items[0].component);
  }, [props.breakupDetails]);

  const handleTabChange = (e) => setactiveTab(e.currentTarget.textContent.toLowerCase());

  if (props.breakupDetails.error) return null;

  return (
    <React.Fragment>
      {activeTab ? (
        <Tabs
          breakupDetails={props.breakupDetails}
          handleTabChange={handleTabChange}
          activeTab={activeTab}
        />
      ) : (
        <LoaderDots />
      )}
      {activeTab ? (
        <EntityList
          breakupDetails={props.breakupDetails}
          activeTab={activeTab}
          settlementId={props.settlementId}
        />
      ) : (
        <div class="div--loading">
          <Spinner />
        </div>
      )}
    </React.Fragment>
  );
};

const mapStateToProps = (state) => {
  return {
    breakupDetails: state.settlement.breakupDetails,
  };
};

export default connect(mapStateToProps, null)(SettlementEntities);
