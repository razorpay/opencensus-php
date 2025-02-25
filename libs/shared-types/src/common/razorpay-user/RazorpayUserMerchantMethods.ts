import { RazorpayUserMerchantApps } from "./RazorpayUserMerchantApps";
import { RazorpayUserMerchantCardNetworks } from "./RazorpayUserMerchantCardNetworks";
import { RazorpayUserMerchantCardlessEmiProviders } from "./RazorpayUserMerchantCardlessEmiProviders";
import { RazorpayUserMerchantCreditEmiProviders } from "./RazorpayUserMerchantCreditEmiProviders";
import { RazorpayUserMerchantDebitEmiProviders } from "./RazorpayUserMerchantDebitEmiProviders";
import { RazorpayUserMerchantPaylaterProviders } from "./RazorpayUserMerchantPaylaterProviders";
import { RazorpayUserMerchantUPIType } from "./RazorpayUserMerchantUPIType";

/**
 * Represents the payment methods available for a merchant.
 */
export type RazorpayUserMerchantMethods = {
    /** Merchant ID */
    merchant_id: string;
  
    /** Boolean flag indicating card payments (1 means enabled, 0 means disabled) */
    card: number;
  
    /** Available card networks and their enabled/disabled status */
    card_networks: RazorpayUserMerchantCardNetworks;
  
    /** Subtype of the card */
    card_subtype: number;
  
    /** Boolean flag indicating availability of net banking */
    netbanking: boolean;
  
    /** Boolean flag indicating availability of UPI */
    upi: boolean;
  
    /** Boolean flag for emandate availability */
    emandate: boolean;
  
    /** Boolean flag for NACH availability */
    nach: boolean;
  
    /** Boolean flag indicating bank transfer availability */
    bank_transfer: boolean;
  
    /** Boolean flag indicating AEPS (Aadhaar Enabled Payment System) availability */
    aeps: boolean;
  
    /** Boolean flag for AMEX availability */
    amex: boolean;
  
    /** List of disabled banks by bank codes */
    disabled_banks: string[];
  
    /** Array of available EMI providers (if any) */
    emi: any[]; // Could be expanded with details on each EMI provider
  
    /** Boolean flag for cardless EMI availability */
    cardless_emi: boolean;
  
    /** Boolean flag for Pay Later options */
    paylater: boolean;
  
    /** Boolean flag indicating debit card acceptance */
    debit_card: boolean;
  
    /** Boolean flag indicating prepaid card acceptance */
    prepaid_card: boolean;
  
    /** Boolean flag indicating credit card acceptance */
    credit_card: boolean;
  
    /** Boolean flag for Paytm wallet support */
    paytm: boolean;
  
    /** Boolean flag for Mobikwik wallet support */
    mobikwik: boolean;
  
    /** Boolean flag for PayZapp wallet support */
    payzapp: boolean;
  
    /** Boolean flag for PayUMoney wallet support */
    payumoney: boolean;
  
    /** Boolean flag for open wallet support */
    openwallet: boolean;
  
    /** Boolean flag for OlaMoney wallet support */
    olamoney: boolean;
  
    /** Boolean flag for PhonePe wallet support */
    phonepe: boolean;
  
    /** Boolean flag for PayPal support */
    paypal: boolean;
  
    /** Boolean flag for FreeCharge wallet support */
    freecharge: boolean;
  
    /** Boolean flag for JioMoney wallet support */
    jiomoney: boolean;
  
    /** Boolean flag for SBI Buddy wallet support */
    sbibuddy: boolean;
  
    /** Boolean flag for M-Pesa wallet support */
    mpesa: boolean;
  
    /** Boolean flag for Airtel Money wallet support */
    airtelmoney: boolean;
  
    /** Boolean flag for Amazon Pay support */
    amazonpay: boolean;
  
    /** Boolean flag for PhonePe Switch support */
    phonepeswitch: boolean;
  
    /** Third-party apps and their enabled/disabled status */
    apps: RazorpayUserMerchantApps;
  
    /** UPI transaction types (Collect and Intent) */
    upi_type: RazorpayUserMerchantUPIType;
  
    /** Boolean flag for Razorpay wallet support */
    razorpaywallet: boolean;
  
    /** Debit EMI providers and their enabled/disabled status */
    debit_emi_providers: RazorpayUserMerchantDebitEmiProviders;
  
    /** Boolean flag for cash-on-delivery (COD) availability */
    cod: boolean;
  
    /** Boolean flag for offline payment options */
    offline: boolean;
  
    /** Boolean flag for FPX (Financial Process Exchange) availability */
    fpx: boolean;
  
    /** Boolean flag for ITZ Cash support */
    itzcash: boolean;
  
    /** Boolean flag for Oxigen wallet support */
    oxigen: boolean;
  
    /** Boolean flag for AMEX EasyClick support */
    amexeasyclick: boolean;
  
    /** Boolean flag for PayCash support */
    paycash: boolean;
  
    /** Boolean flag for Citibank Rewards support */
    citibankrewards: boolean;
  
    /** Boolean flag for in-app purchases */
    in_app: number;
  
    /** Boolean flag for in-app credit card payments */
    in_app_credit_card: number;
  
    /** Boolean flag for credit cards over UPI */
    cc_on_upi: number;
  
    /** Boolean flag for wallets over UPI */
    wallet_on_upi: number;
  
    /** Boolean flag for credit line over UPI */
    creditline_on_upi: number;
  
    /** Credit EMI providers and their enabled/disabled status */
    credit_emi_providers: RazorpayUserMerchantCreditEmiProviders;
  
    /** Cardless EMI providers and their enabled/disabled status */
    cardless_emi_providers: RazorpayUserMerchantCardlessEmiProviders;
  
    /** Pay Later providers and their enabled/disabled status */
    paylater_providers: RazorpayUserMerchantPaylaterProviders;
  
    /** Boolean flag for Bajaj Pay support */
    bajajpay: boolean;
  
    /** Array of available international bank transfers (if any) */
    intl_bank_transfer: any[]; // Could be expanded with details on each transfer option
  
    /** Boolean flag for Boost payment option */
    boost: boolean;
  
    /** Boolean flag for MCash payment option */
    mcash: boolean;
  
    /** Boolean flag for GrabPay payment option */
    grabpay: boolean;
  
    /** Boolean flag for Touch 'n Go payment option */
    touchngo: boolean;
  
    /** Boolean flag for Sodexo payment option */
    sodexo: boolean;
  
    /** Other options, if available */
    [key: string]: any;
  };
  