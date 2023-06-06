import React, { useMemo, useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import rTracking, { useTracking } from 'react-tracking';
import { LS_LABELS, IMPRESSION_TIME_INTERVAL } from 'common/ui/PricingSubscription/constants';
import {
  BottomSheet,
  BottomSheetHeader,
  BottomSheetBody,
  Button,
} from '@razorpay/blade/components';
import PricingHeaderMweb from './PricingHeaderMweb';
import PricingSectionMweb from './PricingSectionMweb';
import Carousel from './PricingBundleCarouselMweb';
import { setCookie } from 'common/utils/cookies';
import {
  TogglePlanValue,
  handleCheckoutPayment,
} from 'common/ui/PricingSubscription/PricingBundleCommon';
import type {
  TrackingObjectType,
  pricingBundleAsset,
} from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';
import { getAssetTrackingProperties } from 'merchant/models/GrowthService/commonUtils';
import { fetchGSModal as fetchGSModalAction } from 'merchant/reducers/growthService';

const enum CloseEvent {
  CLOSE = 'close',
  CloseCTA = 'CloseCTA',
  NotInterestedCTA = 'NotInterestedCTA',
  CloseLabel = 'Close',
  ButtonLabel = 'Not Interested',
}
const BottomSheetPricingContainer = ({
  pricingSubscription,
  templateId,
  gs_modals,
  user,
  fetchGSModal,
}: {
  pricingSubscription: pricingBundleAsset;
  templateId: string;
  gs_modals: pricingBundleAsset;
  user: {
    current: string;
    isAllowedMultiple: (string) => boolean;
    isAccountAndSettingsRevampEnabled: boolean;
  };
  fetchGSModal: ({ template_id }: { template_id: string }) => void;
}): JSX.Element => {
  const [isOpen, setIsOpen] = useState<boolean>(true);
  const [isViewMore, setViewMore] = useState<boolean>(false);
  const [togglePlan, setTogglePlan] = useState<string>(TogglePlanValue.monthly);
  const isChecked = useMemo(() => TogglePlanValue.annual === togglePlan, [togglePlan]);
  const { trackEvent } = useTracking({ page: 'Home' });

  const {
    pricingPlans = [],
    id: trackingId = '',
    featureIdToFeatureCopyMap = {},
    header: { pillText = '', title = '' } = {},
    tracking_data = {},
  } = (templateId ? gs_modals : pricingSubscription?.[0]) || {};

  const trackingData = {
    trackingID: trackingId,
    source: 'Home',
    ...getAssetTrackingProperties(trackingId, tracking_data, {}),
  };

  useEffect(() => {
    if (templateId) fetchGSModal({ template_id: templateId });
  }, []);

  useEffect(() => {
    trackEvent(
      window.rzpQ &&
        window.rzpQ.merchantActions().success(`merchant_dashboard.pricing_banner`, {
          screen: 'Monthly Plans',
          ...trackingData,
        }),
    );
  }, [trackEvent]);

  useEffect(() => {
    if (!templateId) {
      const impressionCount = localStorage.getItem(`${LS_LABELS.IMPRESSION_COUNT}-${user.current}`);
      localStorage.setItem(
        `${LS_LABELS.IMPRESSION_COUNT}-${user.current}`,
        String(Number(impressionCount) + 1),
      );

      const expiryTime = new Date();
      expiryTime.setTime(expiryTime.getTime() + IMPRESSION_TIME_INTERVAL);
      setCookie(`${LS_LABELS.LAST_IMPRESSION_WITHIN_INTERVAL}-${user.current}`, '1', expiryTime);
    }
  }, []);

  const trackInstrumentation = (type: string, trackingObject: TrackingObjectType) => {
    const { toggle_switch, cta_value, section, event_name } = trackingObject || {};

    const handleEventBasedOnType = () => {
      switch (type) {
        case 'choosePlanCTA':
          return {
            ...(toggle_switch && { toggle_switch }),
            ...(cta_value && { cta_value }),
            ...(section && { section }),
            ...trackingData,
          };
        case 'NotInterestedCTA':
          return {
            ...(toggle_switch && { toggle_switch }),
            ...(cta_value && { cta_value }),
            ...getAssetTrackingProperties(trackingId, tracking_data, {}, event_name),
          };
        case 'CloseCTA':
          return {
            ...(toggle_switch && { toggle_switch }),
            ...getAssetTrackingProperties(trackingId, tracking_data, {}, event_name),
          };
        case 'Overlay':
          return {
            ...(toggle_switch && { toggle_switch }),
            ...getAssetTrackingProperties(trackingId, tracking_data, {}, event_name),
          };
        case 'paymentSuccess':
          return {
            ...getAssetTrackingProperties(trackingId, tracking_data, {}, event_name),
          };
        case 'viewMoreCTA':
          return {
            ...(toggle_switch && { toggle_switch }),
            ...(cta_value && { cta_value }),
            ...trackingData,
          };
        case 'toogleSwitch':
          return {
            ...(toggle_switch && { toggle_switch }),
            ...trackingData,
          };
        default:
          return trackingData;
      }
    };

    trackEvent(
      window.rzpQ &&
        window.rzpQ
          .merchantActions()
          .clicked(`${event_name || 'merchant_dashboard.click_cta_initiated'}`, {
            ...trackingObject,
            ...handleEventBasedOnType(),
          }),
    );
  };

  const toggleViewMore = (): void => {
    setViewMore((prevState) => !prevState);
    trackInstrumentation('viewMoreCTA', {
      toggle_switch: togglePlan,
      cta_value: '🎁 View All Benefits',
    });
  };

  const handlePlanSwitch = (): void => {
    if (TogglePlanValue.annual === togglePlan) {
      setTogglePlan(TogglePlanValue.monthly);
      trackInstrumentation('toogleSwitch', {
        toggle_switch: TogglePlanValue.monthly,
        event_name: 'merchant_dashboard.toggle_switch.initiated',
      });
    } else if (TogglePlanValue.monthly === togglePlan) {
      setTogglePlan(TogglePlanValue.annual);
      trackInstrumentation('toogleSwitch', {
        toggle_switch: TogglePlanValue.annual,
        event_name: 'merchant_dashboard.toggle_switch.initiated',
      });
    }
  };
  const handleClose =
    (buttonType?: string): (() => void) =>
    () => {
      setIsOpen(false);
      trackInstrumentation(
        buttonType === CloseEvent.CLOSE ? CloseEvent.CloseCTA : CloseEvent.NotInterestedCTA,
        {
          toggle_switch: togglePlan,
          cta_value:
            buttonType === CloseEvent.CLOSE ? CloseEvent.CloseLabel : CloseEvent.ButtonLabel,
          event_name:
            buttonType === CloseEvent.CLOSE
              ? 'merchant_dashboard.click_close.initiated'
              : 'merchant_dashboard.not_interested.initiated',
        },
      );
      if (buttonType !== 'close' && !templateId)
        localStorage.setItem(`${LS_LABELS.NOT_INTERESTED}-${user.current}`, '1');
    };

  return (
    <BottomSheet isOpen={isOpen} onDismiss={handleClose('close')} snapPoints={[1, 1, 1]}>
      <BottomSheetHeader title="" />
      <BottomSheetBody>
        <PricingHeaderMweb
          togglePlan={togglePlan}
          isChecked={isChecked}
          handlePlanSwitch={handlePlanSwitch}
          pillText={pillText}
          title={title}
        />
        {pricingPlans.length ? (
          <Carousel>
            {pricingPlans.map((item) => (
              <PricingSectionMweb
                trackInstrumentation={trackInstrumentation}
                key={`${item?.title}_${item?.id}`}
                plans={item}
                featureIdToFeatureCopyMap={featureIdToFeatureCopyMap}
                togglePlan={togglePlan}
                isViewMore={isViewMore}
                toggleViewMore={toggleViewMore}
                handleCheckoutPayment={handleCheckoutPayment}
                closeBottomSheet={setIsOpen}
              />
            ))}
          </Carousel>
        ) : null}
        {isViewMore ? (
          <Button isFullWidth onClick={handleClose()} size="small" type="button" variant="tertiary">
            Not Interested
          </Button>
        ) : null}
      </BottomSheetBody>
    </BottomSheet>
  );
};

export default compose<any>(
  rTracking({
    page: 'DashboardBanner',
  }),
  connect(
    (state) => ({
      user: state.session.user,
      ...state?.growthService?.gs_modals,
    }),
    (dispatch) => {
      return bindActionCreators(
        {
          fetchGSModal: fetchGSModalAction,
        },
        dispatch,
      );
    },
  ),
)(BottomSheetPricingContainer);
