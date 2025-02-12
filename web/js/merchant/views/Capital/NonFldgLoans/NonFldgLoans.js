import Button from 'common/new-ui/Button';
import { OnBoardingWrapper } from 'merchant/components/OnBoarding';
import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import UpRightLogo from '../../../../../css/assets/capital/arrow-up-right.svg';
import { NON_FLDG_LOANS_DATA } from './constants';
import { Navigate } from 'react-router-dom';
import { trackFirstScreenRender, trackClickHandler } from './trackEvents';
import { Container } from './styles';

const renderFldgSideModal = () => {
  const {
    title = '',
    tips = [],
    action_point = '',
    description = '',
    ctaText = '',
    redirectTo = '',
  } = NON_FLDG_LOANS_DATA;

  return (
    <Container>
      <div className="title">{title}</div>
      <div className="inner">
        <p className="description">{description}</p>
        {tips.length > 0 && (
          <div className="tips-container">
            {tips.map((item) => (
              <div key={item} className="flex wrapper">
                <i className="i i-check text-success" />
                <p className="text tip">{item}</p>
              </div>
            ))}
          </div>
        )}
        <p className="text action-point">{action_point}</p>
        <a href={redirectTo} target="_blank" rel="noreferrer noopener">
          <Button.Primary className="text cta" onClick={trackClickHandler}>
            {ctaText}
            <img src={UpRightLogo} alt="UpRightLogo" />
          </Button.Primary>
        </a>
      </div>
    </Container>
  );
};

const NonFldgLoans = ({ user }) => {
  useEffect(() => {
    if (user.isNonFldgLoansEnabled) trackFirstScreenRender();
  }, []);

  if (!user.isNonFldgLoansEnabled) return <Navigate to="/" replace />;

  return (
    <OnBoardingWrapper className="Loans">
      <div className="Landing--Image">
        <div className="image-wrapper">
          <img src={require("assets/capital/los_onboarding_hero.svg")} alt="landing-image" />
        </div>
      </div>

      <div className="Product--Details">
        <div className="Details-title">
          Business Loans for you
          <div className="divider" />
        </div>
        Whatever be the industry, we have you covered for your working capital needs to achieve
        exponential growth. Get collateral-free loans through our industry leading lending partners.
      </div>

      <div className="loan-application-home">{renderFldgSideModal()}</div>
    </OnBoardingWrapper>
  );
};

const mapStateToProps = (state, ownProps) => {
  return {
    user: state.session.user,
    ...ownProps,
  };
};

export default connect(mapStateToProps)(NonFldgLoans);
