import React, { useEffect, useState } from 'react';
import {
  Box,
  Card,
  CardBody,
  ChevronDownIcon,
  ChevronUpIcon,
  Divider,
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
import { trackSettlmentDetailsCopied } from 'merchant/views/Settlements/v3/utils/common';
import { getTooltipContent } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/constants';

const SettlementInfo = ({
  orgName,
  settlement,
  orgId,
}: {
  orgName: string;
  settlement: SettlementPropsInterface;
  orgId: string;
}) => {
  const [isOpen, setIsOpen] = useState<boolean>(true);
  const toggleAccordian = () => {
    setIsOpen((prevState) => !prevState);
  };
  const isMobile = useMobile();
  // Org id for HDFC Collect Now
  const isOrgHdfcCollectNow = orgId === 'org_ISCBolfdHQnhj4';

  useEffect(() => {
    // if device type changes to desktop, ensure isOpen is reset to true
    if (!isMobile) {
      setIsOpen(true);
    }
  }, [isMobile]);
  return (
    <Box testID="settlement-info-details-section">
      <SectionHeader enableBorderBottomRadius={!isOpen}>
        <Text weight="semibold" size="large" color="surface.text.gray.normal">
          Details
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
        <CardWrapper enableBorderBottomRadius>
          <Card padding="spacing.5" elevation="none">
            <CardBody>
              <RowsWrapper>
                <RowWrapper tooltipSpacing="5px">
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Settlement ID <Tooltip content={getTooltipContent(orgName).settlementId} />
                  </Text>
                  <CopyWrapper
                    onClick={() => {
                      trackSettlmentDetailsCopied({
                        type: 'ID',
                      });
                      copyToClipboard(settlement.id);
                    }}
                  >
                    <Text
                      variant="body"
                      size="medium"
                      weight="semibold"
                      color="surface.text.gray.normal"
                    >
                      {settlement.id}
                    </Text>
                  </CopyWrapper>
                </RowWrapper>
                {isOrgHdfcCollectNow ? null : (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <RowWrapper tooltipSpacing="5px">
                      <Text
                        variant="body"
                        size="medium"
                        weight="regular"
                        color="surface.text.gray.subtle"
                      >
                        UTR number <Tooltip content={getTooltipContent(orgName).bankRRN} />
                      </Text>
                      {settlement.utr ? (
                        <CopyWrapper
                          onClick={() => {
                            trackSettlmentDetailsCopied({
                              type: 'UTR number',
                            });
                            copyToClipboard(settlement.utr);
                          }}
                        >
                          <Text variant="body">{settlement.utr}</Text>
                        </CopyWrapper>
                      ) : (
                        <Text variant="body" color="surface.text.gray.muted">
                          generated after settlement gets processed
                        </Text>
                      )}
                    </RowWrapper>
                  </>
                )}
              </RowsWrapper>
            </CardBody>
          </Card>
        </CardWrapper>
      ) : null}
    </Box>
  );
};

const mapStateToProps = (state) => {
  const { session, settlement } = state;
  return {
    orgName: session.org?.business_name,
    orgId: session.org?.id,
    settlement: settlement.settlement,
  };
};

export default connect(mapStateToProps, null)(SettlementInfo);
