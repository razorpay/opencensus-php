import { Link } from 'react-router-dom';

export default ({ user }) => {
  return (
    <Link class="Onboarding__Step" to="/payments">
      <div class="media">
        <div class="media-left">
          <div class="media-object first-pay" />
        </div>
        <div class="media-body">
          <div class="media-heading">You received first payment!</div>
          View all payments in <Link to="/payments">Transactions tab</Link>
        </div>
      </div>
    </Link>
  );
};
