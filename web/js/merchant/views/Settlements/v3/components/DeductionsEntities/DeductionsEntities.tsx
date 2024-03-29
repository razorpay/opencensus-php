import { Heading, Spinner } from '@razorpay/blade/components';
import { removeUnreconciledEntity } from 'merchant/views/Settlements/v2/util';
import EntityListNew from 'merchant/views/Settlements/v3/components/EntityList/EntityList';
import EntityTabs from 'merchant/views/Settlements/v3/components/EntityTabs';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { StyledEntityContainer } from './styled';

const DeductionsEntities = (props) => {
  const [activeTab, setactiveTab] = useState<string | null>(null);
  const [isNoDataFound, setNoDataFound] = useState(false);

  const { items, error, loading } = props?.breakupDetails;

  useEffect(() => {
    // sets the first item as active tab by default
    const entityItems = removeUnreconciledEntity(items);
    const entityItemsDeductions = entityItems.filter((item) => item.type === 'debit');
    if (entityItemsDeductions?.length > 0) {
      setactiveTab(entityItemsDeductions[0].component);
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
        <Heading weight="semibold" size="small" color="surface.text.gray.normal">
          Deductions
        </Heading>
        <EntityTabs
          breakupDetails={props.breakupDetails}
          handleTabChange={handleTabChange}
          activeTab={activeTab}
          sectionType="deductions"
          entityType="debit"
        />
        <EntityListNew
          entityType="debit"
          sectionType="deductions"
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

export default connect(mapStateToProps, null)(DeductionsEntities);
