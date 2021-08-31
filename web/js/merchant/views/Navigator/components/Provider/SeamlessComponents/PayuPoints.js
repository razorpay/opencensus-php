import { CommonPoints } from './CommonPoints';

export const PayuPoints = ({ selectedProvider, gatewayName }) => {
  return (
    <ol>
      <CommonPoints gatewayName={gatewayName} />
      <li>
        If you are going to use UPI as a payment method following steps will have to configured:
        <ol type="a">
          <li>
            configure webhook URL as{' '}
            <a
              href={`https://api.razorpay.com/v1/callback/${selectedProvider}`}
              target="_blank"
              rel="noopener noreferrer"
            >
              {`https://api.razorpay.com/v1/callback/${selectedProvider}`}
            </a>{' '}
            to receive UPI response
          </li>
          <li>enable UPI on seamless with the flag “txn_s2s_flow=4”</li>
        </ol>
      </li>
    </ol>
  );
};
