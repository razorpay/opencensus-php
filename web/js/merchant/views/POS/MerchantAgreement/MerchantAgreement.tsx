import React, { useEffect, useState } from 'react';
import { Box, Button, Heading, Link, Spinner, Text, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { useMutation, useQuery } from '@tanstack/react-query';
import HandshakeImg from 'assets/pos/MerchantAgreement.svg';
import { useNavigate } from 'react-router-dom';
import { getOrg, getUser, useStore } from 'shell/commonStore';
import ErrorBoundary, { Ranks } from 'common/new-ui/ErrorBoundary';
import POSAgreementConfirmation from 'merchant/views/POS/MerchantAgreement/AgreementConfirmation';
import {
  StyledAgreementContainer,
  StyledBanner,
  StyledHandshake,
} from 'merchant/views/POS/MerchantAgreement/styles';
import {
  AGREEMENT_CONSENTED_AT_FIELD,
  COMPLETED,
  CONSENT_COMPONENT,
  CONSENT_STEP,
  POS_AGREEMENT_SIGN_FAILED,
  PRICING_AGREEMENT_FAILED_TO_LOAD,
} from 'merchant/views/POS/constants';
import {
  getAgreementStatus,
  getComponentByName,
  getStepDataByStepName,
  isCustomRateEnabled,
} from 'merchant/views/POS/helpers';
import { agreeToPosMerchantAgreement, getModularOnboardingData } from 'merchant/views/POS/services';
import { PosAgreementSignIds } from 'merchant/views/POS/types';
import { ErrorBoundaryFallBackComponent } from 'merchant/widgets/utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const PosMerchantAgreement = (): JSX.Element => {
  const [isSignedSuccessfully, setIsSignedSuccessfully] = useState(false);
  const showNotification = useStore((state) => state.showNotification);
  const session = getUser();
  const org = getOrg();
  const { data: workflowConfig, isLoading } = useQuery({
    queryKey: ['posAgreement'],
    queryFn: () => getModularOnboardingData(session?.merchant?.id ?? ''),
    retryDelay: 800,
    cacheTime: 1000 * 60 * 1,
    refetchOnWindowFocus: false,
    refetchOnMount: 'always',
  });

  const { mutate: signAllAgreements, isLoading: isSigningAgreement } = useMutation({
    mutationFn: (agreementIds: PosAgreementSignIds) => agreeToPosMerchantAgreement(agreementIds),
    onSuccess: (result) => {
      const workflowConfig = result?.data;
      const agreementStatus = getAgreementStatus(workflowConfig);
      if (agreementStatus.status === COMPLETED) {
        setIsSignedSuccessfully(true);
      } else {
        showNotification({ type: 'error', message: POS_AGREEMENT_SIGN_FAILED });
      }
    },
    onError: () => showNotification({ type: 'error', message: POS_AGREEMENT_SIGN_FAILED }),
  });

  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const navigate = useNavigate();
  const getTextAlignment = () => {
    if (['xs', 'base'].includes(matchedBreakpoint as string)) return 'center'; //we show centered text upto 480px
    return 'left';
  };

  const isAgreementAlreadySigned = getAgreementStatus(workflowConfig?.data).status === COMPLETED;

  const getAgreementIds = (workflowConfig) => {
    const consentStep = getStepDataByStepName(workflowConfig, CONSENT_STEP);
    if (!consentStep) {
      showNotification({ type: 'error', message: POS_AGREEMENT_SIGN_FAILED });
      throw new Error('No consent step found');
    }
    const consentComponent = getComponentByName(consentStep.components, CONSENT_COMPONENT);
    if (!consentComponent) {
      showNotification({ type: 'error', message: POS_AGREEMENT_SIGN_FAILED });
      throw new Error('No consent component found');
    }
    const agreementIds = {
      merchantId: session?.merchant?.id ?? '',
      tncId: consentComponent.meta.templates.terms_and_conditions_consent,
      privacyId: consentComponent.meta.templates.privacy_consent,
    };
    return isCustomRateEnabled(workflowConfig)
      ? { ...agreementIds, pricingId: consentComponent.meta.templates.pricing_consent }
      : agreementIds;
  };

  const handleAgreeBtnClick = () => {
    analyticsTrack({
      objectName: 'Website CTA',
      actionName: 'Clicked',
      screen: 'Agreement signing',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        label: 'I Agree',
        l1FunnelStage: 'Agreement Signing',
        l2FunnelStage: 'Merchant Signing-online',
        section: 'Agreement Signing',
        subSection: 'Merchant Signing-online',
      },
    });
    signAllAgreements({
      ...getAgreementIds(workflowConfig?.data),
      [AGREEMENT_CONSENTED_AT_FIELD]: Math.floor(Date.now() / 1000),
    });
  };

  const onCancelClick = () => {
    analyticsTrack({
      objectName: 'Website CTA',
      actionName: 'Clicked',
      screen: 'Agreement signing',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        label: 'Cancel',
        l1FunnelStage: 'Agreement Signing',
        l2FunnelStage: 'Merchant Signing-online',
        section: 'Agreement Signing',
        subSection: 'Merchant Signing-online',
      },
    });
    navigate('/app/dashboard');
  };

  const onPricingAgreementBtnClick = () => {
    analyticsTrack({
      objectName: 'Link',
      actionName: 'Clicked',
      screen: 'Agreement signing',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        label: 'Pricing Agreement',
        l1FunnelStage: 'Agreement Signing',
        l2FunnelStage: 'Merchant Signing-online',
        section: 'Agreement Signing',
        subSection: 'Merchant Signing-online',
      },
    });
  };

  useEffect(() => {
    if (workflowConfig) {
      if (!workflowConfig.data?.workflow_data) {
        showNotification({ type: 'error', message: PRICING_AGREEMENT_FAILED_TO_LOAD });
      }
      const agreementData = getAgreementStatus(workflowConfig.data);
      if (!agreementData?.is_required) {
        navigate('/dashboard');
      }
      if (agreementData?.is_required && !agreementData?.status) {
        navigate('/dashboard');
      }
    }
  }, [isLoading]);

  useEffect(() => {
    if (!isAgreementAlreadySigned || !isSignedSuccessfully) {
      analyticsTrack({
        objectName: 'Page',
        actionName: 'Viewed',
        screen: 'Agreement signing',
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user),
          pageType: 'Merchant Signing-online',
          l1FunnelStage: 'Agreement Signing',
          l2FunnelStage: 'Merchant Signing-online',
        },
      });
    }
  }, [isAgreementAlreadySigned, isSignedSuccessfully]);

  if (isLoading)
    return (
      <Box
        as="section"
        height="90vh"
        width="100%"
        display="flex"
        alignItems="center"
        justifyContent="center"
      >
        <Spinner color="primary" accessibilityLabel="pos-agreement-spinner" size="xlarge" />
      </Box>
    );

  if (isAgreementAlreadySigned || isSignedSuccessfully) return <POSAgreementConfirmation />;

  return (
    <ErrorBoundary rank={Ranks.P0} FallbackComponent={ErrorBoundaryFallBackComponent}>
      <Box padding={{ base: 'spacing.0', s: 'spacing.8' }}>
        <StyledAgreementContainer>
          <StyledBanner displayStyle="flex">
            <Box width="100%" maxWidth={{ base: '278px', s: '350px' }} margin="auto">
              <Heading
                color="surface.text.staticWhite.normal"
                size="large"
                weight="semibold"
                marginBottom="spacing.6"
                textAlign={getTextAlignment()}
              >
                {`Ready to Accept Payments with ${org?.business_name} POS?`}
              </Heading>
              {session?.merchant?.name ? (
                <Text
                  size="small"
                  textAlign={getTextAlignment()}
                  marginBottom="spacing.6"
                  color="surface.text.gray.muted"
                >
                  {`Logged in as ${session.merchant.name}`}
                </Text>
              ) : null}
            </Box>
            <Box>
              <StyledHandshake src={HandshakeImg} />
            </Box>
          </StyledBanner>
          <Box
            height="100%"
            borderTopLeftRadius="xlarge"
            borderTopRightRadius="xlarge"
            padding="spacing.8"
            paddingLeft="spacing.7"
            paddingRight="spacing.7"
            backgroundColor="surface.background.gray.intense"
            flexGrow="1"
          >
            <Heading
              marginBottom="spacing.7"
              color="interactive.text.gray.normal"
              weight="semibold"
              size="large"
            >
              Take a moment to review the following:
            </Heading>
            <Box>
              <Text color="interactive.text.neutral.muted" marginBottom="spacing.7">
                You may review our detailed{' '}
                <Link target="_blank" href="https://razorpay.com/terms/">
                  T&C
                </Link>{' '}
                and{' '}
                <Link target="_blank" href="https://razorpay.com/privacy/">
                  Privacy Policy
                </Link>{' '}
                for services offered by {org?.business_name}.
              </Text>
              {isCustomRateEnabled(workflowConfig?.data) ? (
                <Text color="interactive.text.neutral.muted" marginBottom="spacing.7">
                  You also agree to the pricing details outlined in our{' '}
                  <Link
                    target="_blank"
                    href="/app/pos-merchant-agreement/pricing"
                    onClick={onPricingAgreementBtnClick}
                  >
                    Pricing Agreement
                  </Link>
                  . This agreement explains the fees associated with your plan and details about the
                  devices subscribed by you.
                </Text>
              ) : null}
              <Text color="interactive.text.neutral.muted" marginBottom="68px">
                By clicking ‘I Agree’, you confirm that you have reviewed and agreed to the terms
                outlined above. Upon successful authorisation, you will receive a confirmation email
                with copies of both the agreements for your reference.
              </Text>
            </Box>
            <Box
              width={{ base: 'auto', s: 'fit-content' }}
              marginLeft={{ s: 'auto' }}
              display="flex"
              justifyContent="space-between"
              alignItems="center"
              gap={{ s: 'spacing.10' }}
            >
              <Box textAlign={getTextAlignment()} flex={{ xs: '1', s: '0' }}>
                <Link onClick={onCancelClick} variant="button">
                  Cancel
                </Link>
              </Box>
              <Box flex={{ xs: '1.5' }} width={{ s: '150px' }}>
                <Button
                  onClick={handleAgreeBtnClick}
                  isFullWidth
                  size="large"
                  isLoading={isSigningAgreement}
                >
                  I Agree
                </Button>
              </Box>
            </Box>
          </Box>
        </StyledAgreementContainer>
      </Box>
    </ErrorBoundary>
  );
};

export default PosMerchantAgreement;
