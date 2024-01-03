import BarcodeIcon from 'assets/pos/icons/barCode.svg';
import BatteryIcon from 'assets/pos/icons/battery.svg';
import IntegrationIcon from 'assets/pos/icons/integration.svg';
import NFCIcon from 'assets/pos/icons/nfc.svg';
import PricingPlanIcon from 'assets/pos/icons/pricing-plan.svg';
import PrinterIcon from 'assets/pos/icons/printer.svg';
import ScreenSizeIcon from 'assets/pos/icons/screenSize.svg';
import StandAloneIcon from 'assets/pos/icons/standalone.svg';
import TapAndPayIcon from 'assets/pos/icons/tapAndPay.svg';
import UPI from 'assets/pos/icons/upi.svg';
import AndroidMini from 'assets/pos/productTable/androidMini.webp';
import AndroidPos from 'assets/pos/productTable/androidPos.webp';
import MobilePos from 'assets/pos/productTable/mobilePos.webp';
import { ProductTableList } from 'merchant/views/POS/types';

const PRODUCTS_TABLE: ProductTableList = {
  features: [
    [
      { key: 'pricing_plan', name: 'Pricing Plan', icon: PricingPlanIcon, boxSize: 'xlarge' },
      { key: 'upi', name: 'UPI', icon: UPI, boxSize: 'small' },
      { key: 'tapAndPay', name: 'Tap & Pay', icon: TapAndPayIcon, boxSize: 'small' },
      { key: 'printer', name: 'Printer', icon: PrinterIcon, boxSize: 'small' },
    ],
    [
      { key: 'barcodeScanner', name: 'Barcode Scanner', icon: BarcodeIcon, boxSize: 'small' },
      { key: 'screenSize', name: 'Screen Size', icon: ScreenSizeIcon, boxSize: 'xlarge' },
      { key: 'standalone', name: 'Standalone', icon: StandAloneIcon, boxSize: 'small' },
      {
        key: 'intergrationCapabilities',
        name: 'Integration Capabilities',
        icon: IntegrationIcon,
        boxSize: 'small',
      },
      { key: 'nfc', name: 'Comms Configuration', icon: NFCIcon, boxSize: 'medium' },
      { key: 'battery', name: 'Battery', icon: BatteryIcon, boxSize: 'medium' },
    ],
  ],
  products: [
    {
      image: AndroidPos,
      name: 'android-smart-pos',
      code: 'a50',
      productTitle: 'Android Smart POS',
      features: {
        pricing_plan: {
          isAvailable: true,
          name: 'Pricing Plan',
          isCustomComponent: true,
        },
        upi: {
          isAvailable: true,
          name: 'UPI',
        },
        tapAndPay: {
          isAvailable: true,
          name: 'Card Tap & Pay',
        },
        barcodeScanner: {
          isAvailable: true,
          name: 'Barcode Scanner',
        },
        printer: {
          isAvailable: true,
          name: 'Paper Billing Available',
        },
        screenSize: {
          isAvailable: true,
          name: '5" IPS WXGA 720 x 1280 Pixels Multi-Point Capacitive HD Touch Screen',
        },
        standalone: {
          isAvailable: true,
          name: 'Standalone',
        },
        intergrationCapabilities: {
          isAvailable: true,
          name: 'Integration to apps and devices.',
        },
        nfc: {
          isAvailable: true,
          name: '4G + WiFi® (2.4GHz, optional 5GHz) + Bluetooth® 4.0',
        },
        battery: {
          isAvailable: true,
          name: '2600mAh / 7.2V | Optional 3350mAh / 7.2V',
        },
      },
    },
    {
      image: AndroidMini,
      name: 'android-mini-pos',
      code: 'a910',
      productTitle: 'Android Mini POS',
      features: {
        pricing_plan: {
          isAvailable: true,
          name: 'Pricing Plan',
          isCustomComponent: true,
        },
        upi: {
          isAvailable: true,
          name: 'UPI',
        },
        tapAndPay: {
          isAvailable: true,
          name: 'Card Tap & Pay',
        },
        barcodeScanner: {
          isAvailable: false,
          name: 'Barcode Scanner',
        },
        printer: {
          isAvailable: false,
          name: 'Paper Billing Available',
        },
        screenSize: {
          isAvailable: true,
          name: '4.5" FW 480 x 854 Pixels Multi-Point Capacitive Touch Screen',
        },
        standalone: {
          isAvailable: true,
          name: 'Standalone',
        },
        intergrationCapabilities: {
          isAvailable: true,
          name: 'Integration to apps and devices.',
        },
        nfc: {
          isAvailable: true,
          name: '4G + Wi-Fi® 2.4GHz + Bluetooth®',
        },
        battery: {
          isAvailable: true,
          name: '5600 mAh | 3.8V',
        },
      },
    },
    {
      image: MobilePos,
      name: 'mobile-pos',
      code: 'd180',
      productTitle: 'Mobile POS (mPOS)',
      features: {
        pricing_plan: {
          isAvailable: true,
          name: 'Pricing Plan',
          isCustomComponent: true,
        },
        upi: {
          isAvailable: true,
          name: 'UPI',
        },
        tapAndPay: {
          isAvailable: true,
          name: 'Card Tap & Pay',
        },
        barcodeScanner: {
          isAvailable: false,
          name: 'Barcode Scanner',
        },
        printer: {
          isAvailable: false,
          name: 'Paper Billing Available',
        },
        screenSize: {
          isAvailable: true,
          name: '128 x 64 pixels LCD Display',
        },
        standalone: {
          isAvailable: false,
          name: 'Works with phone',
        },
        intergrationCapabilities: {
          isAvailable: true,
          name: 'Integration to apps and devices.',
        },
        nfc: {
          isAvailable: true,
          name: 'Bluetooth® wireless technology',
        },
        battery: {
          isAvailable: true,
          name: '3.7V / 250mAh Rechargeable Li-ion Battery',
        },
      },
    },
  ],
};

export default PRODUCTS_TABLE;
