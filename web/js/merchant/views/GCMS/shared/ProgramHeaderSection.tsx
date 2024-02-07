import React, { memo } from 'react';
import { Box, BoxProps, Heading, Text, HeadingProps } from '@razorpay/blade/components';

import { capitalize } from 'common/utils/rzp-utils';
import { getProgramHeaderSections } from 'merchant/views/GCMS/Programs/constants';
import { Program } from 'merchant/views/GCMS/Programs/types';

type Props = {
  program: Program;
  containerProps?: BoxProps;
  imageProps?: BoxProps;
  headingProps?: HeadingProps<any>;
  sectionItemProps?: BoxProps;
};

const ProgramHeaderSection: React.FC<Props> = ({
  program,
  containerProps = {},
  headingProps = {},
  imageProps = {},
  sectionItemProps = {},
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
          <Heading size="large" weight="bold" {...headingProps}>
            {program.name}
          </Heading>
        </Box>
        <Box display="flex" flexDirection="row" alignItems="center" justifyContent="space-between">
          {headerSections.map((section) => (
            <Box
              key={section.name}
              padding={['spacing.4', 'spacing.8', 'spacing.4', 'spacing.0']}
              display="flex"
              flexDirection="row"
              alignItems="center"
              {...sectionItemProps}
            >
              <Text color="surface.text.muted.lowContrast">{section.name}:&nbsp;&nbsp;</Text>
              <Text color="surface.text.subdued.lowContrast" weight="bold">
                {capitalize(section.value)}
              </Text>
            </Box>
          ))}
        </Box>
      </Box>
    </Box>
  );
};

export default memo(ProgramHeaderSection);
