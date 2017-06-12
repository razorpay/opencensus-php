import { Link } from 'react-router-dom';

export default ({ user }) => {
  return (
    <div class="Onboarding__Step">
      <div class="media">
        <div class="media-left">
          <a href="#">
            <img class="media-object" />
          </a>
        </div>
        <div class="media-body">
          <div class="media-heading">
            You received first payment!
          </div>
          View all payments in <Link to="/payments">Transactions tab</Link>
        </div>
      </div>
    </div>
  );
};
