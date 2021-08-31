import { CommonPoints } from './CommonPoints';

export const AtomPoints = ({ gatewayName }) => {
  return (
    <ol>
      <CommonPoints gatewayName={gatewayName} />
    </ol>
  );
};
