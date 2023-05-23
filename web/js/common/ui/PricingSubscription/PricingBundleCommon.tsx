import React from 'react';
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
  Label,
  Input,
  Switch,
  StyledHeaderIcon,
  StyleToastLink,
  StyleBadgeContainer,
  StyleSwitchContainer,
  PlanLeftSection,
  StyleFireImage,
  StyleInfo,
  StylePlanWrapper,
} from './PricingStyled';
import Loader from 'common/ui/Loader';
import {
  Text,
  Button,
  ChevronDownIcon,
  ChevronUpIcon,
  Badge,
  CloseIcon,
  Heading,
  Link,
  Box,
  RupeeIcon,
} from '@razorpay/blade/components';
import Image from 'common/ui/Image';
import {
  FooterButtonType,
  PricingHeaderType,
  GetPlanPriceType,
  ViewMoreParams,
} from './PricingSubscriptionProps.type';

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
  handleCheckoutPayment,
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
            isLoading={isLoading && selectedPlanId === plans?.id}
            isDisabled={isLoading && selectedPlanId !== plans?.id}
            iconPosition="left"
            onClick={handleCheckoutPayment(plans)}
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

const PricingTncInfo = (): JSX.Element => {
  return (
    <StyleInfo>
      <Text>
        Auto Renewal Plans. No Refunds <Box /> *Prices mentioned are exclusive of GST
      </Text>
      {/* Full{' '}
      <Link onClick={function noRefCheck() {}} variant="button">
        Terms & Conditions
      </Link> */}
    </StyleInfo>
  );
};
export {
  FooterButton,
  plansDetailsForViewMore,
  PricingHeader,
  RedirectToastUI,
  TogglePlanValue,
  getPlanPrice,
  ModalLoader,
  PricingTncInfo,
};
