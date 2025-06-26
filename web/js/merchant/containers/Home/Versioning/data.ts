import { BentoCardData } from './types';
import { HeadingSegment, VersioningConfig } from './types';
import EnhancedStabilityLarge from 'assets/versioning/enhanced-stability-large.png';
import EnhancedStabilitySmall from 'assets/versioning/enhanced-stability-small.png';
import PaymentDowntimeLarge from 'assets/versioning/payment-downtime-recovery-large.png';
import PaymentDowntimeSmall from 'assets/versioning/payment-downtime-recovery-small.png';
import UpiMandateLarge from 'assets/versioning/upi-mandate-api-large.png';
import UpiMandateSmall from 'assets/versioning/upi-mandate-api-small.png';
import UpiBackupLarge from 'assets/versioning/upi-backup-terminals-large.png';
import UpiBackupSmall from 'assets/versioning/upi-backup-terminals-small.png';
import DigitBinsLarge from 'assets/versioning/8-9digit-bins-large.png';
import DigitBinsSmall from 'assets/versioning/8-9digit-bins-small.png';
import AutoDebitLarge from 'assets/versioning/auto-debit-limit-large.png';
import AutoDebitSmall from 'assets/versioning/auto-debit-limit-small.png';
import RecurringPaymentLarge from 'assets/versioning/recurring-payment-success-large.png';
import RecurringPaymentSmall from 'assets/versioning/recurring-payment-success-small.png';
import McpServerLarge from 'assets/versioning/razorpay-mcp-server-large.png';
import McpServerSmall from 'assets/versioning/razorpay-mcp-server-small.png';
import MigrateSavedCardsLarge from 'assets/versioning/migrate-saved-cards-large.png';
import MigrateSavedCardsSmall from 'assets/versioning/migrate-saved-cards-small.png';
import RetentionProgramLarge from 'assets/versioning/automate-retention-program-large.png';
import RetentionProgramSmall from 'assets/versioning/automate-retention-program-small.png';
import GiftCardPlatformLarge from 'assets/versioning/one-gift-card-platform-large.png';
import GiftCardPlatformSmall from 'assets/versioning/one-gift-card-platform-small.png';
import D2CBuyerProtectionLarge from 'assets/versioning/d2c-buyer-protection-large.png';
import D2CBuyerProtectionSmall from 'assets/versioning/d2c-buyer-protection-small.png';
import MoneysaverAccountLarge from 'assets/versioning/moneysaver-account-large.png';
import MoneysaverAccountSmall from 'assets/versioning/moneysaver-account-small.png';
import PosFixedItselfLarge from 'assets/versioning/pos-fixed-itself-large.png';
import PosFixedItselfSmall from 'assets/versioning/pos-fixed-itself-small.png';
import VersioningLogo from 'assets/versioning/logo.svg';

// SVG Icons for versioning section cards
import ArrowRightIcon from 'assets/versioning/arrow-right.svg';
import StarIcon from 'assets/versioning/star.svg';
import CalendarIcon from 'assets/versioning/calendar.svg';
import BarsIcon from 'assets/versioning/bars.svg';
import PlusIcon from 'assets/versioning/plus.svg';

// Color constants for image backgrounds
const BLUE_BG = '#F5F8FF';
const GREEN_BG = '#EBFAF3';

// SVG icon mapping for dynamic loading
export const versioningSVGIcons = {
  'arrow-right.svg': ArrowRightIcon,
  'star.svg': StarIcon,
  'calendar.svg': CalendarIcon,
  'bars.svg': BarsIcon,
  'plus.svg': PlusIcon,
};

export const versionReleases: BentoCardData[] = [
  {
    id: 'enhanced-stability',
    title: 'Enhanced Stability with 200 Bug Fixes',
    productName: 'PG',
    tags: ['Quality Upgraded'],
    imgLarge: EnhancedStabilityLarge,
    imgSmall: EnhancedStabilitySmall,
    backText:
      "206 bugs squashed! From Jan to May '25, we clocked in 3,000+ engineering hours to fix what matters the most-  fewer issues, better uptime, and a checkout flow you can finally trust.",
    imageBgColor: BLUE_BG,
  },
  {
    id: 'payment-downtime-recovery',
    title: 'Intelligent Payment Downtimes Tracking & Recovery Module',
    productName: 'PG',
    tags: ['Security Upgraded'],
    imgLarge: PaymentDowntimeLarge,
    imgSmall: PaymentDowntimeSmall,
    backText:
      'Now, we can auto-detect outages in near real-time, thanks to our upgraded uptime maintenance system, enabling faster recovery.',
    imageBgColor: GREEN_BG,
  },
  {
    id: 'upi-mandate-api',
    title: 'Launched APIs to Enable UPI Mandates Cancellations',
    productName: 'UPI',
    tags: ['Control Upgraded'],
    imgLarge: UpiMandateLarge,
    imgSmall: UpiMandateSmall,
    backText:
      "You can now facilitate mandate cancellations in real-time using our APIs, so that your customers get their money back faster, and you don't face operational delays.",
    imageBgColor: BLUE_BG,
  },
  {
    id: 'upi-backup-terminals',
    title: 'Razorpay UPI: Now with Back-Up Terminals from Axis & ICICI Banks',
    productName: 'UPI',
    tags: ['Uptime Upgraded'],
    imgLarge: UpiBackupLarge,
    imgSmall: UpiBackupSmall,
    backText:
      'With back-up Axis and ICICI bank terminals now live on Razorpay UPI, you get 99.99% uptime and uninterrupted transactions, even during peak load. Enjoy reliable, round-the-clock performance.',
    imageBgColor: BLUE_BG,
  },
  {
    id: '8-9digit-bins',
    title: 'Industry-First Card Support for 8 & 9-Digit BINs',
    productName: 'Cards',
    tags: ['Convenience Upgraded'],
    imgLarge: DigitBinsLarge,
    imgSmall: DigitBinsSmall,
    backText:
      'To support innovations from ISO and card networks like Visa & Mastercard, we added support for 8 and 9-digit BINs - beyond the 6-digit format. Enjoy fewer declines.',
    ctaText: 'Explore',
    ctaLink: 'https://razorpay.com/docs/api/payments/cards/iin-api/#6-digit-iins',
    imageBgColor: BLUE_BG,
  },
  {
    id: 'auto-debit-limit',
    title: 'Auto-Debit Limit For Card Mandates Now Up to ₹1 Lakh',
    productName: 'Cards',
    tags: ['Convenience Upgraded'],
    imgLarge: AutoDebitLarge,
    imgSmall: AutoDebitSmall,
    backText:
      'Now your biggest payments do not need re-approvals. Auto-debit limits for registered card mandates have been raised from ₹15,000 to ₹1 lakh for select categories like subscriptions, credit card bills.',
    imageBgColor: BLUE_BG,
  },
  {
    id: 'recurring-payment-success',
    title: 'Recurring Card Payments Now 13% More Successful',
    productName: 'Cards',
    tags: ['Success Rate Boost'],
    imgLarge: RecurringPaymentLarge,
    imgSmall: RecurringPaymentSmall,
    backText:
      'With intelligent routing, and retry logic, recurring card transactions just got a 13% boost in success rates, ensuring smoother auto-debits, and more predictable revenue for your business.',
    ctaText: 'Explore',
    ctaLink: 'https://razorpay.com/docs/payments/recurring-payments/cards/integrate/',
    imageBgColor: BLUE_BG,
  },
  {
    id: 'mcp-server',
    title: "Power AI-Payments with Razorpay's Official MCP Server",
    productName: 'PG',
    tags: ['Convenience Upgraded'],
    imgLarge: McpServerLarge,
    imgSmall: McpServerSmall,
    backText:
      'Let your AI say: "Pay now." Connect your AI systems directly to Razorpay\'s MCP Server and trigger payments, share links, and complete transactions - seamlessly within any AI-native flow.',
    ctaText: 'Explore',
    ctaLink:
      'https://razorpay.com/blog/razorpay-remote-mcp-2-0-the-next-leap-in-ai-powered-payments/',
    imageBgColor: GREEN_BG,
  },
  {
    id: 'migrate-saved-cards',
    title: 'Migrate Saved Cards to Razorpay Seamlessly with Copilot',
    productName: 'Copilot Migration',
    tags: ['Experience Upgraded'],
    imgLarge: MigrateSavedCardsLarge,
    imgSmall: MigrateSavedCardsSmall,
    backText:
      'Moving from your current router to Razorpay? With Copilot, your customers can continue using their saved cards, even after migration. No data loss, and no friction in card payments.',
    imageBgColor: BLUE_BG,
  },
  {
    id: 'retention-programs',
    title: 'Automate Retention Programs with Wallets',
    productName: 'Engage',
    tags: ['Experience Upgraded'],
    imgLarge: RetentionProgramLarge,
    imgSmall: RetentionProgramSmall,
    backText:
      'Create, and launch your brand wallet as a payment method for your customers, and automate cashback on orders, refunds & milestones with customized redemption & expiry rules - without developer effort. Delight meets repeat.',
    imageBgColor: GREEN_BG,
  },
  {
    id: 'gift-card-platform',
    title: 'Power Retention, Reach & Revenue with One Gift Card Platform',
    productName: 'Engage',
    tags: ['Engagement Upgraded'],
    imgLarge: GiftCardPlatformLarge,
    imgSmall: GiftCardPlatformSmall,
    backText:
      "Create and distribute customised brand gift cards on a single platform. Expand your reach via Razorpay's 5M+ merchant network and boost customer engagement, lower acquisition costs and drive repeat purchases!",
    imageBgColor: BLUE_BG,
  },
];

export const productUpdates: BentoCardData[] = [
  {
    id: 'moneysaver-amazon-sellers',
    title: 'MoneySaver Account for Amazon Global Sellers',
    productName: 'International Payments',
    tags: ['Top-Pick'],
    imgLarge: MoneysaverAccountLarge,
    imgSmall: MoneysaverAccountSmall,
    backText:
      'Indian exporters selling on Amazon can now get paid through Razorpay with zero FX markup, instant compliance, and India-based support- all in one export account.',
    ctaText: 'Explore',
    ctaLink:
      'https://razorpay.com/blog/razorpay-is-now-amazon-approved-take-your-e-commerce-business-to-the-world-with-moneysaver-export-account/',
    imageBgColor: BLUE_BG,
  },
  {
    id: 'd2c-buyer-protection',
    title: "India's First D2C Buyer Protection Program",
    productName: 'Magic Checkout',
    tags: ['Must-Try'],
    imgLarge: D2CBuyerProtectionLarge,
    imgSmall: D2CBuyerProtectionSmall,
    backText:
      'Fraud fears are killing your conversions. But no longer. Give customers peace of mind with a Money Back Guarantee on lost or damaged items. Less cart anxiety = Up to 30% higher checkout.',
    ctaText: 'Explore',
    ctaLink: 'https://razorpay.com/blog/razorpay-buyer-protection/',
    imageBgColor: BLUE_BG,
  },
  {
    id: 'pos-self-fixing',
    title: 'Razorpay POS Now Fixes Itself',
    productName: 'POS',
    tags: ['Power-Tool'],
    imgLarge: PosFixedItselfLarge,
    imgSmall: PosFixedItselfSmall,
    backText:
      'From network drops to app crashes, up to 40% of common issues on Razorpay POS now resolve automatically. No support calls, no downtime- just uninterrupted sales.',
    ctaText: 'Explore',
    ctaLink: 'https://razorpay.com/pos/selfhealing/',
    imageBgColor: GREEN_BG,
  },
];

// Configurable versioning section configs
export const versioningUpgradeConfig: VersioningConfig = {
  mobile: {
    logo: {
      src: VersioningLogo,
      size: {
        width: 250,
        height: 42,
      },
    },
    heading: [
      { type: 'text', content: 'You have been ', textSize: 'large' },
      { type: 'svg', content: 'arrow-right.svg', imgSize: { width: 46, height: 29 } },
      { type: 'break', marginLeft: 'auto', marginRight: 'auto' },
      { type: 'text', content: 'upgraded', textSize: 'large' },
      { type: 'svg', content: 'bars.svg', imgSize: { width: 28, height: 27 } },
      { type: 'text', content: 'to the latest', textSize: 'large' },
      { type: 'break', paddingRight: '15px' },
      { type: 'svg', content: 'plus.svg', imgSize: { width: 27, height: 26 } },
      { type: 'text', content: 'version of Razorpay', textSize: 'large' },
      { type: 'break', marginLeft: 'auto' },
    ],
    body: {
      text: 'Enjoy faster workflows, stronger security, and higher success rates- all in a superior Razorpay experience',
      size: 'small',
    },
  },
  desktop: {
    logo: {
      src: VersioningLogo,
      size: {
        width: 392,
        height: 67,
      },
    },
    heading: [
      { type: 'svg', content: 'arrow-right.svg', imgSize: { width: 77, height: 45 } },
      { type: 'text', content: 'You have been ', textSize: '2xlarge' },
      { type: 'svg', content: 'bars.svg', imgSize: { width: 48, height: 46 } },
      { type: 'text', content: 'upgraded', textSize: '2xlarge' },
      { type: 'break' },
      { type: 'text', content: 'to the latest ', textSize: '2xlarge' },
      { type: 'svg', content: 'plus.svg', imgSize: { width: 46, height: 45 } },
      { type: 'text', content: 'version of Razorpay', textSize: '2xlarge' },
    ] as HeadingSegment[],
    body: {
      text: 'Enjoy faster workflows, stronger security, and higher success rates- all in a superior Razorpay experience',
      size: 'medium',
    },
  },
};

export const versioningCatchUpConfig: VersioningConfig = {
  mobile: {
    heading: [
      { type: 'svg', content: 'arrow-right.svg', imgSize: { width: 46, height: 29 } },
      { type: 'text', content: 'Catch Up on Key ' },
      { type: 'svg', content: 'star.svg', imgSize: { width: 28, height: 27 } },
      { type: 'break', paddingRight: '15px' },
      { type: 'svg', content: 'calendar.svg', imgSize: { width: 26, height: 26 } },
      { type: 'text', content: 'Product Updates' },
      { type: 'break', marginLeft: 'auto' },
    ] as HeadingSegment[],
    body: {
      text: 'Choose from over 40+ product upgrades to outmaneuver competition and unlock exponential growth',
      size: 'small',
    },
  },
  desktop: {
    heading: [
      { type: 'svg', content: 'arrow-right.svg', imgSize: { width: 77, height: 45 } },
      { type: 'text', content: 'Catch Up on Key ' },
      { type: 'svg', content: 'star.svg', imgSize: { width: 47, height: 46 } },
      { type: 'break', paddingRight: '25px' },
      { type: 'svg', content: 'calendar.svg', imgSize: { width: 45, height: 45 } },
      { type: 'text', content: 'Product Updates' },
      { type: 'break', marginLeft: 'auto' },
    ] as HeadingSegment[],
    body: {
      text: 'Choose from over 40+ product upgrades to outmaneuver competition and unlock exponential growth',
      size: 'medium',
    },
  },
};
