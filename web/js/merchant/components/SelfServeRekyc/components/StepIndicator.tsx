import React from 'react';
import { Box } from "@razorpay/blade/components";

import Step from 'merchant/components/SelfServeRekyc/components/Step';

import { useMobile } from 'common/hooks/useMobile';

import { MOBILE_BREAKPOINTS } from 'merchant/components/SelfServeRekyc/constants';

const StepIndicator = (props) => {
  const isMobile = useMobile(MOBILE_BREAKPOINTS);

  const {
    stepsInfo
  } = props;

  return (
    <Box display="flex" alignItems="center">
      {
        Object.keys(stepsInfo).map((step, idx) => {
          return <Step key={step} isMobile={isMobile} stepInfo={stepsInfo[step]} showDivider={idx !== 2}/>
        })
      }
    </Box>
  );
}

export default StepIndicator;
