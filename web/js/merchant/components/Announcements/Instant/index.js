import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';
import * as LocalStorageService from 'common/utils/localStorage';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import SupportButton from 'merchant/components/Home/SupportButton';

import { trackGoToActivationFromError } from 'merchant/containers/Home/ga';
import Button from 'common/new-ui/Button';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import GenerateTnCPage from 'merchant/components/Home/GenerateTnCPage';
import { getCommonSegmentProperties, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import {
  getActivationState,
  getNcExpiryDate,
} from 'merchant/components/Activation/ActivationUtils';
import { showProductsModal, hideProductsModal } from 'merchant/reducers/home';
import ProductsModal from 'merchant/components/Home/ProductsModal';
import { trackProductsModal } from 'merchant/containers/Home/OnboardingCard/Instant/ga';
import { isMobileDevice } from 'merchant/components/Home/data';
import { getNCUrlOnEasyOrPhantom } from 'merchant/utils/urls';

@connect(
  (state) => ({
    showProducts: state.home.instantActivations.showProductsModal,
    isNcEligibile: state.home.isNcEligibile,
  }),
  { showProductsModal, openModal, closeModal, hideProductsModal },
)
@RTracking(() => window.rzpQ.component('InstantActivationAnnouncements'))
export default class InstantActivationAnnouncements extends Component {
  constructor(props) {
    super(props);
    this.state = {};
  }

  trackEvent = (eventOrigin) => {
    const { tracking } = this.props;
    tracking.trackEvent(
      window.rzpQ.onbr().initiated(`act.${eventOrigin}`, {
        clickSource: 'Instant_Announcement_Banner',
      }),
    );
  };

  sendL2StartEvent = () => {
    const isL1Submitted = this.props.user?.instantActivation?.isL1Submitted;
    if (isL1Submitted) {
      analyticsTrack({
        objectName: 'L2 Start',
        actionName: 'form fill initiated',
        screen: 'home page',
        properties: {
          clickSource: 'form submission popup',
          ...getCommonSegmentProperties(),
          milestone: 'L2 Start',
        },
      });
    }
  };

  trackNCEasyRedirect = (trackProps = {}) => {
    analyticsTrack({
      objectName: 'NC Resolve Now',
      actionName: 'Clicked',
      screen: 'home page',
      properties: {
        funnelStage: 'NC',
        formName: 'We need a few more details to complete KYC verification',
        ctaClicked: 'Resolve Now',
        clickSource: 'Banner',
        deviceType: isMobileDevice(768) ? 'mweb' : 'dweb',
        ...trackProps,
        ...getCommonSegmentProperties(window.rzp_user),
      },
    });
  };

  render() {
    const {
      user,
      mode,
      payments,
      tracking,
      // eslint-disable-next-line no-shadow
      openModal,
      // eslint-disable-next-line no-shadow
      closeModal,
      // eslint-disable-next-line no-shadow
      hideProductsModal,
      showProducts,
      shouldShowTnCBannerForAxis = false,
    } = this.props;
    const activationUrl = user.isActivationFormFullView ? '/kyc' : '/activation';
    const needsClarificationOnEasyUrl = getNCUrlOnEasyOrPhantom();
    const commonSettlementBanner = {
      theme: 'success',
      title: 'Account Activated',
    };
    let theme = 'warning';
    let title;
    let content = payments instanceof Object;

    const activationState = getActivationState(
      user,
      user.isUnregisteredBusiness,
      this.props.isNcEligibile,
    );

    const expiryDate = getNcExpiryDate(user?.kyc_clarification_reasons);

    if (user.isInstantActivationEnabled) {
      switch (activationState) {
        case 'L1_dedupe_blocked':
        case 'L2_dedupe_blocked': {
          theme = 'danger';
          title = 'Business not supported';
          content = (
            <div class="announcement-container">
              <div class="announcement-info">
                Your current business category is not supported by our banking partners. If you wish
                to reconsider and update, please reach out to us via support.
              </div>
              <div className="big-circle-seprator" />
              <SupportButton
                type="anchor"
                buttonLabel="Contact Support"
                category="merchant"
                openSection="account-activation"
              />
            </div>
          );
          break;
        }
        case 'poi_failed':
        case 'payment_disabled': {
          theme = 'warning';
          title = 'Complete KYC details';
          content = (
            <div class="announcement-container">
              <div class="announcement-info">
                Please submit your KYC details to get your account activated and start accepting
                payments{' '}
              </div>
              <div className="big-circle-seprator" />
              <Link to={activationUrl} onClick={() => this.sendL2StartEvent()}>
                Complete KYC
              </Link>
            </div>
          );
          break;
        }
        case 'under_review_with_tnc_partial': {
          theme = 'warning';
          title = (
            <div>
              Payment paused <br /> temporarily
            </div>
          );
          content = (
            <div>
              Our compliance team and banking partners are reviewing your KYC and your payments have
              been temporarily paused. We will review your KYC and reach out to you for any
              clarifications within 3-4 days.
            </div>
          );
          break;
        }
        case 'under_review_without_tnc_partial': {
          theme = user.isOrgAxis ? 'burgundy' : 'warning';
          title = (
            <div>
              Payment paused <br /> temporarily
            </div>
          );
          content = (
            <div class="announcement-container">
              <div class="announcement-info">
                Our compliance team and banking partners are reviewing your KYC and your payments
                have been temporarily paused. We will reach out to you for any clarifications.
                within 3-4 days Meanwhile you can generate your Tnc page{' '}
              </div>
              <div className="big-circle-seprator" />
              <Button.Secondary
                type="button"
                children="Generate Page now"
                onClick={() => {
                  openModal({
                    size: 'small',
                    component: <GenerateTnCPage onCloseModal={closeModal} openModal={openModal} />,
                  });
                  tracking.trackEvent(
                    window.rzpQ.onbr().initiated('act.generate_page_now', {
                      clickSource: 'Banner',
                    }),
                  );
                  analyticsTrack({
                    objectName: 'Act Generate Page Now',
                    actionName: 'initiated',
                    screen: 'home page',
                    properties: {
                      clickSource: 'Banner',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
              />
            </div>
          );

          break;
        }
        case 'under_review_with_tnc_passed': {
          theme = 'warning';
          title = <div>Payment limits removed</div>;
          content = (
            <div>
              You can accept unlimited payments now. Settlements will be enabled after we
              successfully review your KYC details. It usually takes 3-4 business days. We will
              notify you if we require any clarifications on your KYC
            </div>
          );
          break;
        }
        case 'under_review_without_tnc_passed': {
          theme = user.isOrgAxis ? 'burgundy' : 'warning';
          title = (
            <div>
              Payment limits removed,
              <br /> Generate TnC
            </div>
          );
          content = (
            <div class="announcement-container">
              <div class="announcement-info">
                You can accept unlimited payments now. Settlements will be enabled after we
                successfully review your KYC details. It usually takes 3-4 business days. Generate
                TnC page at the earliest, failing which KYC review might get delayed{' '}
              </div>
              <div className="big-circle-seprator" />
              <Button.Secondary
                type="button"
                children="Generate Page now"
                onClick={() => {
                  openModal({
                    size: 'small',
                    component: <GenerateTnCPage onCloseModal={closeModal} openModal={openModal} />,
                  });
                  tracking.trackEvent(
                    window.rzpQ.onbr().initiated('act.generate_page_now', {
                      clickSource: 'Banner',
                    }),
                  );
                  analyticsTrack({
                    objectName: 'Act Generate Page Now',
                    actionName: 'initiated',
                    screen: 'home page',
                    properties: {
                      clickSource: 'Banner',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
              />
            </div>
          );

          break;
        }
        case 'under_review_with_tnc': {
          theme = 'warning';
          title = <div>KYC Under Review</div>;
          content = (
            <div>
              We are reviewing your KYC details. It usually takes 3-4 business days. We will notify
              you if we require any clarifications on your KYC
            </div>
          );
          break;
        }
        case 'under_review_without_tnc': {
          theme = user.isOrgAxis ? 'burgundy' : 'warning';
          title = <div>Generate TnC Page</div>;
          content = (
            <div class="announcement-container">
              <div class="announcement-info">
                Generate the terms and conditions page for your business. KYC review might get
                delayed in case of delays in generating TnC{' '}
              </div>
              <div className="big-circle-seprator" />
              <Button.Secondary
                type="button"
                children="Generate Page now"
                onClick={() => {
                  openModal({
                    size: 'small',
                    component: <GenerateTnCPage onCloseModal={closeModal} openModal={openModal} />,
                  });
                  tracking.trackEvent(
                    window.rzpQ.onbr().initiated('act.generate_page_now', {
                      clickSource: 'Banner',
                    }),
                  );
                  analyticsTrack({
                    objectName: 'Act Generate Page Now',
                    actionName: 'initiated',
                    screen: 'home page',
                    properties: {
                      clickSource: 'Banner',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                }}
              />
            </div>
          );

          break;
        }
        case 'needs_clarification_mcc_pending': {
          theme = 'danger';
          title = 'KYC Clarification';
          content = (
            <div class="announcement-container">
              <div class="announcement-info">
                Your KYC details require further clarifications. Update required details within 1
                day, otherwise your settlements might get paused.{' '}
              </div>
              <div className="big-circle-seprator" />
              <Link to={activationUrl}>Update details</Link>
            </div>
          );
          break;
        }
        case 'needs_clarification_funds_on_hold': {
          theme = 'danger';
          title = 'settlements have been paused';
          content = (
            <div class="announcement-container">
              <div class="announcement-info">
                Your funds are on hold now. Update required details immediately to unblock your
                settlements{' '}
              </div>
              <div className="big-circle-seprator" />
              <Link to={activationUrl}>Update details</Link>
            </div>
          );
          break;
        }
        case 'needs_clarification': {
          theme = 'danger';
          title = 'KYC Clarification';
          content = (
            <div class="announcement-container">
              <div class="announcement-info">
                We need some clarfication regarding your KYC details. Please clarify at the earliest
                to get your KYC approved{' '}
              </div>
              <div className="big-circle-seprator" />
              <Link to={activationUrl}>Update details</Link>
            </div>
          );
          break;
        }
        case 'needs_clarification_payments_settlement_enabled': {
          theme = 'danger';
          title = 'Action required';
          content = (
            <div class="announcement-container">
              <div>
                <div class="announcement-info-header">
                  We need a few more details to complete KYC verification.
                </div>
                <div class="full-width-info">
                  {`You will not be able to receive payments in your bank account if the required details are not updated before ${expiryDate}`}
                </div>
              </div>
              <div className="big-circle-seprator" />
              <button
                class="btn-link"
                type="button"
                onClick={() => {
                  this.trackNCEasyRedirect({
                    activationState,
                    ncCount: `${user?.kyc_clarification_reasons?.nc_count}`,
                  });
                  window.open(needsClarificationOnEasyUrl);
                }}
              >
                <strong>Resolve Now</strong>
              </button>
            </div>
          );
          break;
        }
        case 'needs_clarification_with_payments_enabled': {
          theme = 'danger';
          title = 'Action required';
          content = (
            <div class="announcement-container">
              <div>
                <div class="announcement-info-header">
                  We need a few more details to complete KYC verification.
                </div>
                <div class="full-width-info">
                  You’ll be able to collect payments and receive them in your bank account only
                  after the required details are updated
                </div>
              </div>
              <div className="big-circle-seprator" />
              <button
                class="btn-link"
                type="button"
                onClick={() => {
                  this.trackNCEasyRedirect({
                    activationState,
                    ncCount: `${user?.kyc_clarification_reasons?.nc_count}`,
                  });
                  window.open(needsClarificationOnEasyUrl);
                }}
              >
                <strong>Resolve Now</strong>
              </button>
            </div>
          );
          break;
        }
        case 'needs_clarification_with_payment_disabled': {
          theme = 'danger';
          title = 'Action required';
          content = (
            <div class="announcement-container">
              <div>
                <div class="announcement-info-header">
                  We need a few more details to complete KYC verification.
                </div>
                <div class="full-width-info">
                  Your payments acceptance and settlement to your bank account will be made live
                  after getting the required inputs
                </div>
              </div>
              <div className="big-circle-seprator" />
              <button
                class="btn-link"
                type="button"
                onClick={() => {
                  this.trackNCEasyRedirect({
                    activationState,
                    ncCount: `${user?.kyc_clarification_reasons?.nc_count}`,
                  });
                  window.open(needsClarificationOnEasyUrl);
                }}
              >
                <strong>Resolve Now</strong>
              </button>
            </div>
          );
          break;
        }
        case 'rejected': {
          theme = 'danger';
          title = 'Account Rejected';
          content = (
            <div class="announcement-container">
              <div class="announcement-info">
                We can't support your business because it doesn't meet our compliance requirements.
                Please raise a ticket to request for settlement of payments accepted via your
                account{' '}
              </div>
            </div>
          );
          break;
        }
        case 'account_activated': {
          theme = 'success';
          title = 'Account Activated';
          content = (
            <div>
              Congratulations! Your KYC has been successfully verified and your account has been
              activated. Payments received by you will be settled to your account as per your
              settlement schedule.
            </div>
          );
          break;
        }
        case 'funds_on_hold': {
          theme = 'danger';
          title = 'KYC Under Review';
          content = (
            <div>
              Our compliance team and banking partners are reviewing your KYC and your funds have
              been temporarily put on hold. We will review your KYC and reach out to you for any
              clarifications within 3-4 days
            </div>
          );
          break;
        }
        case 'activated_mcc_pending_with_tnc': {
          theme = 'success';
          title = (
            <div>
              Payment and <br />
              Settlements Enabled
            </div>
          );
          content = (
            <div>
              Congratulations, now you can accept unlimited payments. Settlements to your bank
              account have been enabled. Please note that as part of routine compliance checks
              mandated by our banking partners, we may review your KYC again and reach out in case
              of further clarifications
            </div>
          );
          break;
        }
        case 'activated_mcc_pending_without_tnc': {
          theme = 'success';
          title = (
            <div>
              Settlements enabled, <br />
              Generate TnC Page
            </div>
          );
          content = (
            <div class="announcement-container">
              <div class="announcement-info">
                Your payments can now be settled to your bank account according to your settlement
                schedule. As part of the routine compliance checks mandated by our banking partners,
                we will review your kyc and reach out for further
              </div>
              <div className="big-circle-seprator" />
              <div>
                <Button.Secondary
                  type="button"
                  children="Generate Page now"
                  onClick={() => {
                    openModal({
                      size: 'small',
                      component: (
                        <GenerateTnCPage onCloseModal={closeModal} openModal={openModal} />
                      ),
                    });
                    tracking.trackEvent(
                      window.rzpQ.onbr().initiated('act.generate_page_now', {
                        clickSource: 'Banner',
                      }),
                    );
                    analyticsTrack({
                      objectName: 'Act Generate Page Now',
                      actionName: 'initiated',
                      screen: 'home page',
                      properties: {
                        clickSource: 'Banner',
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                  }}
                />
              </div>
            </div>
          );
          break;
        }

        default:
          break;
      }
    } else if (!user.isSubmitted) {
      if (user.instantActivation.isWhitelistFlow && !user.isAccepted && mode === 'live') {
        if (payments && payments.items.length === 0) {
          title = 'Accept Payments';
          content = (
            <span>
              You can start using our products to accept payments right away. Meanwhile we will
              await your KYC details to enable settlements for your account. &nbsp;
              <Link to={activationUrl} onClick={this.sendL2StartEvent}>
                Fill KYC Form
              </Link>
            </span>
          );
        } else if (payments && payments.items.length > 0) {
          title = 'Enable Settlements';
          content = (
            <span>
              You can continue accepting payments from your customers. However, you must complete
              KYC for the payments to be settled to your account. &nbsp;
              <Link to={activationUrl} onClick={this.sendL2StartEvent}>
                Fill KYC Form
              </Link>
            </span>
          );
        }
      } else if (user.instantActivation.isGraylistFlow) {
        title = 'Submit your KYC';
        content = (
          <span>
            In order to enable payments for your business model we need your KYC Details. &nbsp;
            <Link to={activationUrl} onClick={this.sendL2StartEvent}>
              Fill KYC Form
            </Link>
          </span>
        );
      } else if (payments && payments.items.length > 0 && !user.isAccepted && mode === 'live') {
        title = 'Enable Settlements';
        content = (
          <span>
            You can continue accepting payments from your customers. However, you must complete KYC
            for the payments to be settled to your account. &nbsp;
            <Link to={activationUrl} onClick={this.sendL2StartEvent}>
              Fill KYC Form
            </Link>
          </span>
        );
      } else if (user.isActivated && user.bank_details_verification_status == 'failed') {
        theme = 'danger';
        title = 'Bank Verification Failed';
        content = 'We were unable to verify your bank account. Please upload bank account proof.';
      } else if (user.business_type == 11) {
        if (user.isActivated && user.poi_verification_status == 'verified') {
          theme = 'success';
          title = 'Accept Payments';
          content = (
            <span>
              Your PAN was successfully verified and you can start accepting domestic payments now.
              Meanwhile we will await your KYC details to enable settlements for your account.
              &nbsp;
              <Link to={activationUrl} onClick={this.sendL2StartEvent}>
                Fill KYC Form
              </Link>
            </span>
          );
        } else if (user.poi_verification_status == 'failed' && !user.canSkipPoiValidation) {
          theme = 'danger';
          title = 'Unable To Verify PAN';
          content = (
            <React.Fragment>
              Government’s PAN database seems to be down, we couldn’t verify your PAN Details.
              Please try again in a couple of minutes. &nbsp;
              <Link
                to="/kyc?auto-submit=l1-form"
                onClick={() => {
                  this.trackEvent('nav_try_again');
                }}
              >
                Try Again
              </Link>
            </React.Fragment>
          );
        } else if (
          (user.poi_verification_status == 'incorrect_details' ||
            user.poi_verification_status == 'not_matched') &&
          !user.canSkipPoiValidation
        ) {
          theme = 'danger';
          title = 'PAN Verification Failed';
          content = (
            <React.Fragment>
              Your PAN details did not match with the government PAN database. Please review and
              submit again. &nbsp;
              <Link
                to={activationUrl}
                onClick={() => {
                  this.trackEvent('nav_review_details');
                  trackGoToActivationFromError();
                }}
              >
                Review Details
              </Link>
            </React.Fragment>
          );
        } else return null;
      } else {
        return null;
      }
    } else {
      const isActivatedMccPending = user.activation_status === 'activated_mcc_pending';
      if (user.isAccepted) {
        if (!user.isNPSSurveyBannerEnabled && !user.isCovidFeatureEnabled) {
          theme = commonSettlementBanner.theme;
          title = commonSettlementBanner.title;
          content =
            'You can start accepting payments now. Payments will be settled to your bank account according to your settlement schedule.';
        } else return null;
      } else if (user.isRejected || user.needsClarification) {
        theme = 'danger';

        if (user.isRejected) {
          title = 'Account Suspended';
          content =
            'Due to irregularities in documents submitted by you, your account has been suspended. You will not be able to conduct live transactions';
        } else if (activationState === 'needs_clarification_payments_settlement_enabled') {
          theme = 'danger';
          title = 'Action required';
          content = (
            <div class="announcement-container">
              <div>
                <div class="announcement-info-header">
                  We need a few more details to complete KYC verification.
                </div>
                <div class="full-width-info">
                  {`You will not be able to receive payments in your bank account if the required details are not updated before ${expiryDate}`}
                </div>
              </div>
              <div className="big-circle-seprator" />
              <button
                class="btn-link"
                type="button"
                onClick={() => {
                  this.trackNCEasyRedirect({
                    activationState,
                    ncCount: `${user?.kyc_clarification_reasons?.nc_count}`,
                  });
                  window.open(needsClarificationOnEasyUrl);
                }}
              >
                <strong>Resolve Now</strong>
              </button>
            </div>
          );
        } else if (activationState === 'needs_clarification_with_payments_enabled') {
          theme = 'danger';
          title = 'Action required';
          content = (
            <div class="announcement-container">
              <div>
                <div class="announcement-info-header">
                  We need a few more details to complete KYC verification.
                </div>
                <div class="full-width-info">
                  You’ll be able to receive collected payments in your account only after the
                  required details are updated
                </div>
              </div>
              <div className="big-circle-seprator" />
              <button
                class="btn-link"
                type="button"
                onClick={() => {
                  this.trackNCEasyRedirect({
                    activationState,
                    ncCount: `${user?.kyc_clarification_reasons?.nc_count}`,
                  });
                  window.open(needsClarificationOnEasyUrl);
                }}
              >
                <strong>Resolve Now</strong>
              </button>
            </div>
          );
        } else if (activationState === 'needs_clarification_with_payment_disabled') {
          theme = 'danger';
          title = 'Action required';
          content = (
            <div class="announcement-container">
              <div>
                <div class="announcement-info-header">
                  We need a few more details to complete KYC verification.
                </div>
                <div class="full-width-info">
                  You’ll be able to collect payments and receive them in your bank account only
                  after the required details are updated
                </div>
              </div>
              <div className="big-circle-seprator" />
              <button
                class="btn-link"
                type="button"
                onClick={() => {
                  this.trackNCEasyRedirect({
                    activationState,
                    ncCount: `${user?.kyc_clarification_reasons?.nc_count}`,
                  });
                  window.open(needsClarificationOnEasyUrl);
                }}
              >
                <strong>Resolve Now</strong>
              </button>
            </div>
          );
        } else {
          title = 'KYC Clarification';
          content = (
            <React.Fragment>
              Your KYC details require further clarifications. Update required details within 1 day,
              otherwise your settlements might get paused. &nbsp;
              <Link to={activationUrl} style={{ 'font-weight': 'bold' }}>
                Update Details
              </Link>
              .
            </React.Fragment>
          );
        }
      } else if (user.isHardLimitReached) {
        theme = 'warning';
        title = 'Account Under Review';
        content = (
          <>
            Our compliance team and partner banks carry out routine audits of your KYC documents. We
            might temporarily pause your settlements during this time, but don't worry, just look
            for clarifications asked by our team on your registered email. Once we receive the
            clarifications, we will resume your settlements. Upon receiving your response, we will
            be able to process the application within 2 days and re enable settlements for you.
            Please note, you can still accept payments from your customers.{' '}
            <a
              href="https://knowledgebase.razorpay.com/support/solutions/articles/11000103841-why-is-my-settle[%E2%80%A6]ld-and-my-account-under-review-after-getting-activated"
              target="_blank"
              rel="noreferrer noopener"
            >
              More details
            </a>
          </>
        );
      } else if (
        isActivatedMccPending &&
        (user.business_website || user.merchant_tnc || !user.canGenerateTnCPage)
      ) {
        theme = commonSettlementBanner.theme;
        title = commonSettlementBanner.title;
        content =
          'Congratulations! You can start accepting payments now. Payments will be settled to your bank account according to your settlement schedule. Please note that as part of the routine compliance checks mandated by our banking partners, we will review your business model, website details and reach out for further clarifications.';
      } else if (user.activation_status === 'kyc_qualified_unactivated') {
        theme = 'success';
        title = 'KYC verified successfully';
        content =
          'We are working to take your account live in the next 2-3 days and you will be able to start accepting payments immediately post that. There is no action required from your end. We thank you for your patience.';
      } else if (user.activation_status === 'under_review' && !!user.locked && user.isDedupe) {
        title = 'Contact Support';
        content = (
          <>
            We need more information regarding your submitted details. Please{' '}
            <SupportButton
              type="anchor"
              buttonLabel="Contact Support"
              category="merchant"
              openSection="account-activation"
            />{' '}
            to complete your activation.
          </>
        );
      } else if (!user.business_website && !user.merchant_tnc && user.canGenerateTnCPage) {
        title = isActivatedMccPending ? (
          <span>
            Account Activated,
            <br /> Generate TnC Page
          </span>
        ) : (
          'Generate TnC Page'
        );
        theme = isActivatedMccPending ? 'success' : 'warning';
        content = (
          <div style={{ display: 'flex', alignItems: 'center' }}>
            {isActivatedMccPending ? (
              <div style={{ maxWidth: '80%' }}>
                You can start accepting payments now. Payments will be settled to your bank account
                according to your settlement schedule. Please generate the TnC page at the earliest.
                Your payments can be put on hold in case of failure to do so
              </div>
            ) : (
              'Generate the terms and conditions page for your business. KYC review might get delayed in case of delays in generating TnC'
            )}
            <div className="big-circle-seprator" />
            <Button.Secondary
              type="button"
              children="Generate Page now"
              onClick={() => {
                openModal({
                  size: 'small',
                  component: <GenerateTnCPage onCloseModal={closeModal} openModal={openModal} />,
                });
                tracking.trackEvent(
                  window.rzpQ.onbr().initiated('act.generate_page_now', {
                    clickSource: 'Banner',
                  }),
                );
                analyticsTrack({
                  objectName: 'Act Generate Page Now',
                  actionName: 'initiated',
                  screen: 'home page',
                  properties: {
                    clickSource: 'Banner',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
              }}
            />
          </div>
        );
      } else {
        let activation_tat = '1-2 days';
        const clarification_submitted = LocalStorageService.getItem(
          `rzp_onboarding--${user.current}--clarification_submitted`,
        );
        if (clarification_submitted) {
          activation_tat = '4-5 days';
        }
        title = 'KYC Under Review';
        if (user.instantActivation.isWhitelistFlow) {
          if (payments && payments.items.length > 0 && mode === 'live') {
            content = (
              <>
                We will be reviewing your KYC details after your first transaction. Review process
                usually takes {activation_tat}{' '}
                <strong>from the date of the first transaction</strong>, we will reach out to you on
                your registered email ID if we need any clarifications. Your settlements will be
                enabled after your KYC is reviewed and approved.
              </>
            );
          } else {
            title = 'Accept Payments';
            content = (
              <React.Fragment>
                You can start using our products to accept payments right away, however your
                settlements will be enabled after your KYC is reviewed. KYC Review process usually
                takes {activation_tat} <strong>from the date of the first transaction</strong>, we
                will reach out to you on your registered email ID if we need any clarifications.
                &nbsp;
                <a
                  href="https://razorpay.freshdesk.com/support/solutions/articles/11000092582"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  Know more
                </a>
              </React.Fragment>
            );
          }
        } else if (mode !== 'live') {
          content = (
            <React.Fragment>
              KYC Review process usually takes{' '}
              {user.kyc_clarification_reasons?.nc_count ? '3' : '3 - 4'} working days. We will
              notify you if we require any clarifications on your KYC.
              <button
                className="btn-link cursor-pointer"
                style={{ padding: '0' }}
                onClick={() => this.props.showProductsModal()}
              >
                you can try our products in test mode.
              </button>
            </React.Fragment>
          );
        } else {
          content = (
            <React.Fragment>
              KYC Review process usually takes{' '}
              {user.kyc_clarification_reasons?.nc_count ? '3' : '3 - 4'} working days. We will
              notify you if we require any clarifications on your KYC.
            </React.Fragment>
          );
        }
      }
    }

    return (
      <>
        {!!title ? (
          <AnnouncementBanner
            title={title}
            theme={theme}
            bannerKey={`announcement-banner-${user.activation_status}-${user.current}`}
            canBeClosed={user.isAccepted}
            shouldShowTnCBannerForAxis={shouldShowTnCBannerForAxis}
            card_id="instant-activation-status-banner"
          >
            {content}
          </AnnouncementBanner>
        ) : null}
        {showProducts ? (
          <ProductsModal onClose={hideProductsModal} track={trackProductsModal} />
        ) : null}
      </>
    );
  }
}
