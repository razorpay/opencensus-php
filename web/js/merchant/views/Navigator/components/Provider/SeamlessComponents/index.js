import { CommonPoints } from './CommonPoints';
import { PayuPoints } from './PayuPoints';
import { CcavenuePoints } from './CcavenuePoints';
import { CashfreePoints } from './CashfreePoints';
import { PaytmPoints } from './PaytmPoints';
import { AtomPoints } from './AtomPoints';
import { PineLabsPoints } from './PineLabsPoints';

export const SeamlessHowto = ({ gatewayName, selectedProvider }) => {
  switch (selectedProvider) {
    case 'payu':
      return <PayuPoints gatewayName={gatewayName} selectedProvider={selectedProvider} />;
    case 'ccavenue':
      return <CcavenuePoints gatewayName={gatewayName} selectedProvider={selectedProvider} />;
    case 'cashfree':
      return <CashfreePoints gatewayName={gatewayName} />;
    case 'paytm':
      return <PaytmPoints gatewayName={gatewayName} />;
    case 'atom':
      return <AtomPoints gatewayName={gatewayName} />;
    case 'pinelabs':
      return <PineLabsPoints gatewayName={gatewayName} />;
    default:
      return (
        <ol>
          <CommonPoints gatewayName={gatewayName} />
        </ol>
      );
  }
};
