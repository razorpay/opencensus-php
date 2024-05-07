import { Box, BoxProps, Heading, Text, TextProps } from '@razorpay/blade/components';
import React, { memo } from 'react';

import { capitalize } from 'common/utils/rzp-utils';
import { getProgramHeaderSections } from 'merchant/views/GCMS/Programs/constants';
import { Program } from 'merchant/views/GCMS/Programs/types';

type Props = {
  program: Program;
  containerProps?: BoxProps;
  imageProps?: BoxProps;
  showOverview?: boolean;
  headingProps?: TextProps<any>;
  sectionItemProps?: BoxProps;
};

const ProgramHeaderSection: React.FC<Props> = ({
  program,
  containerProps = {},
  headingProps = {},
  imageProps = {},
  sectionItemProps = {},
  showOverview = true,
}) => {
  const headerSections = program ? getProgramHeaderSections(program) : [];

  return (
    <Box
      display="flex"
      flexDirection="row"
      alignItems="center"
      padding="spacing.6"
      borderRadius="medium"
      {...containerProps}
    >
      <Box {...imageProps}>
        <img src={program.policies?.image_link} width="100%" height="100%" alt={program.name} />
      </Box>
      <Box paddingLeft="spacing.8">
        <Box>
          <Heading weight="semibold" {...headingProps} size="medium">
            {program.name}
          </Heading>
        </Box>
        <Box
          display="flex"
          flexDirection="row"
          flexWrap="wrap"
          alignItems="center"
          justifyContent="space-between"
        >
          {showOverview ? (
            headerSections.map((section) => (
              <Box
                key={section.name}
                padding={['spacing.4', 'spacing.8', 'spacing.4', 'spacing.0']}
                display="flex"
                flexDirection="row"
                alignItems="center"
                {...sectionItemProps}
              >
                <Text color="surface.text.gray.muted">{section.name}:&nbsp;&nbsp;</Text>
                <Text color="surface.text.gray.normal" weight="semibold">
                  {capitalize(section.value)}
                </Text>
              </Box>
            ))
          ) : (
            <Box display="flex" flexDirection="row" paddingTop="spacing.2">
              <Text color="surface.text.gray.muted">Program ID:&nbsp;&nbsp;</Text>
              <Text color="surface.text.gray.muted" weight="semibold">
                {program.program_id}
              </Text>
            </Box>
          )}
        </Box>
      </Box>
    </Box>
  );
};

export default memo(ProgramHeaderSection);
