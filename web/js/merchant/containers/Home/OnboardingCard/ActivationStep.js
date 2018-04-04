import { Link } from 'react-router-dom';
import ProgressBar from 'rzp/ui/ProgressBar';

const Progress = ({ progress }) => {
  return (
    <div className="activation-progress">
      <ProgressBar type="success" max={100} value={progress} />
    </div>
  );
};

export default ({ user }) => {
  const progress = user.activation_progress;

  return (
    <Link class="Onboarding__Step" to="/activation">
      <div class="media">
        <div class="media-body">
          <div className="activation-progress-cont">
            <div>
              <b>Activate Your Account</b>
              <span className="activation-progress-num">{progress}%</span>
            </div>
            <div>
              <Progress progress={progress} />
            </div>
          </div>
          <div className="step-desc">Complete form to accept live payments</div>
        </div>
        <div className="media-arrow">
          <i className="i i-chevron-right" />
        </div>
      </div>
    </Link>
  );
};
