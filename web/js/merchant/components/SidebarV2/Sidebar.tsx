import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { BladeProvider, Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { analyticsTrack } from 'common/utils/analytics';
import { redirectToEasyAfter1sec } from 'merchant/components/Activation/ActivationUtils';
import ShowWhen from 'merchant/components/ShowWhen';
import { trackViewedBankingNavBar } from 'merchant/components/Sidebar/ga';
import { getIsBankingEnabled } from 'merchant/components/Sidebar/helpers';
import ActivationProgress from 'merchant/components/SidebarV2/components/ActivationProgress';
import Theme from 'merchant/components/SidebarV2/theme';
import { LOYALTY_PRODUCTS_SECTION } from 'merchant/components/SidebarV2/utils/Fallback';
import AcceptPaymentsModal from 'merchant/containers/Home/OnboardingCard/Instant/AcceptPaymentsModal';
import { isOrgFeatureExist } from 'merchant/models/User';
import { hideAcceptPaymentsModal } from 'merchant/reducers/home';
import { fetchLeftNavItems as fetchNavigationItems } from 'merchant/reducers/leftNav';

import { trackEvents } from 'merchant/reducers/trackEvents';
import {
  checkEligibilityForFeeBasedGating,
  handleFeeBasedGatingNavigation,
} from 'merchant/utils/feeBasedGatingUtils';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import Divider from './components/Divider';
import NavLinkItem from './components/NavLinkItem';
import { Typo, Icon } from './components/NavLinkItem/styled';
import NavLinkProduct from './components/NavLinkProduct';
import {
  RZP_LOGO_URL,
  ONBOARDING_STEPS_URL,
  KYC_URL,
  ACTIVATION_URL,
  EASY_DASHBOARD_NC_LANDING_URL,
} from './constants/constants';
import {
  SidebarContainer,
  SidebarSection,
  Logo,
  NavContent,
  Navigation,
  ExternalLink,
} from './styled';
import { NavLinkData, Routes, SidebarPropsInterface } from './typings';
import { COMMON_PRODUCTS, PRODUCTS_DATA, CUSTOMERS_PRODUCTS } from './utils/Products';
import { getLeftNavItemsCache, setLeftNavItemsCache } from './utils/Sidebar';
import { getActiveTab, initializeRoutes } from './utils/href';

const SideBar = (props: SidebarPropsInterface): JSX.Element => {
  const {
    logoURL,
    config,
    user,
    fetchLeftNavItems,
    leftNavItems: { loading: isNavItemsLoading, data, error },
    org,
    isMobile,
    isTagsLoading,
    isNcEligibile,
    trackEvents,
  } = props;
  const { location, history } = props;

  const [routesInfo, setRoutesInfo] = useState<Routes>({});
  const [activeTab, setActiveTab] = useState<string>('');
  const isExternalRedirect = org?.external_redirect_url_text && org?.external_redirect_url;
  const [isTwoSecondsTimeoutReached, setIsTwoSecondsTimeoutReached] = useState(false);
  const [cachedLeftNavItems, setCacheLeftNavItems] = useState<NavLinkData[]>();
  const { signup_campaign } = (user?.user as Record<string, unknown>) ?? {};
  const isSignupWithEasyOnboarding = signup_campaign === EASY_ONBOARDING;
  const merchant = user?.merchants?.[user?.current as string];

  useEffect(() => {
    const leftNavItemsCache = getLeftNavItemsCache({ merchantId: merchant?.id });
    if (!leftNavItemsCache) {
      fetchLeftNavItems();
      setTimeout(() => {
        setIsTwoSecondsTimeoutReached(true);
      }, 4000);
    } else {
      setIsTwoSecondsTimeoutReached(true);
      setCacheLeftNavItems(leftNavItemsCache);
    }
  }, []);

  const handleActivationClick = () => {
    if (isNcEligibile && user.activation_status === 'needs_clarification') {
      trackEvents({
        objectName: 'NC Easy',
        actionName: 'Redirect',
        screen: 'home page',
        properties: {
          ctaLabel: 'Account Activation',
          ctaLocation: 'LHS_Nav_Bar_v2',
          ncCount: user?.kyc_clarification_reasons?.nc_count,
        },
        includeScreenResolution: true,
      });
      window.open(EASY_DASHBOARD_NC_LANDING_URL, '_self', 'noopener');
    } else if (checkEligibilityForFeeBasedGating(user)) {
      handleFeeBasedGatingNavigation({ ctaLocation: 'Sidebar' });
    } else if (isSignupWithEasyOnboarding) {
      analyticsTrack({
        objectName: 'redirect to easy-dashboard CTA',
        actionName: 'Redirect',
        screen: 'home page',
        properties: {
          'CTA Label': 'Account Activation',
        },
      });
      redirectToEasyAfter1sec();
    } else if (user.isOnboardingV2Enabled && isMobile) {
      history.push(ONBOARDING_STEPS_URL);
    } else if (user.isActivationFormFullView) {
      history.push(KYC_URL);
    } else {
      history.push(ACTIVATION_URL);
    }
  };

  useEffect(() => {
    const routes = initializeRoutes(location, user);
    setRoutesInfo(routes);
    setActiveTab(getActiveTab(location));
  }, [location.pathname]);

  useEffect(() => {
    if (getIsBankingEnabled(user)) {
      trackViewedBankingNavBar();
    }
  }, []);

  const commonNavLinkProps = {
    routes: routesInfo,
    activeTab,
    user,
  };

  // fallback to default list if it takes more than 2 seconds to load nav items
  const isLoading = !isTwoSecondsTimeoutReached && (isNavItemsLoading || isTagsLoading);

  if (!isNavItemsLoading && data && !error) {
    setLeftNavItemsCache({ merchantId: merchant?.id, leftNavItems: data });
  }

  const leftNavItems = cachedLeftNavItems || data;

  return (
    <BladeProvider themeTokens={Theme} colorScheme="light">
      <SidebarContainer>
        <SidebarSection>
          <Link to="/dashboard">
            <Logo src={logoURL || RZP_LOGO_URL} />
          </Link>
        </SidebarSection>
        <ShowWhen additionalCondition={() => !isOrgFeatureExist('hide_activation_form')}>
          <ActivationProgress
            onSidebarActivationClick={handleActivationClick}
            user={user}
            config={config}
          />
        </ShowWhen>
        <Navigation>
          <NavContent>
            <>
              {' '}
              <Box display="flex" flexDirection="column" gap="spacing.1">
                {COMMON_PRODUCTS.map((product, index) => (
                  <NavLinkItem
                    key={`${product.title}_${index}`}
                    {...commonNavLinkProps}
                    {...product}
                    {...PRODUCTS_DATA[product.product_id]}
                  />
                ))}
              </Box>
              <Divider />
              {leftNavItems.map((each) => (
                <NavLinkProduct
                  key={each.section_name}
                  heading={each.section_name}
                  products={each.product_options}
                  routes={routesInfo}
                  activeTab={activeTab}
                  loading={isLoading}
                  user={user}
                  {...each}
                />
              ))}
              {/* temporary solution until wallet is onboarded on merchant navigation API */}
              <NavLinkProduct
                key={LOYALTY_PRODUCTS_SECTION.section_name}
                heading={LOYALTY_PRODUCTS_SECTION.section_name}
                products={LOYALTY_PRODUCTS_SECTION.product_options}
                section_id={LOYALTY_PRODUCTS_SECTION.section_id}
                routes={routesInfo}
                activeTab={activeTab}
                loading={isLoading}
                user={user}
              />
              <Box display="flex" flexDirection="column" gap="spacing.1">
                {CUSTOMERS_PRODUCTS.map((product, index) => (
                  <NavLinkItem
                    key={`${product.title}_${index}`}
                    {...commonNavLinkProps}
                    {...product}
                    {...PRODUCTS_DATA[product.product_id]}
                  />
                ))}
                <ShowWhen
                  additionalCondition={() =>
                    isOrgFeatureExist('enable_external_redirect') && isExternalRedirect
                  }
                >
                  <ExternalLink
                    href={org?.external_redirect_url}
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    <Icon className="i i-external-link" />
                    <Typo>{org?.external_redirect_url_text}</Typo>
                  </ExternalLink>
                </ShowWhen>
              </Box>
            </>
          </NavContent>
        </Navigation>
      </SidebarContainer>
      <AcceptPaymentsModal
        isKLA={user.has_key_access}
        shouldShow={props.showAcceptPayments}
        onClose={props.hideAcceptPaymentsModal}
      />
    </BladeProvider>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    leftNavItems: state.leftNav,
    showAcceptPayments: state.home.instantActivations.showAcceptPayments,
    org: state.session.org,
    isNcEligibile: state.home.isNcEligibile,
    isMobile: state.app.isMobileResolution,
    isTagsLoading: !state.session.isTagsLoaded,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { fetchLeftNavItems: fetchNavigationItems, hideAcceptPaymentsModal, trackEvents },
    dispatch,
  );
};

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(SideBar));
