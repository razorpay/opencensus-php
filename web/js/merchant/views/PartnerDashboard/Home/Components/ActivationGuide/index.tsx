import React from 'react';
import ProductShimmer from 'merchant/views/PartnerDashboard/Home/Components/ReferralGuide/shimmer';
import {
  ActivationStatesT,
  AddMerchantSource,
  FUXStatusStateT,
  RTrackingT,
} from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import {
  StartReferringStep,
  ActivateAccountStep,
  IntegratingAPIStep,
  CommissionStep,
} from 'merchant/views/PartnerDashboard/Home/Components/ActivationGuide/ActivationStepVariants';
import { withRouter } from 'common/deprecated/withRouter';
import { History } from 'history';
import rTracking from 'react-tracking';
import { compose } from 'redux';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';

interface ActivationGuideT {
  fuxStatus: FUXStatusStateT;
  user: any;
  partnerName: string;
  history: History;
  handleReferClient: (source: AddMerchantSource, arg?: string) => void;
  tracking: RTrackingT;
  org: TODO_PD;
}
const ActivationGuide = ({
  history,
  handleReferClient,
  fuxStatus,
  user,
  partnerName,
  tracking,
  org,
}: ActivationGuideT): JSX.Element | null => {
  const cdnBase = `${window.cdnBaseUrl}/static/assets/partner-dashboard/fux-cards/activation-guide`;
  const activationTitleIcon = `${cdnBase}/activation-title-icon.svg`;

  const activation_status = user.activation_status as ActivationStatesT;
  const isFirstInvoiceGen = fuxStatus.value?.first_commission_payout === true;
  const orgName = org?.business_name || 'Razorpay';

  if (isFirstInvoiceGen) return null;

  const trackUserEvent = (eventName: string, properties: Record<string, unknown> = {}): void => {
    tracking?.trackEvent(
      window?.rzpQ?.onbr()?.interaction(eventName, {
        ...properties,
      }),
    );
  };
  return (
    <ErrorBoundary team={Teams?.PARTNERSHIP} rank={Ranks.P1} resetOnProps>
      <div>
        {fuxStatus.isFetching ? (
          <ProductShimmer />
        ) : (
          <div className="activation-guide-card">
            <div className="title-bar">
              <div className="confetti-wrapper">
                <canvas id="confettiActivationGuideMob" />
              </div>
              <div className="title-bar__container">
                <div className="title">
                  <span>Start your journey as {orgName} Partner</span>
                </div>

                <div className="sub-title">
                  <span>
                    Complete the steps to start earning commissions and pamper yourself to exclusive
                    training & dedicated support.
                  </span>
                </div>

                <img src={activationTitleIcon} alt="" />
              </div>
            </div>
            <div className="activation-steps">
              <div className="confetti-wrapper">
                <canvas id="confettiActivationGuide" />
              </div>

              <StartReferringStep
                handleReferClient={handleReferClient}
                fuxStatus={fuxStatus}
                partnerName={partnerName}
                partnerType={user.partner_type}
                orgName={orgName}
              />

              <ActivateAccountStep
                history={history}
                fuxStatus={fuxStatus}
                activation_status={activation_status}
                trackUserEvent={trackUserEvent}
                partnerType={user.partner_type}
              />

              <IntegratingAPIStep
                activation_status={activation_status}
                fuxStatus={fuxStatus}
                partnerType={user.partner_type}
                orgName={orgName}
              />

              <CommissionStep
                activation_status={activation_status}
                fuxStatus={fuxStatus}
                partnerType={user.partner_type}
                history={history}
                trackUserEvent={trackUserEvent}
              />
            </div>
          </div>
        )}
      </div>
    </ErrorBoundary>
  );
};

export default compose<any>(
  rTracking(() => window.rzpQ.component('ActivationGuide')),
  withRouter,
)(ActivationGuide);
