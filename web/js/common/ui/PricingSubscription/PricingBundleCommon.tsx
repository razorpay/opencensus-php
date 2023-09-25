import React, { useState, memo, useCallback } from 'react';
import {
  Text,
  Button,
  ChevronDownIcon,
  ChevronUpIcon,
  Badge,
  CloseIcon,
  Heading,
  Box,
  RupeeIcon,
  Link,
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
  StyledTr,
  StyledTd,
  StyledFooter,
  StyledHeader,
  StyledCloseIcon,
  StyledModalClose,
  StyleStrikePrice,
  StylePlanIcon,
  StyleMonthlyPrice,
  StylePlanName,
  StyleWrapper,
  StylePercentageColor,
  StyleToastLink,
  Label,
  Input,
  Switch,
  StyledHeaderIcon,
  StyleBadgeContainer,
  StyleSwitchContainer,
  PlanLeftSection,
  StyleFireImage,
  StyleInfo,
  StylePlanWrapper,
} from './PricingStyled';
import {
  FooterButtonType,
  PricingHeaderType,
  GetPlanPriceType,
  ViewMoreParams,
  PlansType,
  PaymentCheckoutFlowType,
} from './PricingSubscriptionProps.type';
import TncMobile from './PricingTnCMobile';

const TncDesktop = lazy(
  () => import(/* webpackChunkName: 'PricingTncModalDesktopComponent' */ './PricingTnC'),
);

const TogglePlanValue = {
  monthly: 'monthly',
  annual: 'annual',
} as const;

const FooterButton = ({ isFullView, handleToggle }: FooterButtonType): JSX.Element => {
  return (
    <StyledFooter className="pricing-footer">
      <Button
        iconPosition="right"
        icon={!isFullView ? ChevronDownIcon : ChevronUpIcon}
        onClick={handleToggle}
        size="small"
        type="button"
      >
        &#127873; &nbsp; View All Benefits
      </Button>
    </StyledFooter>
  );
};

const plansDetailsForViewMore = ({
  text,
  pricingPlans,
  featureId,
  featureIndex,
  handleMouseEnter,
  handleMouseLeave,
  togglePlan,
}: ViewMoreParams): JSX.Element => {
  return (
    <StyledTr key={text} pricingPlanLength={pricingPlans?.length}>
      <StyledTd removeCss textAlign verticalAlign={featureIndex === 0 ? undefined : 'baseline'}>
        <PlanLeftSection>
          <Heading contrast="low" size="small" type="normal" variant="regular" weight="bold">
            {text}
          </Heading>
        </PlanLeftSection>
      </StyledTd>
      {pricingPlans.map((plans): JSX.Element => {
        let featureOffering = plans?.[featureId]?.[togglePlan];
        featureOffering = featureOffering ? String(featureOffering).trim() : '';

        return (
          <StyledTd
            verticalAlign={featureIndex === 0 ? undefined : 'baseline'}
            key={plans?.id}
            addShadow
            addRightMargin
            addLineGradient={featureIndex === 0}
            isRecommend={plans?.isRecommended}
            onMouseEnter={handleMouseEnter(plans?.title)}
            onMouseLeave={handleMouseLeave(plans?.title)}
          >
            <Text>{featureOffering}</Text>
          </StyledTd>
        );
      })}
    </StyledTr>
  );
};
const PricingHeader = ({
  headerSrc,
  headerAlt,
  title,
  isChecked,
  toggleSwitchButton,
  pillText,
  handleClose,
}: PricingHeaderType): JSX.Element => {
  return (
    <StyledHeader className="pricing-header">
      <StyledHeaderIcon>
        <StyleFireImage>
          <Image src={headerSrc} alt={headerAlt} />
        </StyleFireImage>
        <Heading>{title}</Heading>
      </StyledHeaderIcon>
      <StyleSwitchContainer>
        <Text>Switch to Annual Plans</Text>
        <Label>
          <Input checked={isChecked} type="checkbox" onChange={(e) => toggleSwitchButton(e)} />
          <Switch />
        </Label>
      </StyleSwitchContainer>
      <StyleBadgeContainer>
        <Badge contrast="low" size="large" variant="positive">
          {String(pillText)}
        </Badge>
        <StyledCloseIcon data-testid="close-icon" onClick={handleClose('close')}>
          <CloseIcon color="feedback.icon.neutral.lowContrast" size="medium" />
        </StyledCloseIcon>
      </StyleBadgeContainer>
    </StyledHeader>
  );
};
const RedirectToastUI = ({ handleToastLink }: { handleToastLink: () => void }): JSX.Element => {
  return (
    <StyleToastLink>
      <Text contrast="low" size="small" type="placeholder" variant="body">
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
const getPlanPrice = ({
  plans,
  togglePlan,
  isReadOnly,
  isLoading,
  selectedPlanId,
  getPaymentMethodCall,
  isPaymentOptionLoading,
}: GetPlanPriceType): JSX.Element => {
  return (
    <StylePlanName data-testid={`plan-column-${plans.id}`}>
      <StylePlanIcon>
        <Image src={plans.icon.src} alt={plans.icon.alt} />
      </StylePlanIcon>
      <StyleWrapper>
        <Heading contrast="low" size="small" type="normal" variant="regular" weight="bold">
          {plans.title}
        </Heading>
      </StyleWrapper>
      <StylePlanWrapper>
        <RupeeIcon color="currentColor" size="large" />
        <Heading contrast="low" size="small" type="normal" variant="regular" weight="bold">
          {togglePlan === TogglePlanValue.monthly
            ? `${plans.monthlyPrice.toLocaleString()}/Month`
            : `${plans.annualPrice.toLocaleString()}/Year`}
        </Heading>
      </StylePlanWrapper>
      {togglePlan === TogglePlanValue.monthly ? (
        <StyleMonthlyPrice>
          <Text contrast="high" size="small" type="placeholder" variant="body">
            ₹{Math.floor(plans.annualPrice / 12).toLocaleString()}/Month with Annual Plan
          </Text>
        </StyleMonthlyPrice>
      ) : null}
      {togglePlan === TogglePlanValue.annual ? (
        <StyleStrikePrice>
          <Text contrast="high" size="small" type="placeholder" variant="body">
            ₹
            {getMonthlyDiscount(
              plans.monthlyPrice,
              plans.annualPrice,
            ).projectedPrice.toLocaleString()}
          </Text>
          <StylePercentageColor>
            <Text>
              {getMonthlyDiscount(plans.monthlyPrice, plans.annualPrice).percentSavings}% Off
            </Text>
          </StylePercentageColor>
        </StyleStrikePrice>
      ) : null}
      {!isReadOnly && (
        <StyleWrapper>
          <Button
            isLoading={(isLoading || isPaymentOptionLoading) && selectedPlanId === plans?.id}
            isDisabled={(isLoading || isPaymentOptionLoading) && selectedPlanId !== plans?.id}
            iconPosition="left"
            onClick={getPaymentMethodCall(plans)}
            size="small"
            type="button"
            variant={plans.button?.variant}
          >
            {plans.button?.label}
          </Button>
        </StyleWrapper>
      )}
    </StylePlanName>
  );
};
const ModalLoader = ({ closeModal }: { closeModal: () => void }): JSX.Element => {
  return (
    <>
      <StyledModalClose onClick={closeModal}>
        <CloseIcon color="feedback.icon.neutral.lowContrast" size="medium" />
      </StyledModalClose>
      <div id="gs-modal-loader">
        <Loader />
      </div>
    </>
  );
};

const PricingTncInfo = ({ isMobile }: { isMobile: boolean }): JSX.Element => {
  const [isOpenTncModal, setOpenTnCModal] = useState(false);
  const toggleTncModal = useCallback(
    (): void => setOpenTnCModal((prevState) => !prevState),
    [isOpenTncModal],
  );
  return (
    <StyleInfo isMobile={isMobile}>
      <Text>Auto Renewal Plans. No Refunds</Text>
      <Text>*Prices mentioned are exclusive of GST </Text>
      <Box width="fit-content" height="fit-content">
        Full{'  '}
        <Link onClick={toggleTncModal} variant="button">
          Terms & Conditions
        </Link>
      </Box>
      {isMobile ? (
        <TncMobile isOpenTncModal={isOpenTncModal} toggleTncModal={toggleTncModal} />
      ) : (
        <TncDesktop isOpenTncModal={isOpenTncModal} toggleTncModal={toggleTncModal} />
      )}
    </StyleInfo>
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
  plansDetailsForViewMore,
  PricingHeader,
  TogglePlanValue,
  getPlanPrice,
  ModalLoader,
  PricingTncInfoMemo,
  handleCheckoutPayment,
  getMonthlyDiscount,
  RedirectToastUI,
};
