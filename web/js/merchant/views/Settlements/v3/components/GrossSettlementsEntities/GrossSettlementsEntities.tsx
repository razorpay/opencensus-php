import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import EntityTabs from 'merchant/views/Settlements/v3/components/EntityTabs';
import { removeUnreconciledEntity } from 'merchant/views/Settlements/v2/util';
import EntityListNew from 'merchant/views/Settlements/v3/components/EntityList/EntityList';
import { Heading, Spinner } from '@razorpay/blade/components';
import { StyledEntityContainer } from './Styled';

const GrossSettlementsEntities = (props) => {
  const [activeTab, setactiveTab] = useState<string | null>(null);
  const [isNoDataFound, setNoDataFound] = useState(false);

  const { items, error, loading } = props?.breakupDetails;

  useEffect(() => {
    // sets the first item as active tab by default
    const entityItems = removeUnreconciledEntity(items);
    const entityItemsGrossSettlements = entityItems.filter((item) => item.type === 'credit');
    if (entityItemsGrossSettlements?.length > 0) {
      setactiveTab(entityItemsGrossSettlements[0].component);
      setNoDataFound(false);
    } else {
      setNoDataFound(true);
    }
  }, [items]);

  const handleTabChange = (tabKey) => setactiveTab(tabKey);

  if (error) return null;

  if (!loading && isNoDataFound) {
    return (
      <div className="no-transactions-data">No transactions were detected for this settlement</div>
    );
  }

  if (activeTab) {
    return (
      <StyledEntityContainer>
        <Heading size="medium" weight="bold" contrast="low">
          Gross Settlements
        </Heading>
        <EntityTabs
          breakupDetails={props.breakupDetails}
          handleTabChange={handleTabChange}
          activeTab={activeTab}
          sectionType="gross_settlements"
          entityType="credit"
        />
        <EntityListNew
          entityType="credit"
          sectionType="gross_settlements"
          breakupDetails={props.breakupDetails}
          activeTab={activeTab}
          settlementId={props.settlementId}
        />
      </StyledEntityContainer>
    );
  } else {
    return <Spinner accessibilityLabel="Loading" size="medium" />;
  }
};

const mapStateToProps = (state) => {
  return {
    breakupDetails: state.settlement.breakupDetails,
  };
};

export default connect(mapStateToProps, null)(GrossSettlementsEntities);
