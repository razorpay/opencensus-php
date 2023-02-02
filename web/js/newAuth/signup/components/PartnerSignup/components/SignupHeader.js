import { STEPS } from 'newAuth/signup/Constants';
import { redirectToLogIn } from 'newAuth/utils';
import { CustomSecondaryButton } from 'newAuth/commonStyles';
import { StyledSignupHeader, StyledHeaderCTA, StyledInfoIcon } from './styled';
import imageBackIcon from 'assets/partner-dashboard/back-icon-white.svg';
export default ({ step, setStep }) => {
  const infoIconVisibility = [STEPS.MOBILE_NUMBER, STEPS.CONGRATS].includes(step)
    ? 'hidden'
    : 'inherit';
  const onBackClick = () => {
    setStep((step) => step - 1);
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
