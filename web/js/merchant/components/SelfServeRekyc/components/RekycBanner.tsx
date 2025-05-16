import React, { useEffect } from 'react'
import { Box, Text, Button, HelpCircleIcon, Link, Heading, BoxProps, useTheme } from '@razorpay/blade/components'

import StepIndicator from 'merchant/components/SelfServeRekyc/components/StepIndicator'
import BadgeComponent from 'merchant/components/SelfServeRekyc/components/Badge'

import { useMobile } from 'common/hooks/useMobile';

import { analyticsTrack, openTicketModal, openUrlInNewTab, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import { RekycBannerProps } from 'merchant/components/SelfServeRekyc/types'

import { MOBILE_BREAKPOINTS } from 'merchant/components/SelfServeRekyc/constants';
import { RekycBannerImageWrapper } from '../styledComponents/RekycBanner';

import { REKYC_DOCUMENTATION_URL } from 'merchant/components/SelfServeRekyc/constants';

const RekycBanner = (props: RekycBannerProps) => {
  const isMobile = useMobile(MOBILE_BREAKPOINTS);

  const {
    bannerDetails,
    deadlineDate,
    daysFromDeadline,
    stepsInfo,
    rekycUrl,
    rekycStatus,
  } = props;

  const {theme} = useTheme();

  const {
    heading,
    description,
    IconComponent,
    showChip,
    iconColor,
    headingColor,
    iconBackgroundColor,
    ctaText,
    dynamicDate,
    hideActionables,
    hideTimeline,
    chipType,
    dynamicDescription,
    imageSrc,
    showImageInBanner,
    hideIcon
  } = bannerDetails;

  useEffect(() => {
    analyticsTrack({
      objectName: 'self serve rekyc banner',
      actionName: 'displayed',
      screen: 'self serve rekyc banner',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
        rekycStatus: rekycStatus ?? '',
        experimentName: 'self-serve-rekyc',
      }
    });
  }, [rekycStatus]);

  const handleUpdateKycClick = (rekycUrl: string) => {
    analyticsTrack({
      objectName: 'self serve rekyc banner cta',
      actionName: 'clicked',
      screen: 'self serve rekyc banner',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
        rekycStatus: rekycStatus ?? '',
        experimentName: 'self-serve-rekyc',
        ctaText: ctaText ?? '',
        redirectionUrl: rekycUrl,
      }
    });

    if(rekycUrl=== 'contactSupport'){
      openTicketModal()
    }else {
      openUrlInNewTab(rekycUrl)
    }
  }

  const handleReadDocumentationClick = () => {
    analyticsTrack({
      objectName: 'self serve rekyc banner documentation',
      actionName: 'clicked',
      screen: 'self serve rekyc banner',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
        rekycStatus: rekycStatus ?? '',
        experimentName: 'self-serve-rekyc',
        ctaText: 'Why',
        redirectionUrl: 'dummy-url',
      }
    });

    openUrlInNewTab(REKYC_DOCUMENTATION_URL);
  }

  return (
    <Box
      borderRadius='large'
      paddingX='spacing.7'
      paddingY='spacing.8'
      display='flex'
      justifyContent='space-between'
      borderColor='surface.border.gray.muted'
      borderWidth='thin'
      backgroundColor='surface.background.gray.subtle'
      marginX={isMobile? '0px' : 'spacing.6'}
      alignItems={isMobile ? 'flex-start' : 'center'}
      flexDirection={isMobile ? 'column-reverse' : 'row'}
      gap={isMobile ? 'spacing.7' : 'spacing.9'}
      position='relative'
    >
      <Box
        display='flex'
        gap='spacing.5'
      >
        {
          IconComponent ?
          <Box display={isMobile && (!hideTimeline || hideIcon) ? 'none' : 'block'}>
            <Box
              width='spacing.8'
              height='spacing.8'
              borderRadius='round'
              backgroundColor={iconBackgroundColor as BoxProps['backgroundColor']}
              alignItems='center'
              justifyContent='center'
              display='flex'
            >
              <IconComponent size='large' color={iconColor}/>
            </Box>
          </Box> : null
        }
        <Box
          display='flex'
          flexDirection='column'
          flex='column'
          gap={isMobile ? 'spacing.7' : 'spacing.4'}
        >
          <Box
            display='flex'
            flexDirection='column'
            gap={isMobile ? 'spacing.4' : (showChip ? 'spacing.3' : 'spacing.2')}
          >
            <Box
              display={isMobile ? 'inline-flex' : 'flex'}
              gap='spacing.3'
              alignItems='baseline'
            >
              <span
              >
                <Heading
                  size='medium'
                  weight='semibold'
                  color={headingColor}
                >
                  <span>
                    {dynamicDate && (typeof heading !== 'string') ? heading(deadlineDate) : heading}
                    &nbsp; {showChip && chipType ? <BadgeComponent daysFromDeadline={daysFromDeadline} chipType={chipType}/> : null}
                  </span>
                </Heading>
              </span>
            </Box>
            <Text
              color='surface.text.gray.subtle'
              size="medium"
            >
              { dynamicDescription && (typeof description !== 'string') ? description(deadlineDate) : description}
            </Text>
          </Box>
          {
            !hideActionables ?
            <Box
              display='flex'
              gap='spacing.5'
              alignItems='center'
            >
              <Button
                size="medium"
                color='primary'
                variant='primary'
                onClick={() => handleUpdateKycClick(rekycUrl)}
              >
                {ctaText ? ctaText : 'Update KYC'}
              </Button>
              <Box display="flex" justifyContent="center">
              <Link color='primary' icon={HelpCircleIcon} iconPosition='right' onClick={handleReadDocumentationClick}>
                  Why
              </Link>
              </Box>
            </Box> : null
          }
        </Box>
      </Box>
      {!hideTimeline && stepsInfo ? <StepIndicator stepsInfo={stepsInfo}/> : null}
      {showImageInBanner && isMobile ? <Box height='96px'/> : null }
      {showImageInBanner && imageSrc ? <RekycBannerImageWrapper isMobile={isMobile} theme={theme}>
        <img src={imageSrc} alt="banner-img"/>
      </RekycBannerImageWrapper> : null }
    </Box>
  )
}

export default RekycBanner;