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
import { tooltipContent } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/constants';
import { trackSettlmentDetailsCopied } from 'merchant/views/Settlements/v3/utils/common';

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
                    Settlement ID <Tooltip content={tooltipContent.settlementId} />
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
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper tooltipSpacing="5px">
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    UTR number <Tooltip content={tooltipContent.bankRRN} />
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
