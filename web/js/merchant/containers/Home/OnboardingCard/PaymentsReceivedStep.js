import { Link } from 'react-router-dom';

export default ({ user }) => {
  return (
    <Link className="Onboarding__Step" to="/payments">
      <div className="media">
        <div className="media-left">
          <div className="media-object first-pay" />
        </div>
        <div className="media-body">
          <div className="media-heading">You received first payment!</div>
          View all payments in <Link to="/payments">Transactions tab</Link>
        </div>
      </div>
    </Link>
  );
};
