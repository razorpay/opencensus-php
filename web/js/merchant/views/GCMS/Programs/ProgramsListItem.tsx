import React, { memo } from 'react';
import { Box, Card, CardBody, Divider, Text } from '@razorpay/blade/components';
import { truncatedString } from 'common/utils/rzp-utils';
//import { StyledProgramTypeContainer } from 'merchant/views/GCMS/Programs/styled';
import { Program } from 'merchant/views/GCMS/Programs/types';
//import { PROGRAM_TYPES } from 'merchant/views/GCMS/shared/constants';
import { daysToMonths, getProgramDenomination } from 'merchant/views/GCMS/shared/utils';

type Props = {
  program: Program;
  onClick: () => void;
};

const ProgramsListItem: React.FC<Props> = ({ program, onClick }) => {
  // const programType =
  //   Object.keys(PROGRAM_TYPES).find((key) => PROGRAM_TYPES[key].id === program.type) || 'VOUCHER';

  return (
    <Box paddingX="0px" paddingY="spacing.3" flexBasis="25%">
      <Card
        onClick={onClick}
        accessibilityLabel="GCMS Programs Card"
        elevation="midRaised"
        onHover={function noRefCheck() {}}
        shouldScaleOnHover
        width={{
          m: '272px',
          s: '100%',
        }}
        height="382px"
        padding="spacing.5"
        borderRadius="xlarge"
      >
        <CardBody>
          <Box width="240px" height="140px">
            <img src={program.policies?.image_link} width="100%" height="100%" alt={program.name} />
          </Box>
          <Box paddingTop="spacing.4">
            <Box>
              <Text weight="semibold" size="large">
                {program.name}
              </Text>
            </Box>
            <Box display="flex" flexDirection="row" alignItems="center" paddingTop="spacing.2">
              <Box>
                <Text color="surface.text.gray.muted">
                  {truncatedString(program.policies?.program_category, 18)}
                </Text>
              </Box>
              {/* <StyledProgramTypeContainer
                backgroundColor={PROGRAM_TYPES[programType as keyof typeof PROGRAM_TYPES].color}
              >
                <Text color="interactive.text.staticWhite.normal" weight="semibold" size="small">
                  {capitalize(PROGRAM_TYPES[programType as keyof typeof PROGRAM_TYPES].name)}
                </Text>
              </StyledProgramTypeContainer> */}
            </Box>
            <Box paddingTop="spacing.5">
              <Divider />
            </Box>
            <Box
              paddingTop="spacing.5"
              display="flex"
              flexDirection="row"
              alignItems="center"
              justifyContent="space-between"
              gap="8px"
            >
              <Box paddingTop="spacing.2" height="38px" width="116px">
                <Box>
                  <Text color="surface.text.gray.muted">Validity</Text>
                </Box>
                <Box paddingTop="spacing.1">
                  <Text>
                    {program.policies?.gift_card_validity_in_days
                      ? program.policies?.gift_card_validity_in_days > 30
                        ? `${daysToMonths(program.policies?.gift_card_validity_in_days)} months`
                        : `${program.policies?.gift_card_validity_in_days} days`
                      : '-'}
                  </Text>
                </Box>
              </Box>
              <Box paddingTop="spacing.2" height="38px" width="116px">
                <Box>
                  <Text color="surface.text.gray.muted">Denomination</Text>
                </Box>
                <Box paddingTop="spacing.1">
                  <Text>{getProgramDenomination({ policy: program.policies })}</Text>
                </Box>
              </Box>
            </Box>
            <Box paddingTop="spacing.5" height="38px" width="240px">
              <Box>
                <Text color="surface.text.gray.muted">Reseller Discount</Text>
              </Box>
              <Box paddingTop="spacing.1">
                <Text>{'5%'}</Text>
              </Box>
            </Box>
          </Box>
        </CardBody>
      </Card>
    </Box>
  );
};

export default memo(ProgramsListItem);
