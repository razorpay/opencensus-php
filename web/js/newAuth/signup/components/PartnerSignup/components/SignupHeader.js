import imageBackIcon from 'assets/partner-dashboard/back-icon-white.svg';
import { CustomSecondaryButton } from 'newAuth/commonStyles';
import { STEPS } from 'newAuth/signup/Constants';
import { redirectToLogIn } from 'newAuth/utils';

import { StyledSignupHeader, StyledHeaderCTA, StyledInfoIcon } from './styled';
export default ({ step, setStep }) => {
  const infoIconVisibility = [STEPS.MOBILE_NUMBER, STEPS.CONGRATS].includes(step)
    ? 'hidden'
    : 'inherit';
  const onBackClick = () => {
    setStep((step) => {
      if (step === STEPS.MOBILE_VERIFICATION) {
        // doing this because second step is welcome back screen
        // and we don't want to show that when clicking on back
        return STEPS.MOBILE_NUMBER;
      } else {
        return step - 1;
      }
    });
  };
  return (
    <StyledSignupHeader>
      <StyledInfoIcon visibility={infoIconVisibility} onClick={onBackClick}>
        <img src={imageBackIcon} alt="go back" />
      </StyledInfoIcon>
      <StyledHeaderCTA>
        <span className="already-user-text">Already a User?</span>
        <span>
          <CustomSecondaryButton size="small" onClick={redirectToLogIn}>
            Log In
          </CustomSecondaryButton>
        </span>
      </StyledHeaderCTA>
    </StyledSignupHeader>
  );
};
