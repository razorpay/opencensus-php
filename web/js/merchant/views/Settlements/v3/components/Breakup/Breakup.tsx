import { Box, ChevronDownIcon, Heading, InfoIcon, Text } from '@razorpay/blade/components';
import Collapsible from 'common/components/Collapsible';
import { User } from 'common/typings';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Amount from 'merchant/views/Settlements/v3/components/Amount';
import { CollapsibleIcon } from 'merchant/views/Settlements/v3/components/FAQs/styled';
import { BreakupDetailsInterface } from 'merchant/views/Settlements/v3/typings';
import React, { useState } from 'react';
import { connect } from 'react-redux';
import BreakupShimmer from './Shimmer';
import { getBreakUpDetails } from './config';
import { BreakupHeader, StyledBox, StyledDivider } from './styled';

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

  if (loading) return <BreakupShimmer />;
  if (error) return null;
  return (
    <Box
      display="flex"
      width="100%"
      flexDirection="column"
      flex="1.8"
      backgroundColor="surface.background.gray.intense"
      minWidth={{ base: 'spacing.0', m: '410px' }}
    >
      <BreakupHeader>
        <Heading weight="semibold" size="small">
          Breakup
        </Heading>
      </BreakupHeader>
      <Box
        display="flex"
        flexDirection="column"
        padding={{ base: '14px', m: ['spacing.7', '57px', 'spacing.8', 'spacing.7'] }}
      >
        <Box display="flex" justifyContent="space-between" alignItems="center">
          <StyledBox onClick={toggleDrawer.bind(null, 'gross')}>
            <Box display="flex" alignItems="center" gap={{ base: '5px' }}>
              <Text size="medium" weight="semibold" color="feedback.text.positive.intense">
                Gross settlement
              </Text>
              <CollapsibleIcon open={collapseState.gross}>
                <ChevronDownIcon size="medium" color="feedback.icon.neutral.intense" />
              </CollapsibleIcon>
            </Box>
          </StyledBox>
          <Amount
            amount={grossSettlements.amount}
            type="breakup"
            color="feedback.text.positive.intense"
            currency={currency}
            operator="+"
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
                    <Text size="medium" color="surface.text.gray.subtle">
                      {each.name}
                    </Text>
                    <div>
                      <InfoIcon size="small" color="feedback.icon.neutral.intense" />
                      {each.tooltipInfo && (
                        <Popover theme="dark" align="top">
                          <PopoverBody>
                            <div>{each.tooltipInfo}</div>
                          </PopoverBody>
                        </Popover>
                      )}
                    </div>
                  </Box>
                  <Amount amount={each.amount} type="subBreakup" currency={currency} />
                </Box>
              ),
            )}
          </Box>
        </Collapsible>
        {deductions ? (
          <>
            <StyledDivider />
            <Box display="flex" justifyContent="space-between" alignItems="center">
              <StyledBox onClick={toggleDrawer.bind(null, 'deduction')}>
                <Box display="flex" alignItems="center" gap={{ base: '5px' }}>
                  <Text size="medium" weight="semibold" color="feedback.text.negative.intense">
                    Deductions
                  </Text>
                  <CollapsibleIcon open={collapseState.deduction}>
                    <ChevronDownIcon size="medium" color="feedback.icon.neutral.intense" />
                  </CollapsibleIcon>
                </Box>
              </StyledBox>
              <Amount
                amount={deductions.amount}
                type="breakup"
                color="feedback.text.negative.intense"
                currency={currency}
                operator="-"
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
                        <Text size="medium" color="surface.text.gray.subtle">
                          {each.name}
                        </Text>
                        <div>
                          <InfoIcon size="small" color="feedback.icon.neutral.intense" />
                          {each.tooltipInfo && (
                            <Popover theme="dark" align="top">
                              <PopoverBody>
                                <div>{each.tooltipInfo}</div>
                              </PopoverBody>
                            </Popover>
                          )}
                        </div>{' '}
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
        <StyledDivider />
        <Box
          display="flex"
          justifyContent="space-between"
          alignItems="center"
          marginTop={{ base: 'spacing.3' }}
        >
          <Text size="medium" weight="semibold">
            Net settlement
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
