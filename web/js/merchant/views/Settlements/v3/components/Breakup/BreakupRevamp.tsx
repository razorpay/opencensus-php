import { Box, ChevronDownIcon, Divider, Text } from '@razorpay/blade/components';
import Collapsible from 'common/components/Collapsible';
import { User } from 'common/typings';
import Amount from 'merchant/views/Settlements/v3/components/Amount';
import { CollapsibleIcon } from 'merchant/views/Settlements/v3/components/FAQs/styled';
import { BreakupDetailsInterface } from 'merchant/views/Settlements/v3/typings';
import React, { useState } from 'react';
import { connect } from 'react-redux';
import { getBreakUpDetails } from './config';
import { BreakupRevampShimmer } from './Shimmer';
import { StyledBox } from './styled';
import { DashedDividerWrapper } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import Tooltip from 'merchant/views/Settlements/v3/components/Tooltip';

const Breakup = ({
  breakupDetails: { items, isBreakupNew, loading, error },
  user: {
    merchant: { currency },
  },
}: {
  breakupDetails: BreakupDetailsInterface;
  user: Required<User>;
}): JSX.Element | null => {
  const [collapseState, setCollapseState] = useState<{ [key: string]: boolean }>({
    gross: true,
    deduction: true,
  });
  const { grossSettlements, deductions, netSettlements } = getBreakUpDetails({
    items,
    isBreakupNew,
  });
  const toggleDrawer = (type) =>
    setCollapseState((prevState) => ({ ...prevState, [type]: !prevState[type] }));

  if (loading) return <BreakupRevampShimmer />;
  if (error) return null;
  return (
    <Box
      display="flex"
      width="100%"
      flexDirection="column"
      flex="1.8"
      backgroundColor="surface.background.level2.lowContrast"
      minWidth={{ base: 'spacing.0', m: '410px' }}
      borderRadius="medium"
      borderColor="surface.border.normal.lowContrast"
    >
      <Box display="flex" flexDirection="column" padding="spacing.6">
        <Box display="flex" justifyContent="space-between" alignItems="center">
          <StyledBox onClick={toggleDrawer.bind(null, 'gross')}>
            <Box display="flex" alignItems="center" gap={{ base: '5px' }}>
              <Text size="medium" weight="bold" color="surface.text.subtle.lowContrast">
                Gross settlement
              </Text>
              <CollapsibleIcon open={collapseState.gross}>
                <Box display="flex" alignItems="center" justifyContent="cemter">
                  <ChevronDownIcon size="medium" color="feedback.icon.neutral.lowContrast" />
                </Box>
              </CollapsibleIcon>
            </Box>
          </StyledBox>
          <Amount
            amount={grossSettlements.amount}
            type="breakup"
            color="feedback.text.positive.lowContrast"
            currency={currency}
          />
        </Box>
        <Collapsible open={collapseState.gross}>
          <Box display="flex" flexDirection="column" gap="spacing.4" paddingTop="spacing.4">
            {grossSettlements.entries.map(
              (each, index): JSX.Element => (
                <Box
                  key={`${each.id}_${index}`}
                  display="flex"
                  justifyContent="space-between"
                  alignItems="center"
                  paddingLeft={{
                    base: 'spacing.3',
                    m: 'spacing.4',
                  }}
                >
                  <Box display="flex" alignItems="center" gap={{ base: '5px' }}>
                    <Text size="medium" type="subtle">
                      {each.name}
                    </Text>
                    <Tooltip content={each.tooltipInfo} />
                  </Box>
                  <Amount amount={each.amount} type="subBreakup" currency={currency} />
                </Box>
              ),
            )}
          </Box>
        </Collapsible>
        {deductions.amount ? (
          <>
            <DashedDividerWrapper>
              <Divider dividerStyle="dashed" marginY="spacing.4" thickness="thick" />
            </DashedDividerWrapper>
            <Box display="flex" justifyContent="space-between" alignItems="center">
              <StyledBox onClick={toggleDrawer.bind(null, 'deduction')}>
                <Box display="flex" alignItems="center" gap={{ base: '5px' }}>
                  <Text size="medium" weight="bold" color="surface.text.subtle.lowContrast">
                    Deductions
                  </Text>
                  <CollapsibleIcon open={collapseState.deduction}>
                    <Box display="flex" alignItems="center" justifyContent="cemter">
                      <ChevronDownIcon size="medium" color="feedback.icon.neutral.lowContrast" />
                    </Box>
                  </CollapsibleIcon>
                </Box>
              </StyledBox>
              <Amount
                amount={deductions.amount}
                type="breakup"
                color="feedback.text.negative.lowContrast"
                currency={currency}
              />
            </Box>
            <Collapsible open={collapseState.deduction}>
              <Box display="flex" flexDirection="column" gap="spacing.4" paddingTop="spacing.4">
                {deductions.entries?.map(
                  (each, index): JSX.Element => (
                    <Box
                      key={`${each.id}_${index}`}
                      display="flex"
                      justifyContent="space-between"
                      alignItems="center"
                      paddingLeft={{
                        base: 'spacing.3',
                        m: 'spacing.4',
                      }}
                    >
                      <Box display="flex" alignItems="center" gap={{ base: '5px' }}>
                        <Text size="medium" type="subtle">
                          {each.name}
                        </Text>
                        <Tooltip content={each.tooltipInfo} />
                      </Box>
                      <Amount amount={each.amount} type="subBreakup" currency={currency} />
                    </Box>
                  ),
                )}
              </Box>
            </Collapsible>
          </>
        ) : null}
        <Box />
        <Divider variant="normal" marginY="spacing.4" thickness="thick" />
        <Box display="flex" justifyContent="space-between" alignItems="center">
          <Text size="medium" weight="bold">
            Net settlements
          </Text>
          <Amount amount={netSettlements.amount} type="net" currency={currency} />
        </Box>
      </Box>
    </Box>
  );
};

const mapStateToProps = ({ settlement, session }) => ({
  breakupDetails: settlement.breakupDetails,
  user: session.user,
});

export default connect(mapStateToProps, null)(Breakup);
