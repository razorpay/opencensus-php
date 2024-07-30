import React, { memo, useCallback, useState } from 'react';
import {
  Badge,
  Box,
  Button,
  ChevronDownIcon,
  ChevronsUpIcon,
  CloseIcon,
  Link,
  RupeeIcon,
  Switch,
  Text,
} from '@razorpay/blade/components';
import rzpLogo from 'assets/rzp_logo.jpg';

import Image from 'common/ui/Image';
import Loader from 'common/ui/Loader';
import { PAYMENT_TYPE } from 'common/ui/PricingSubscription/constants';
import { MODAL_TYPE } from 'common/ui/PricingSubscription/screen/Common/MultiPaymentOptions/constant';
import lazy from 'merchant/routes/LazyLoader';
import { merchantFetch } from 'merchant/utils/ajax';
import { loadCheckoutScript } from 'merchant/views/Capital/utils';

import {
  CloseModalButton,
  FeatureItem,
  FireImage,
  Footer,
  Header,
  ModalClose,
  PercentageColor,
  PlansTncSection,
  StrikePrice,
  StyleToastLink,
} from './PricingStyled';
import {
  FooterButtonType,
  GetPlanPriceType,
  PaymentCheckoutFlowType,
  PlansType,
  PricingHeaderType,
} from './PricingSubscriptionProps.type';
import TncMobile from './PricingTnCMobile';

const TncDesktop = lazy(
  () => import(/* webpackChunkName: 'PricingTncModalDesktopComponent' */ './PricingTnC'),
);

const TogglePlanValue = {
  monthly: 'monthly',
  annual: 'annual',
} as const;

const FooterButton = ({
  isFullView,
  handleToggle,
  equalizeRowElementHeights,
  handleClose,
}: FooterButtonType): JSX.Element => {
  const expandBenefits = useCallback(() => {
    Promise.resolve(handleToggle()).then(() => {
      equalizeRowElementHeights();
    });
  }, [handleToggle, equalizeRowElementHeights]);

  return (
    <Box position="relative">
      <Footer className="pricing-footer">
        <Button
          iconPosition="right"
          icon={!isFullView ? ChevronDownIcon : ChevronsUpIcon}
          onClick={expandBenefits}
          size="small"
          type="button"
          variant="secondary"
          color="primary"
        >
          &#127873; &nbsp; View All Benefits
        </Button>
        <Button
          onClick={() => handleClose()}
          marginLeft="spacing.7"
          size="small"
          type="button"
          variant="tertiary"
        >
          Not Interested
        </Button>
      </Footer>
    </Box>
  );
};

const PricingHeader = ({
  headerSrc,
  headerAlt,
  title,
  toggleSwitchButton,
  pillText,
  handleClose,
}: PricingHeaderType): JSX.Element => {
  return (
    <Box position="relative">
      <Header className="pricing-header">
        <Box display="flex" alignItems="center" flexDirection="row">
          <FireImage>
            <Image src={headerSrc} alt={headerAlt} />
          </FireImage>
          <Text size="large" weight="semibold" color="surface.text.gray.normal">
            {title}
          </Text>
        </Box>
        <Box display="flex" alignItems="center">
          <Box as="label" display="flex" alignItems="center" gap="spacing.2">
            <Text size="large" color="surface.text.gray.muted">
              Switch to Annual Plans
            </Text>
            <Switch onChange={toggleSwitchButton} accessibilityLabel="Toggle Plans" size="medium" />
          </Box>
          <Box marginLeft="spacing.4">
            <Badge emphasis="subtle" size="large" color="positive">
              {String(pillText)}
            </Badge>
            <CloseModalButton data-testid="close-icon" onClick={() => handleClose('close')}>
              <CloseIcon size="large" color="interactive.icon.gray.muted" />
            </CloseModalButton>
          </Box>
        </Box>
      </Header>
    </Box>
  );
};

const RedirectToastUI = ({ handleToastLink }: { handleToastLink: () => void }): JSX.Element => {
  return (
    <StyleToastLink>
      <Text size="small" variant="body" color="surface.text.gray.disabled">
        We’ve received your payment, and your new Pricing plan should be updated in the next
        24-48hrs.
        <Link onClick={handleToastLink} variant="button">
          Check Plan
        </Link>
      </Text>
    </StyleToastLink>
  );
};

const getMonthlyDiscount = (
  monthlyPrice: number,
  annualPrice: number,
): { projectedPrice: number; percentSavings: number } => {
  const projectedPrice = monthlyPrice * 12;
  const percentSavings = Math.floor(((projectedPrice - annualPrice) * 100) / projectedPrice);
  return { projectedPrice, percentSavings };
};

const PlanDetails = ({
  plans,
  togglePlan,
  isReadOnly,
  isLoading,
  selectedPlanId,
  getPaymentMethodCall,
  isPaymentOptionLoading,
  featureIdOrder,
  featureIdToFeatureCopyMap,
  isFullView,
  featureIndex,
  handleMouseEnter,
  handleMouseLeave,
}: GetPlanPriceType): JSX.Element => {
  const { icon, title, monthlyPrice, annualPrice, isRecommended } = plans;

  const renderFeatures = () => {
    return featureIdOrder
      .filter((_, index) => isFullView || index < 3)
      .map((featureId, index) => {
        const featureOffering = plans?.[featureId]?.[togglePlan];
        const featureOfferingTrimmed = featureOffering ? String(featureOffering).trim() : '';

        return (
          <div key={index} style={{ width: 'inherit' }}>
            <FeatureItem
              addLineGradient={index === 0}
              isRecommended={isRecommended}
              planTitle={featureIdToFeatureCopyMap[featureId]}
              showRowTitle={featureIndex === 0}
              onMouseEnter={handleMouseEnter(plans?.title)}
              onMouseLeave={handleMouseLeave(plans?.title)}
            >
              <Text size="medium" color="surface.text.gray.subtle">
                {featureOfferingTrimmed}
              </Text>
            </FeatureItem>
          </div>
        );
      });
  };

  return (
    <>
      <Box marginBottom="spacing.3">
        <img src={icon.src} alt={icon.alt} />
      </Box>
      <Text
        marginBottom="spacing.7"
        marginTop="spacing.2"
        weight="semibold"
        size="large"
        color="surface.text.gray.subtle"
      >
        {title}
      </Text>

      <Box display="flex" alignItems="center" flexDirection="row">
        <RupeeIcon color="currentColor" size="large" />
        <Text weight="semibold" size="large" color="surface.text.gray.normal">
          {togglePlan === TogglePlanValue.monthly
            ? `${monthlyPrice.toLocaleString()}/Month`
            : `${annualPrice.toLocaleString()}/Year`}
        </Text>
      </Box>
      {togglePlan === TogglePlanValue.monthly && (
        <Box marginBottom="spacing.4" marginTop="spacing.2">
          <Text size="small" variant="body" color="surface.text.gray.muted">
            ₹{Math.floor(annualPrice / 12).toLocaleString()}/Month with Annual Plan
          </Text>
        </Box>
      )}
      {togglePlan === TogglePlanValue.annual && (
        <StrikePrice>
          <Text color="surface.text.gray.muted" size="small" variant="body">
            ₹{getMonthlyDiscount(monthlyPrice, annualPrice).projectedPrice.toLocaleString()}
          </Text>
          <PercentageColor>
            <Text>{getMonthlyDiscount(monthlyPrice, annualPrice).percentSavings}% Off</Text>
          </PercentageColor>
        </StrikePrice>
      )}
      {!isReadOnly && (
        <Button
          isLoading={(isLoading || isPaymentOptionLoading) && selectedPlanId === plans?.id}
          isDisabled={(isLoading || isPaymentOptionLoading) && selectedPlanId !== plans?.id}
          iconPosition="left"
          onClick={getPaymentMethodCall(plans)}
          size="medium"
          isFullWidth
          type="button"
          variant={plans.button?.variant}
          marginTop="spacing.8"
          marginBottom="spacing.10"
        >
          {plans.button?.label}
        </Button>
      )}

      {renderFeatures()}
    </>
  );
};

const ModalLoader = ({ closeModal }: { closeModal: () => void }): JSX.Element => {
  return (
    <>
      <ModalClose onClick={closeModal}>
        <CloseIcon color="feedback.icon.neutral.intense" size="medium" />
      </ModalClose>
      <div id="gs-modal-loader">
        <Loader />
      </div>
    </>
  );
};

const PricingTncInfo = ({ isMobile }: { isMobile: boolean }): JSX.Element => {
  const [isOpenTncModal, setOpenTnCModal] = useState(false);

  const toggleTncModal = useCallback(() => {
    setOpenTnCModal((prevState) => !prevState);
  }, []);

  return (
    <PlansTncSection isMobile={isMobile}>
      <Text color="surface.text.gray.subtle">Auto Renewal Plans. No Refunds</Text>
      <Text color="surface.text.gray.subtle">*Prices mentioned are exclusive of GST</Text>
      <Box width="fit-content" height="fit-content" display="flex" alignItems="center">
        <Text color="surface.text.gray.subtle" marginRight="spacing.2">
          Full
        </Text>
        <Link onClick={toggleTncModal} variant="button">
          Terms & Conditions
        </Link>
      </Box>
      {isMobile ? (
        <TncMobile isOpenTncModal={isOpenTncModal} toggleTncModal={toggleTncModal} />
      ) : (
        <TncDesktop isOpenTncModal={isOpenTncModal} toggleTncModal={toggleTncModal} />
      )}
    </PlansTncSection>
  );
};

const handleCheckoutInitiation = (trackInstrumentation) => {
  trackInstrumentation('', {
    value: 'success',
    event_name: 'merchant_dashboard.checkout_modal.initiated',
  });
};

const handleCheckoutError = (trackInstrumentation) => {
  trackInstrumentation('', {
    value: 'failure',
    event_name: 'merchant_dashboard.checkout_modal.initiated',
  });
  throw new Error('Something went wrong . Please try again');
};

const handleCheckoutPayment =
  (
    props: PaymentCheckoutFlowType,
  ): ((plans?: PlansType | React.MouseEvent<HTMLButtonElement, MouseEvent>) => Promise<void>) =>
  async () => {
    const {
      togglePlan,
      plans,
      type,
      trackInstrumentation,
      setLoading,
      handlePaymentSuccess,
      handlePaymentFailure,
      planAmount,
      settlementBalance,
      setModalType,
      togglePaymentOptionModal,
      setCongratulatoryModal,
      showNotificationToast,
    } = props;

    const { title, id, button: { label } = {} } = plans;

    trackInstrumentation('choosePlanCTA', {
      event_method: 'initiated',
      toggle_switch: togglePlan,
      cta_value: type ? 'Proceed to pay' : label,
      section: title,
      payment_method: type === PAYMENT_TYPE.INTERNAL ? 'Settlement balance' : 'Normal Checkout',
      plan_id: id,
      plan_name: title,
      event_name: 'merchant_dashboard.click_cta',
    });

    setLoading(true);

    await loadCheckoutScript();

    try {
      const subscriptionData = await merchantFetch({
        method: 'post',
        url: `pricing/merchant/subscriptions?plan_id=${id}&frequency=${togglePlan}&type=${
          type || 'PG' // TODO: remove `|| 'PG'` when add this feature in Mobile
        }`,
        mode: 'live',
      });

      const { data: { response = {}, status_code = '' } = {} } = subscriptionData || {};

      if (status_code === 200) {
        const { subscription = {} } = response;

        if (type === PAYMENT_TYPE.PG || !type) {
          // TODO: remove `!type` when add this feature in Mobile
          const { merchant_id, account_key, payment_subscription_id } = subscription || {};

          const options = {
            notes: {
              merchant_id,
            },
            key: account_key,
            subscription_id: payment_subscription_id,
            name: `Razorpay Pricing Package`,
            description: '18% GST included',
            image: rzpLogo,
            handler: (response) => {
              handlePaymentSuccess(response, plans);
            },
          };

          const razorpayCheckout = new window.Razorpay(options);
          razorpayCheckout.open();

          razorpayCheckout.on('payment.failed', (response) => {
            handlePaymentFailure(response, plans);
          });

          handleCheckoutInitiation(trackInstrumentation);
        } else if (type === PAYMENT_TYPE.INTERNAL) {
          if (planAmount <= settlementBalance) {
            setModalType(MODAL_TYPE.SUFFICIENT_BALANCE);
          } else {
            setModalType(MODAL_TYPE.INSUFFICIENT_BALANCE);
          }

          setCongratulatoryModal(true);
          togglePaymentOptionModal();
        }
      } else {
        handleCheckoutError(trackInstrumentation);
      }
    } catch (e) {
      showNotificationToast({
        type: 'error',
        message: (e as Error)?.message || 'Something went wrong . Please try again',
      });
    } finally {
      setLoading(false);
    }
  };

const PricingTncInfoMemo = memo(PricingTncInfo);

export {
  FooterButton,
  getMonthlyDiscount,
  PlanDetails,
  handleCheckoutPayment,
  ModalLoader,
  PricingHeader,
  PricingTncInfoMemo,
  RedirectToastUI,
  TogglePlanValue,
};
