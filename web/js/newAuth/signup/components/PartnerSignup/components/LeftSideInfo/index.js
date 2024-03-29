import { RupeeIcon } from '@razorpay/blade/components';
import { STEPS } from 'newAuth/signup/Constants';
import Testimonials from './Testimonials';
import imageCongrats from 'assets/partner-dashboard/congrats-img.png';
import imageStarStroke from 'assets/partner-dashboard/star-stroke.png';
import { StyledHeading, StyledInfoWrapper, StyledCongratsContent } from './styled';

export default ({ step }) => {
  return step !== STEPS.CONGRATS ? (
    <StyledInfoWrapper>
      <StyledHeading>
        Become a <span className="highlight">Partner</span>
      </StyledHeading>
      <hr className="yellow-seperator" />
      <div className="sub-heading">
        Most of our partners earn more than
        <span className="rupee-icon">
          <RupeeIcon color="surface.icon.staticWhite.normal" size="xlarge" />
        </span>
        7500 in commissions every month!
      </div>
      <Testimonials />
    </StyledInfoWrapper>
  ) : (
    <StyledInfoWrapper>
      <StyledCongratsContent>
        <img src={imageCongrats} className="congrats-img" alt="congrats-img" />

        <div className="congrats-heading">Congratulations!</div>
        <div className="congrats-sub-heading">
          You are now a &nbsp;
          <span>Razorpay Partner.</span>
        </div>
        <hr className="yellow-seperator" />
        <div className="congrats-sub-heading description">Let's get you started.</div>
      </StyledCongratsContent>

      <div className="congrats-content-mweb">
        <img src={imageStarStroke} alt="success" className="star-logo" />
        <label>Your account is created! 🙌</label>
      </div>
    </StyledInfoWrapper>
  );
};
