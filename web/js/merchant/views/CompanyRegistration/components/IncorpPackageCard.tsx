import React from 'react';
import { Box, CheckCircle2Icon, Heading } from '@razorpay/blade/components';
import { PACKAGES, ICORP_PACKAGE_HEADER } from '../constant';
import { IconWithTextUI } from './CompanyRegisterBanner';

const IncorpPackageCard = ({ isSmallDevice }: { isSmallDevice: boolean }) => {
  return (
    <Box
      testID="incorp-package-container"
      display={'flex'}
      flexDirection={'column'}
      justifyContent={'flex-start'}
    >
      <Heading
        size={isSmallDevice ? 'large' : 'medium'}
        marginX={'spacing.7'}
        marginY={'spacing.7'}
      >
        {ICORP_PACKAGE_HEADER}
      </Heading>
      <Box
        minHeight={'156px'}
        borderRadius={'large'}
        marginX={isSmallDevice ? 'spacing.0' : 'spacing.7'}
        padding={'spacing.8'}
        display={'grid'}
        backgroundColor={'surface.background.gray.intense'}
        gridTemplateColumns={'repeat(auto-fill, 330px)'}
        rowGap={'spacing.7'}
        justifyContent={isSmallDevice ? 'flex-start' : 'space-evenly'}
      >
        <IconWithTextUI
          Icon={CheckCircle2Icon}
          iconColor={'interactive.icon.positive.subtle'}
          textColor={'surface.text.gray.subtle'}
          LoopOverData={PACKAGES}
        />
      </Box>
    </Box>
  );
};

export default IncorpPackageCard;
