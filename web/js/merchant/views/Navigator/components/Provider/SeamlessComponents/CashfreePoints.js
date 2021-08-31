import { CommonPoints } from './CommonPoints';

export const CashfreePoints = ({ gatewayName }) => {
  return (
    <ol>
      <CommonPoints gatewayName={gatewayName} />
    </ol>
  );
};
