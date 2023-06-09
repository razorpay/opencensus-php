import React, { useEffect, useState } from 'react';
import { withRouter, Link } from 'react-router-dom';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import NavLinkItem from './components/NavLinkItem';
import Divider from './components/Divider';
import NavLinkProduct from './components/NavLinkProduct';
import { COMMON_PRODUCTS, PRODUCTS_DATA, CUSTOMERS_PRODUCTS } from './utils/Products';
import { fetchLeftNavItems as fetchNavigationItems } from 'merchant/reducers/leftNav';
import { isOrgFeatureExist } from 'merchant/models/User';
import ShowWhen from 'merchant/components/ShowWhen';
import ActivationProgress from 'merchant/components/SidebarV2/components/ActivationProgress';
import { redirectToEasyAfter1sec } from 'merchant/components/Activation/ActivationUtils';
import {
  RZP_LOGO_URL,
  ONBOARDING_STEPS_URL,
  KYC_URL,
  ACTIVATION_URL,
  EASY_DASHBOARD_NC_LANDING_URL,
} from './constants/constants';
import { getActiveTab, initializeRoutes } from './utils/href';
import {
  SidebarContainer,
  SidebarSection,
  Logo,
  Items,
  NavContent,
  Navigation,
  ExternalLink,
} from './styled';
import AcceptPaymentsModal from 'merchant/containers/Home/OnboardingCard/Instant/AcceptPaymentsModal';
import { hideAcceptPaymentsModal } from 'merchant/reducers/home';
import { Typo, Icon } from './components/NavLinkItem/styled';
import { Routes, SidebarPropsInterface } from './typings';
import { trackViewedBankingNavBar } from 'merchant/components/Sidebar/ga';
import { getIsBankingEnabled } from 'merchant/components/Sidebar/helpers';
import { LOYALTY_PRODUCTS_SECTION } from 'merchant/components/SidebarV2/utils/Fallback';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { analyticsTrack } from 'common/utils/analytics';
import { trackEvents } from 'merchant/reducers/trackEvents';

const SideBar = (props: SidebarPropsInterface): JSX.Element => {
  const {
    logoURL,
    config,
    user,
    fetchLeftNavItems,
    leftNavItems: { loading: isNavItemsLoading, data },
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
  const { signup_campaign } = (user?.user as Record<string, unknown>) ?? {};
  const isSignupWithEasyOnboarding = signup_campaign === EASY_ONBOARDING;

  useEffect(() => {
    fetchLeftNavItems();
    setTimeout(() => {
      setIsTwoSecondsTimeoutReached(true);
    }, 2000);
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

  return (
    <>
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
              <Items>
                {COMMON_PRODUCTS.map((product, index) => (
                  <NavLinkItem
                    key={`${product.title}_${index}`}
                    {...commonNavLinkProps}
                    {...product}
                    {...PRODUCTS_DATA[product.product_id]}
                  />
                ))}
              </Items>
              <Divider />
              {data.map((each) => (
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
              <Items>
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
              </Items>
            </>
          </NavContent>
        </Navigation>
      </SidebarContainer>
      <AcceptPaymentsModal
        isKLA={user.has_key_access}
        shouldShow={props.showAcceptPayments}
        onClose={props.hideAcceptPaymentsModal}
      />
    </>
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
