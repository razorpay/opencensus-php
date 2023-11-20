import React, { useEffect, useState } from 'react';
import {
  Box,
  Card,
  CardBody,
  ChevronDownIcon,
  ChevronUpIcon,
  Divider,
  Heading,
  Text,
} from '@razorpay/blade/components';
import {
  CardWrapper,
  CollapsibleContainer,
  CopyWrapper,
  RowsWrapper,
  RowWrapper,
  SectionHeader,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { useMobile } from 'common/hooks/useMobile';
import copyToClipboard from 'common/utils/copyToClipboard';
import Tooltip from 'merchant/views/Settlements/v3/components/Tooltip';
import { connect } from 'react-redux';
import { SettlementPropsInterface } from 'merchant/views/Settlements/v3/typings';
import { tooltipContent } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/constants';

const SettlementInfo = ({ settlement }: { settlement: SettlementPropsInterface }) => {
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
  return (
    <Box testID="settlement-info-details-section">
      <SectionHeader enableBorderBottomRadius={!isOpen}>
        <Heading type="normal" size="small" weight="bold" contrast="low">
          Details
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
        <CardWrapper enableBorderBottomRadius>
          <Card padding="spacing.5" elevation="none">
            <CardBody>
              <RowsWrapper>
                <RowWrapper tooltipSpacing="5px">
                  <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                    Settlement ID <Tooltip content={tooltipContent.settlementId} />
                  </Text>
                  <CopyWrapper onClick={() => copyToClipboard(settlement.id)}>
                    <Text type="normal" variant="body" size="medium" weight="bold" contrast="low">
                      {settlement.id}
                    </Text>
                  </CopyWrapper>
                </RowWrapper>
                <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
                <RowWrapper tooltipSpacing="5px">
                  <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                    UTR number <Tooltip content={tooltipContent.bankRRN} />
                  </Text>
                  {settlement.utr ? (
                    <CopyWrapper onClick={() => copyToClipboard(settlement.utr)}>
                      <Text variant="body">{settlement.utr}</Text>
                    </CopyWrapper>
                  ) : (
                    <Text variant="body" color="surface.text.muted.lowContrast">
                      generated after settlement gets processed
                    </Text>
                  )}
                </RowWrapper>
              </RowsWrapper>
            </CardBody>
          </Card>
        </CardWrapper>
      ) : null}
    </Box>
  );
};

const mapStateToProps = (state) => {
  const { settlement } = state;
  return {
    settlement: settlement.settlement,
  };
};

export default connect(mapStateToProps, null)(SettlementInfo);
