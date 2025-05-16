import React from 'react'
import { Box, Heading, Text, Divider, BoxProps } from "@razorpay/blade/components";

import { DividerWrapper } from "merchant/components/SelfServeRekyc/styledComponents/StepIndicator";
import { RekycStepProps } from 'merchant/components/SelfServeRekyc/types';

const Step = (props: RekycStepProps): JSX.Element => {
  const { isMobile, stepInfo, showDivider } = props;

  const {IconComponent} = stepInfo;

  const isCurrentStepOnMobile = isMobile && stepInfo.currentStep;
  const isCompletedStep = stepInfo.completed;
  const isCurrentStepOnDesktop = !isMobile && stepInfo.currentStep;

  const backgroundColor = (isCurrentStepOnMobile || isCompletedStep) ? stepInfo.iconBackgroundColor : (isCurrentStepOnDesktop ? 'surface.background.primary.intense' : "transparent");
  return (
    <>
      <Box display="flex" flexDirection="column" alignItems={isMobile ? 'flex-start' : "center"} gap='spacing.3' width='88px'>
        <Box
          display="flex"
          alignItems="center"
          justifyContent="center"
          width="32px"
          height="32px"
          borderRadius="round"
          backgroundColor={backgroundColor as BoxProps['backgroundColor']}
          borderWidth={isCurrentStepOnMobile || isCompletedStep ? 'none' : "thin"}
          borderStyle="solid"
          borderColor={stepInfo.borderColor ? (stepInfo.borderColor as BoxProps['borderColor']) : 'surface.border.primary.normal'}
        >
          {
            (isCurrentStepOnMobile || isCompletedStep) ?
            <IconComponent size="large" color={stepInfo.iconColor}/>:
            <Heading
              size="small"
              weight="semibold"
              color={stepInfo.textColor}
            >
              {stepInfo.textContent}
            </Heading>
          }
        </Box>
        <Text
          size="small"
          weight="medium"
          color={stepInfo.headingColor}
        >
          {stepInfo.heading}
        </Text>
      </Box>
      {
        showDivider ?
        <DividerWrapper isMobile={isMobile}>
          <Divider
            orientation='vertical'
            dividerStyle="dashed"
            thickness='thin'
            height="60px"
            marginX="spacing.4"
            variant='normal'
          />
        </DividerWrapper> : null
      }
    </>
  )
}

export default Step