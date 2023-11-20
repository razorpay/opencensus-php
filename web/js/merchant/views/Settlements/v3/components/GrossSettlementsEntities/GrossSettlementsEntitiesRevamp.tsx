import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import EntityTabs from 'merchant/views/Settlements/v3/components/EntityTabs';
import { removeUnreconciledEntity } from 'merchant/views/Settlements/v2/util';
import EntityListNew from 'merchant/views/Settlements/v3/components/EntityList/EntityList';
import { Heading, Spinner, Text, ChevronDownIcon, ChevronUpIcon } from '@razorpay/blade/components';
import { StyledEntityContainer } from './Styled';
import {
  CollapsibleContainer,
  SectionHeader,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { useMobile } from 'common/hooks/useMobile';

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

  const [isOpen, setIsOpen] = useState<boolean>(true);
  const toggleAccordian = () => {
    setIsOpen((prevState) => !prevState);
  };
  const isMobile = useMobile();

  useEffect(() => {
    // if device type changes to desktop, ensure isOpen is reset to true
    if (!isMobile) {
      setIsOpen(true);
    }
  }, [isMobile]);
  if (error) return null;

  if (!loading && isNoDataFound) {
    return (
      <div className="no-transactions-data">No transactions were detected for this settlement</div>
    );
  }

  if (activeTab) {
    return (
      <StyledEntityContainer data-testid="settlements-gross-entities">
        <SectionHeader enableBorderBottomRadius={!isOpen}>
          <Heading type="normal" size="small" weight="bold" contrast="low">
            Gross Settlements
          </Heading>
          {isMobile && (
            <CollapsibleContainer onClick={toggleAccordian} data-testid="collapsible-container">
              <Text type="subtle" size="medium" weight="bold">
                {!isOpen ? (
                  <ChevronDownIcon
                    size="medium"
                    color="feedback.icon.neutral.lowContrast"
                    data-testid="chevron-down"
                  />
                ) : (
                  <ChevronUpIcon
                    size="medium"
                    color="feedback.icon.neutral.lowContrast"
                    data-testid="chevron-up"
                  />
                )}
              </Text>
            </CollapsibleContainer>
          )}
        </SectionHeader>
        {isOpen ? (
          <>
            <EntityTabs
              breakupDetails={props.breakupDetails}
              handleTabChange={handleTabChange}
              activeTab={activeTab}
              sectionType="gross_settlements"
              entityType="credit"
              isDetailsRevampFlow={true}
            />
            <EntityListNew
              entityType="credit"
              sectionType="gross_settlements"
              breakupDetails={props.breakupDetails}
              activeTab={activeTab}
              settlementId={props.settlementId}
              isDetailsRevampFlow={true}
            />
          </>
        ) : null}
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
