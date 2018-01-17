import { Link } from 'react-router-dom';
import ProgressBar from 'rzp/ui/ProgressBar';
import ActivateAccountSVG from 'styles/assets/activate-account.svg';
import ActivationSubmittedSVG from 'styles/assets/activation-submitted.svg';
import AccountActivatedSVG from 'styles/assets/account-activated.svg';

export default ({ user }) => {
  let svgSrc = ActivateAccountSVG;
  let header = 'Activate account to go live!';
  let headerDesc = (
    <div class="clearfix">
      <span class="pull-left">{user.activation_progress}% complete</span>
      <div class="activation-progress">
        <ProgressBar
          type="success"
          max={100}
          value={user.activation_progress}
        />
      </div>
    </div>
  );

  if (user.isActivated) {
    header = 'Congrats! Account Activated.';
    headerDesc = <div>Now you can accept live payments</div>;
    svgSrc = AccountActivatedSVG;
  } else if (user.isSubmitted) {
    header = 'Activation Submitted.';
    headerDesc = <div>Usually takes 1-2 days for activation</div>;
    svgSrc = ActivationSubmittedSVG;
  }

  return (
    <Link class="Onboarding__Step" to="/activation">
      <div class="media">
        <div class="media-left">
          <img class="media-object" src={svgSrc} />
        </div>
        <div class="media-body">
          <div class="media-heading">{header}</div>
          {headerDesc}
        </div>
      </div>
    </Link>
  );
};
