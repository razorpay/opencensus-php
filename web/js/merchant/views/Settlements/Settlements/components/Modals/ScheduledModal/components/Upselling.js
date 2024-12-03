import React, { useState, useEffect } from 'react';
import { Box, Heading, InfoIcon, Text, Button, ArrowRightIcon } from '@razorpay/blade/components';
import { makeBorderSize } from '@razorpay/blade/utils';
import { connect } from 'react-redux';
import styled from 'styled-components';

import User from 'merchant/models/User';
import { updateSession as fnUpdateSession } from 'merchant/reducers/session';
import { useODSAutomaticPricingDiscount } from 'merchant/views/Settlements/InstantSettlements/hooks/useODSAutomaticPricingDiscount';
import { useIsManagedMerchantAccount } from 'merchant/views/Settlements/InstantSettlements/hooks/useIsManagedMerchantAccount';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import {
  trackCrossSellBannerRendered,
  trackKnowMoreClicked,
  trackEnableNowClicked,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/analytics';
import { SAMEDAY_MODAL_LOCATIONS } from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/constants';
import {
  enableAutomaticSettlements,
  setEnableEsPartialAutomaticDate,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils';
import {
  closeModal as fnCloseModal,
  openModal as fnOpenModal,
} from 'merchant_common/reducers/modals';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';

const BIG_UPSELLING_BG = '/dist/css/assets/settlements/bigupselling-bg.svg';
const SMALL_UPSELLING_BG = '/dist/css/assets/settlements/upselling-bg.svg';

const Container = styled.div`
  background-color: ${(props) => props.theme.colors.interactive.background.staticWhite.faded};
  overflow: hidden;
  border-radius: ${(props) => makeBorderSize(props.theme.border.radius.medium)};
`;

const ContainerDiscount = styled.div`
  background-color: ${(props) => props.theme.colors.interactive.background.staticWhite.highlighted};
  overflow: hidden;
  border-radius: ${(props) => makeBorderSize(props.theme.border.radius.medium)};
  display: flex;
  align-items: center;
  justify-content: center;
`;

const getScreenForTrackEvent = (from) => {
  const isScreenSettlements = window.location.pathname.includes('/settlements');

  switch (from) {
    case SAMEDAY_MODAL_LOCATIONS.ONDEMAND: {
      return isScreenSettlements
        ? 'Settlements Page || Settle Now Modal || Settlement Successful'
        : 'PG Dashboard Home || Settle Now Modal || Settlement Successful';
    }

    case SAMEDAY_MODAL_LOCATIONS.ONDEMAND_V2: {
      return isScreenSettlements
        ? 'Settlements Page || Settle Now V2 Modal || Settlement Successful'
        : 'PG Dashboard Home || Settle Now V2 Modal || Settlement Successful';
    }

    case SAMEDAY_MODAL_LOCATIONS.SETTLEMENTS_DETAILS: {
      return isScreenSettlements
        ? 'Settlements Page || Settlement Details Modal'
        : 'PG Dashboard Home || Settlement Details Modal';
    }

    default:
      return null;
  }
};

function Upselling({
  user,
  showDiscount,
  openModal,
  closeModal,
  updateSession,
  showNotification,
  trackKnowMore = () => {},
  trackSameDaySettlement,
  from,
}) {
  const [isLoading, setLoading] = useState(false);
  const screen = getScreenForTrackEvent(from);
  /** We expect the currentPrice to be already prefetched to avoid CLS in OnDemandV2   */
  const { discountPercent, currentPrice, newPrice, canViewDiscount } =
    useODSAutomaticPricingDiscount(user.merchant.currency || 'INR');

  const { isManagedMerchantAccount } = useIsManagedMerchantAccount();

  const isPricingValid = showDiscount && !isManagedMerchantAccount && canViewDiscount;

  useEffect(() => {
    trackCrossSellBannerRendered({ screen });
  }, []);

  const isOndemandSettlementsRestricted = user.isOndemandSettlementsRestricted;
  const isOndemandSettlementEnabled = user.isOndemandSettlementEnabled;

  const handleKnowMoreClick = () => {
    openModal({
      component: <ScheduledModal from={from} />,
      size: 'small',
      disableClose: true,
      isNew: false,
    });

    trackKnowMore({ screen });

    trackKnowMoreClicked({
      screen,
    });
  };

  const handleEnableNowClick = () => {
    trackEnableNowClicked({
      screen,
    });
    setLoading(true);
    return new Promise((resolve) => {
      enableAutomaticSettlements()
        .then(() => {
          const updatedUser = new User(user);
          updatedUser
            .fetch()
            .then((res) => {
              updateSession({ user: res.data });
              openModal({
                component: (
                  <ScheduledModal enabled trackSameDaySettlement={trackSameDaySettlement} />
                ),
                size: 'small',
                disableClose: true,
                isNew: false,
              });
              resolve();
            })
            .catch(() => {
              showNotification({
                type: 'error',
                message: 'Error loading user profile',
              });
              closeModal();
              resolve();
            });

          if (isOndemandSettlementEnabled && isOndemandSettlementsRestricted) {
            setEnableEsPartialAutomaticDate();
          }
        })
        .catch(({ errors }) => {
          const error = errors?.[0] || 'Something went wrong!';
          showNotification({
            type: 'error',
            message: error,
          });
          resolve();
        });
    }).finally(() => {
      setLoading(false);
    });
  };

  if (!user.isOrgRZP) return null;

  return (
    <Box
      padding="spacing.6"
      borderRadius="large"
      backgroundColor="surface.background.primary.intense"
      width="100%"
      backgroundSize="cover"
      backgroundRepeat="no-repeat"
      backgroundImage={showDiscount ? `url(${BIG_UPSELLING_BG})` : `url(${SMALL_UPSELLING_BG})`}
    >
      <Heading marginBottom="spacing.3" color="surface.text.staticWhite.normal" size="small">
        <InfoIcon
          marginBottom="-2px"
          size="large"
          color="surface.icon.staticWhite.normal"
          marginRight="spacing.3"
        />
        Did you know?
      </Heading>
      <Text
        display="block"
        marginBottom="spacing.7"
        color="surface.text.staticWhite.subtle"
        size="large"
      >
        You can get your daily revenue automatically at{' '}
        <Text size="large" as="span" weight="semibold" color="currentColor">
          09:00 AM
        </Text>{' '}
        and{' '}
        <Text size="large" weight="semibold" as="span" color="currentColor">
          05:00 PM
        </Text>{' '}
        on all working days with Same-day Settlements
        {isManagedMerchantAccount && (
          <Text marginTop={'spacing.6'} size="large" weight="regular" color="currentColor">
            Please contact your Account Manager to enable this feature.
          </Text>
        )}
      </Text>

      {isPricingValid && (
        <Container>
          <Box textAlign="center" paddingY="spacing.6" paddingX="spacing.6">
            <Heading size="2xlarge" color="surface.text.staticWhite.normal">
              -{discountPercent}%
            </Heading>
            <Text
              display="block"
              marginTop="spacing.4"
              marginBottom="spacing.6"
              color="surface.text.staticWhite.subtle"
            >
              Discount on your Instant Settlements fee, forever!
            </Text>
            <ContainerDiscount>
              <Text
                display="block"
                color="surface.text.gray.subtle"
                marginX="spacing.3"
                marginY="spacing.3"
                textDecorationLine="line-through"
              >
                {currentPrice}%
              </Text>
              <ArrowRightIcon marginX="spacing.5" />
              <Text weight="semibold" as="span" textDecorationLine="none">
                {newPrice}% / settlement
              </Text>
            </ContainerDiscount>
          </Box>
        </Container>
      )}

      <Box display="flex" gap="spacing.5" marginTop="spacing.7">
        <Box whiteSpace="nowrap" flexGrow="1">
          <Button
            variant="tertiary"
            color="white"
            isFullWidth
            onClick={handleKnowMoreClick}
            isDisabled={isLoading}
          >
            Know More
          </Button>
        </Box>

        {!isManagedMerchantAccount && showDiscount && (
          <Box whiteSpace="nowrap" flexGrow="1">
            <Button color="white" isFullWidth isLoading={isLoading} onClick={handleEnableNowClick}>
              Enable Now
            </Button>
          </Box>
        )}
      </Box>
    </Box>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, {
  closeModal: fnCloseModal,
  openModal: fnOpenModal,
  updateSession: fnUpdateSession,
  showNotification: fnShowNotification,
})(Upselling);
