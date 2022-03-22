import Button from 'common/new-ui/Button';
import { OFFER_DETAILS } from '../../ConnectedBanking/data';
import FeaturesList from '../../ConnectedBanking/components/FeaturesList';
import { analyticsTrack } from 'common/utils/analytics';

const featuresList = OFFER_DETAILS.ICICI.content.featuresList;

const CAMPAIGN_VALUE = 'pg_x_widget';

const OffersPage = ({ prev }) => {
  const handleBackButton = () => {
    prev();
  };

  const handleGetStartedButton = () => {
    analyticsTrack({
      objectName: 'RazorayX Get Started',
      actionName: 'Clicked',
      screen: 'RazorpayX Onboarding',
    });

    window.open(
      `${window.bankingServiceUrl}/welcome?campaign=${CAMPAIGN_VALUE}&intent=current_account`,
      '_blank',
      'noopener noreferrer',
    );
  };

  return (
    <div className="offers-page">
      <div className="offers-page--header">
        <h1>
          What makes <span className="text-gradient">RazorpayX</span> great?
        </h1>
        <div>
          <a href="https://razorpay.com/x/" rel="noopener noreferrer" target="_blank">
            Know more
          </a>
          <span className="bullet">•</span>
          <a href="https://razorpay.com/docs/x/" rel="noopener noreferrer" target="_blank">
            View API Docs
          </a>
        </div>
      </div>
      <div className="offers-page--content">
        <FeaturesList featuresList={featuresList} />
      </div>
      <div class="Button-Container">
        <Button.Transparent
          className="Back-Button"
          iconBefore="arrow-back"
          onClick={handleBackButton}
        >
          Back
        </Button.Transparent>
        <Button
          className="Forward-Button"
          iconAfter="arrow-forward"
          onClick={handleGetStartedButton}
        >
          Get started
        </Button>
      </div>
    </div>
  );
};

export default OffersPage;
