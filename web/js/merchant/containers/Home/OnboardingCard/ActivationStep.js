import { Link } from 'react-router-dom';
import ProgressBar from 'rzp/ui/ProgressBar';

export default ({ user }) => {
  let image = 'activate';
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
    image = 'activated';
  } else if (user.isSubmitted) {
    header = 'Activation Submitted.';
    headerDesc = <div>Usually takes 1-2 days for activation</div>;
    image = 'submitted';
  }

  return (
    <Link class="Onboarding__Step" to="/activation">
      <div class="media">
        <div class="media-left">
          <div class={`media-object ${image}`} />
        </div>
        <div class="media-body">
          <div class="media-heading">{header}</div>
          {headerDesc}
        </div>
      </div>
    </Link>
  );
};
