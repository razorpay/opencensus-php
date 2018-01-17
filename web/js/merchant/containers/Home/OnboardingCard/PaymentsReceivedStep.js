import { Link } from 'react-router-dom';
import FirstPaymentSVG from 'styles/assets/first-payment.svg';

export default ({ user }) => {
  return (
    <Link class="Onboarding__Step" to="/payments">
      <div class="media">
        <div class="media-left">
          <img class="media-object" src={FirstPaymentSVG} />
        </div>
        <div class="media-body">
          <div class="media-heading">You received first payment!</div>
          View all payments in <Link to="/payments">Transactions tab</Link>
        </div>
      </div>
    </Link>
  );
};
