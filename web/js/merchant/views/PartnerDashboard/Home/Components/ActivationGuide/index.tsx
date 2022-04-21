import React from 'react';
import ProductShimmer from 'merchant/views/PartnerDashboard/Home/Components/ReferralGuide/shimmer';
import {
  ActivationStatesT,
  AddMerchantSource,
  FUXStatusStateT,
} from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import {
  StartReferringStep,
  ActivateAccountStep,
  IntegratingAPIStep,
  CommissionStep,
} from 'merchant/views/PartnerDashboard/Home/Components/ActivationGuide/ActivationStepVariants';
import { withRouter } from 'react-router-dom';
import { History } from 'history';

interface ActivationGuideT {
  fuxStatus: FUXStatusStateT;
  user: any;
  partnerName: string;
  history: History;
  handleReferClient: (source: AddMerchantSource, arg?: string) => void;
}
const ActivationGuide = ({
  history,
  handleReferClient,
  fuxStatus,
  user,
  partnerName,
}: ActivationGuideT): JSX.Element | null => {
  const cdnBase = `${window.cdnBaseUrl}/static/assets/partner-dashboard/fux-cards/activation-guide`;
  const activationTitleIcon = `${cdnBase}/activation-title-icon.svg`;

  const activation_status = user.activation_status as ActivationStatesT;
  const isFirstInvoiceGen = fuxStatus.value?.first_commission_payout === true;

  if (isFirstInvoiceGen) return null;

  return (
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
                <span>Start your journey as Razorpay Partner</span>
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
            />

            <ActivateAccountStep
              history={history}
              fuxStatus={fuxStatus}
              activation_status={activation_status}
            />

            <IntegratingAPIStep
              activation_status={activation_status}
              fuxStatus={fuxStatus}
              partnerType={user.partner_type}
            />

            <CommissionStep
              activation_status={activation_status}
              fuxStatus={fuxStatus}
              partnerType={user.partner_type}
              history={history}
            />
          </div>
        </div>
      )}
    </div>
  );
};

export default withRouter<any, any>(ActivationGuide);
