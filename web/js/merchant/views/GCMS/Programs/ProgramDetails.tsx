import React, { memo } from 'react';
import { Box, Text, Skeleton } from '@razorpay/blade/components';

import { capitalize } from 'common/utils/rzp-utils';
import {
  getProgramContentSections,
  getProgramGiftCardConfiguration,
  getProgramGiftCardDetails,
} from 'merchant/views/GCMS/Programs/constants';

import { DEFAULT_IMAGE } from '../shared/constants';
import { Program } from './types';

const ProgramDetails = ({
  program,
  isImageLoading,
  programImage,
}: {
  program: Program;
  isImageLoading: boolean;
  programImage: string;
}) => {
  const sections = [
    {
      title: 'Program Details',
      values: getProgramContentSections(program),
    },
    {
      title: 'Gift Card Details',
      values: getProgramGiftCardDetails(program),
    },
    {
      title: 'Gift Card Number Configuration',
      values: getProgramGiftCardConfiguration(program),
    },
  ];

  function getImage() {
    const {
      policies: { gift_card_file_storage_id },
    } = program;
    if (gift_card_file_storage_id) {
      if (programImage) {
        return (
          <img
            src={programImage[1]}
            width="100%"
            height="100%"
            style={{ 'object-fit': 'contain' }}
            alt={program.name}
          />
        );
      } else {
        return <Skeleton borderRadius="medium" height="100%" width="100%" />;
      }
    } else {
      return (
        <img
          src={DEFAULT_IMAGE}
          width="100%"
          height="100%"
          alt={program.name}
          style={{ 'object-fit': 'contain' }}
        />
      );
    }
  }

  function renderSection(section) {
    return (
      <Box width="100%">
        <Box>
          <Text weight="semibold" size="large">
            {section.title}
          </Text>
        </Box>
        <Box paddingTop="spacing.1" gap="spacing.1">
          {section.values.map((section) => (
            <Box key={section.name} display="flex" flexDirection="row" paddingTop="spacing.4">
              <Box minWidth="180px">
                <Text color="surface.text.gray.subtle">{section.name + ':'}</Text>
              </Box>
              <Box>
                <Text color="surface.text.gray.subtle">
                  {typeof section.value === 'string' ? capitalize(section.value) : section.value}
                </Text>
              </Box>
            </Box>
          ))}
        </Box>
      </Box>
    );
  }

  return (
    <Box padding="spacing.5" backgroundColor="#F9FAFC" borderRadius="large">
      <Box display="flex" flexDirection="row" flexWrap="wrap-reverse">
        <Box width="100%" minWidth="464px" flexDirection="row" display="flex">
          <Box>
            <Box padding={['spacing.0', 'spacing.0', 'spacing.5', 'spacing.0']}>
              <Box
                // width="350px"
                height="190px"
                elevation="highRaised"
                borderRadius={'medium'}
                overflow="hidden"
              >
                {getImage()}
              </Box>
            </Box>
          </Box>
          <Box display="flex" flexDirection="column" gap="spacing.6" marginLeft="32px">
            {sections.map((section) => renderSection(section))}
          </Box>
        </Box>
      </Box>
    </Box>
  );
};

export default memo(ProgramDetails);
