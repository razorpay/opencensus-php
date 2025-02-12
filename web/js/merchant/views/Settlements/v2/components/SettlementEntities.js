import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import Tabs from './Tabs';
import EntityList from './EntityList';
import Spinner from 'common/ui/Spinner';
import LoaderDots from 'common/ui/LoaderDots';
import { removeUnreconciledEntity } from 'merchant/views/Settlements/v2/util';

const SettlementEntities = (props) => {
  const [activeTab, setactiveTab] = useState(null);
  const [noDataFound, setNoDataFound] = useState(false);

  const { items, error, loading } = props?.breakupDetails;

  useEffect(() => {
    // sets the first item as active tab by default
    const ENTITY_ITEMS = removeUnreconciledEntity(items);
    if (ENTITY_ITEMS?.length > 0) {
      setactiveTab(ENTITY_ITEMS[0].component);
      setNoDataFound(false);
    } else {
      setNoDataFound(true);
    }
  }, [items]);

  const handleTabChange = (e) => setactiveTab(e?.currentTarget?.textContent?.toLowerCase());

  if (error) return null;

  if (!loading && noDataFound) {
    return (
      <div className="no-transactions-data">No transactions were detected for this settlement</div>
    );
  }

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
          currency={props.currency}
        />
      ) : (
        <div className="div--loading">
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
