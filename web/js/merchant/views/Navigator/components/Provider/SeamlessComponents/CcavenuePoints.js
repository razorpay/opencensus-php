import { CommonPoints } from './CommonPoints';

export const CcavenuePoints = ({ selectedProvider, gatewayName }) => {
  return (
    <ol>
      <CommonPoints gatewayName={gatewayName} />
      <li>
        Write to your relationship manager to whitelist the URLs https://api.razorpay.com for your
        PG account or the below IP address:
        <ol type="a">
          <li>52.66.75.174</li>
          <li>52.66.76.63</li>
          <li>52.66.151.218</li>
        </ol>
      </li>
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
        </ol>
      </li>
    </ol>
  );
};
