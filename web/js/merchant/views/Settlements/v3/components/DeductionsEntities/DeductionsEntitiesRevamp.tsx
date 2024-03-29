import { ChevronDownIcon, ChevronUpIcon, Spinner, Text } from '@razorpay/blade/components';
import { useMobile } from 'common/hooks/useMobile';
import { removeUnreconciledEntity } from 'merchant/views/Settlements/v2/util';
import { StyledEntityContainer } from 'merchant/views/Settlements/v3/components/DeductionsEntities/styled';
import EntityListNew from 'merchant/views/Settlements/v3/components/EntityList/EntityList';
import EntityTabs from 'merchant/views/Settlements/v3/components/EntityTabs';
import {
  CollapsibleContainer,
  SectionHeader,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';

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
      <StyledEntityContainer data-testid="settlements-deductions-entities">
        <SectionHeader enableBorderBottomRadius={!isOpen}>
          <Text weight="semibold" size="large" color="surface.text.gray.normal">
            Deductions
          </Text>
          {isMobile && (
            <CollapsibleContainer onClick={toggleAccordian} data-testid="collapsible-container">
              <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
                {!isOpen ? (
                  <ChevronDownIcon
                    size="medium"
                    color="feedback.icon.neutral.intense"
                    data-testid="chevron-down"
                  />
                ) : (
                  <ChevronUpIcon
                    size="medium"
                    color="feedback.icon.neutral.intense"
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
              sectionType="deductions"
              entityType="debit"
              isDetailsRevampFlow={true}
            />
            <EntityListNew
              entityType="debit"
              sectionType="deductions"
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

export default connect(mapStateToProps, null)(DeductionsEntities);
