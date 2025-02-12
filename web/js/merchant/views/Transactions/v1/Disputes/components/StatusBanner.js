import Amount from 'common/ui/Amount';
import { Link } from 'react-router-dom';

const StatusBanner = (props) => {
  const {
    dispute: { status, amount_deducted, currency, evidence },
  } = props;
  let message;
  switch (status) {
    case 'under_review':
      message =
        'We have received the evidences for the dispute from you and the dispute is under review.';
      if (amount_deducted > 0) {
        message =
          'We have received the evidences for the dispute from you and the dispute is under review. As per banking guidelines, we have debited the dispute amount from your Razorpay balance. Upon winning the dispute, the dispute amount will be added back to your Razorpay balance. This may take upto 45 days from the date of evidence submission';
      }
      break;
    case 'won':
      message =
        'Post evaluation of the documents submitted, the dispute has been marked as won in your favour by our banking partners';
      if (amount_deducted > 0) {
        message =
          'Post evaluation of the documents submitted, the dispute has been marked as won in your favour by our banking partners. Any amount deducted earlier as a part of this dispute has been credited back to your Razorpay balance';
      }
      break;
    case 'lost':
      if (evidence?.amount === 0) {
        // Dispute accepted
        message = (
          <span>
            We have received your acceptance for the dispute and the dispute has been marked as
            lost. <Amount value={amount_deducted} currency={currency} /> has been debited from your
            Razorpay account balance and will be credited to the customer
          </span>
        );
        if (amount_deducted === 0) {
          message =
            'We have received your acceptance for the dispute and the dispute has been marked lost. The corresponding amount has been debited from your Razorpay balance and will be credited to the customer';
        }
      } else {
        message = (
          <span>
            Post evaluation of the documents submitted, the dispute has been marked as lost by our
            banking partners. <Amount value={amount_deducted} currency={currency} /> has been
            debited from your Razorpay account balance and will be credited to the customer
          </span>
        );
        if (amount_deducted === 0) {
          message =
            'Post evaluation of the documents submitted, the dispute has been marked as lost by our banking partners. The corresponding amount is debited from your Razorpay balance and will be credited to the customer';
        }
      }

      break;
    case 'closed':
      message = (
        <span>
          We have received the evidences for the dispute and the dispute has been marked closed.
          Please check the combined report for any refund/ debit from your Razorpay account balance{' '}
          <Link to="/reports">here</Link>
        </span>
      );
      break;
    default:
      message = '';
  }
  return (
    <div className="alert alert-info">
      <div className="rzp-banner-text">
        <p>{message}</p>
      </div>
    </div>
  );
};

export default StatusBanner;
