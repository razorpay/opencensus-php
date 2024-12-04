import { AtomPoints } from './AtomPoints';
import { AxisMigsPoints } from './AxisMigsPoints';
import { BillDeskPoints } from './BillDeskPoints';
import { CashfreePoints } from './CashfreePoints';
import { CcavenuePoints } from './CcavenuePoints';
import { CommonPoints } from './CommonPoints';
import { CybersourcePoints } from './CybersourcePoints';
import { HdfcPoints } from './HdfcPoints';
import { IngenicoPoints } from './IngenicoPoints';
import { Pay10Points } from './Pay10Points';
import { PayZappPoints } from './PayZappPoints';
import { PaytmPoints } from './PaytmPoints';
import { PayuPoints } from './PayuPoints';
import { PineLabsPoints } from './PineLabsPoints';
import { UpiAxisPoints } from './UpiAxisPoints';
import { UpiIciciPoints } from './UpiIciciPoints';
import { GetSimplPoints } from './GetSimplPoints';
import { IciciNetbankingPoints } from './IciciNetbankingPoints';
import { HdfcNetbankingPoints } from './HdfcNetbankingPoints';
import { ZaakpayPoints } from './ZaakpayPoints';

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
    case 'hdfc':
      return <HdfcPoints />;
    case 'cybersource_hdfc':
      return <CybersourcePoints />;
    case 'cybersource_axis':
      return <CybersourcePoints />;
    case 'upi_icici':
      return <UpiIciciPoints />;
    case 'wallet_payzapp':
      return <PayZappPoints />;
    case 'pay10':
      return <Pay10Points />;
    case 'getsimpl_optimizer':
      return <GetSimplPoints />;
    case 'netbanking_icici':
      return <IciciNetbankingPoints />;
    case 'netbanking_hdfc':
      return <HdfcNetbankingPoints />;
    case 'zaakpay':
      return <ZaakpayPoints />;
    default:
      return (
        <ol>
          <CommonPoints gatewayName={gatewayName} />
        </ol>
      );
  }
};
