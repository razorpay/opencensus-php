import { CommonPoints } from './CommonPoints';
import { PayuPoints } from './PayuPoints';
import { CcavenuePoints } from './CcavenuePoints';
import { CashfreePoints } from './CashfreePoints';
import { PaytmPoints } from './PaytmPoints';
import { AtomPoints } from './AtomPoints';
import { PineLabsPoints } from './PineLabsPoints';
import { IngenicoPoints } from './IngenicoPoints';
import { BillDeskPoints } from './BillDeskPoints';
import { UpiAxisPoints } from './UpiAxisPoints';
import { AxisMigsPoints } from './AxisMigsPoints';
import { CybersourcePoints } from './CybersourcePoints';

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
    case 'ingenico':
      return <IngenicoPoints />;
    case 'billdesk_optimizer':
      return <BillDeskPoints />;
    case 'axis_migs':
      return <AxisMigsPoints />;
    case 'upi_axis':
      return <UpiAxisPoints />;
    case 'cybersource_hdfc':
      return <CybersourcePoints />;
    case 'cybersource_axis':
      return <CybersourcePoints />;
    default:
      return (
        <ol>
          <CommonPoints gatewayName={gatewayName} />
        </ol>
      );
  }
};
