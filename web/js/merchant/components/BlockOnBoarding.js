import { Link } from '@razorpay/blade/components';
import ErrorImage from 'assets/error.svg';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

const BlockOnBoarding = ({ title, description }) => {
  return (
    <div className="blocked-onboarding-container">
      <div className="info">
        <div>
          <div className="heading">{title}</div>
          <div className="divider" />
          <div className="desc">{description}</div>
        </div>
      </div>

      <div className="error-img">
        <img src={ErrorImage} />
      </div>
    </div>
  );
};

export const BlockCustomerFeeBearerOnboarding = ({ feature }) => (
  <BlockOnBoarding
    title={feature}
    description={
      <>
        This product is not supported for merchants accepting payments as per the convenience fee
        model. If you wish to enable this product click{' '}
        <Link
          href={`/app${ROUTES_INFO.CAPTURE_AND_REFUND_SETTINGS}`}
          size="large"
          htmlTitle="open capture and refund settings"
        >
          here
        </Link>{' '}
        to switch to platform fee bearer model.
      </>
    }
  />
);

export default BlockOnBoarding;
