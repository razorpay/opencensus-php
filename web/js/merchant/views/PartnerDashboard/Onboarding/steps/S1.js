import React, { useState } from 'react';
import SlideContoller from './SlideController';
import { Box, Heading, List, ListItem } from '@razorpay/blade/components';
import TnCFooter from 'merchant/views/PartnerDashboard/Onboarding/steps/TnCFooter';
import { useI18Service } from 'common/i18';

const S1 = ({
  screenName,
  orgDetails,
  sliderProps,
  isLastStep,
  onNext,
  handleOtherCTAClicks,
  onCompleteClick,
}) => {
  const { isConfigTagEnabled } = useI18Service();
  const [isLoading, setIsLoading] = useState(false);

  const onSignupClick = () => {
    setIsLoading(true);
    onCompleteClick()?.finally?.(() => {
      setIsLoading(false);
    });
  };

  return (
    <Box display="flex" flexDirection="column" height="380px">
      <Box flex="1">
        <Heading size="large" weight="medium" wordBreak="break-word">
          Awesome, Join the Razorpay Partner
          <br /> Program and:
        </Heading>
        {/* <ShowWhen additionalCondition={() => !isConfigTagEnabled('partnership.partner_commission')}>
          <div className="line-box brd-primary">
            <p className="info info-green">Get 0.1% commission on all your referrals.</p>
          </div>
        </ShowWhen> */}
        <List variant="ordered" size="large" marginTop="spacing.7" marginBottom="spacing.8">
          <ListItem>Delight clients with Razorpay's powerful tech stack</ListItem>
          <ListItem>Unlock an additional revenue stream</ListItem>
          <ListItem>Onboard clients faster with partner tools & support</ListItem>
          <ListItem>Boost your brand with co-marketing opportunities</ListItem>
          <ListItem>Track your referrals via exclusive partner dashboard</ListItem>
        </List>

        <TnCFooter
          orgDetails={orgDetails}
          handleOtherCTAClicks={handleOtherCTAClicks}
          screenName={screenName}
        />
      </Box>

      <Box>
        {isLastStep ? (
          <SlideContoller
            sliderProps={sliderProps}
            onNext={onSignupClick}
            nextBtnLabel="Sign up as a Partner"
            isLoading={isLoading}
          />
        ) : (
          <SlideContoller sliderProps={sliderProps} onNext={onNext} />
        )}
      </Box>
    </Box>
  );
};

export default S1;
