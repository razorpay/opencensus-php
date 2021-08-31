import { CommonPoints } from './CommonPoints';

export const PaytmPoints = ({ gatewayName }) => {
  return (
    <ol>
      <CommonPoints gatewayName={gatewayName} />
      <li>
        Your PG account must be configured to receive callback_url as part of the payment URL
        parameters. This can be achieved by emailing your {gatewayName} relationship manager.
      </li>
    </ol>
  );
};
