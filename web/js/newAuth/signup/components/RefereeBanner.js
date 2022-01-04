import React, { useEffect, useState } from 'react';
import { DesktopOnlyView, MobileOnlyView } from '../../commonStyles';
import View from '@razorpay/blade-old/src/atoms/View';
import Text from '@razorpay/blade-old/src/atoms/Text';
import styled from 'styled-components';
import ajax from 'common/utils/ajax';
import { getFormattedAmount, getURLQueryParams } from '../../utils';

const RefereeBannerContainerView = styled(View)`
  height: 50px;
`;

const RefereeBannerDesktopView = styled(View)`
  position: absolute;
  padding: 10px 28px 10px 357px;
  background: #1fca80;
  right: 387px;
  top: 15px;
  left: -3px;
  right: -3px;
  display: flex;
  flex-flow: column;
  justify-content: center;
  align-items: center;
`;

const RefereeBannerMobileView = styled(View)`
  background: #1fca80;
  padding: 12px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
`;

const RefereeBanner = () => {
  const [referralAmount, setReferralAmount] = useState(0);
  let showRefereeBanner = false;
  const params = getURLQueryParams(location.search);
  if (
    params.utm_source === 'friendbuy' &&
    params.referralCode &&
    params.utm_medium === 'referral'
  ) {
    showRefereeBanner = true;
  }

  useEffect(() => {
    ajax({
      url: '/user/api/live/m2m_referral',
    })
      .then((data) => {
        setReferralAmount(data?.data?.referee?.referral_amount);
      })
      .catch((err) => {
        console.log(err);
      });
  }, []);

  if (showRefereeBanner && referralAmount) {
    return (
      <>
        <MobileOnlyView>
          <RefereeBannerMobileView>
            <Text size="xsmall" color="background.100" as="div">
              You have been invited to try out Razorpay
            </Text>
            <Text size="medium" color="background.100" as="div">
              Complete your signup and receive {getFormattedAmount(referralAmount, true)} in
              collections - 100% FREE!*
            </Text>
          </RefereeBannerMobileView>
        </MobileOnlyView>
        <DesktopOnlyView>
          <RefereeBannerContainerView>
            <RefereeBannerDesktopView>
              <Text size="xsmall" color="background.100" as="div">
                Hi! Your friend believes your business can benefit from using our payments
                solutions.
              </Text>
              <Text size="medium" color="background.100" as="div">
                Complete your signup and receive {getFormattedAmount(referralAmount, true)} in
                collections - 100% FREE!*
              </Text>
            </RefereeBannerDesktopView>
          </RefereeBannerContainerView>
        </DesktopOnlyView>
      </>
    );
  }
  return null;
};

export default RefereeBanner;
