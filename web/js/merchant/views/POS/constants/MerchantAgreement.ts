export const POS_BENEFITS = [
  'Accept payments of any amount without any limits',
  'Receive customer payments in your bank account as per settlement cycle',
  'Hassle free payment - Uninterrupted connectivity over wifi / sim',
];

export const MDR_VAS_RATES_COMPONENT = 'mdr_vas_rates_component';
export const VAS_RATES_COMPONENT = 'vas_rates_component';
export const DEVICE_CATALOG_COMPONENT = 'device_catalogue_component';
export const PAYMENT_OPTIONS_COMPONENT = 'payment_options_component';
export const PAYMENT_OPTIONS_FIELD = 'payment_options_field';
export const QR_CODE = 'qr_code';
export const PAYMENT_LINK = 'payment_link';
export const QR_CODE_COMPONENT_V2 = 'qr_code_component_v2';
export const SALES_ASSISTED_PAYMENT_LINK_COMPONENT = 'sales_assisted_payment_link_component';
export const PRICING_STEP = 'pricing_step';
export const DEVICE_SELECTION_STEP = 'device_selection_step';
export const CONSENT_STEP = 'consent_step';
export const AGREEMENT_STEP = 'agreement_step';
export const CONSENT_COMPONENT = 'consent_component';
export const AGREEMENT_COMPONENT = 'agreement_component';
export const IN_PROGRESS = 'in_progress';
export const COMPLETED = 'completed';
export const CUSTOM_RATES_ENABLED_FIELD = 'custom_rates_enabled_field';
export const AGREEMENT_STATUS_FIELD = 'agreement_status_field';
export const PRICING_CONSENT_FIELD = 'pricing_consent_field';
export const PRICING_AGREEMENT_FAILED_TO_LOAD =
  'Unable to load Pricing Agreement Details. Please try again!';
export const POS_AGREEMENT_FAILED_TO_LOAD =
  'Unable to load POS Agreement Details. Please try again!';
export const POS_AGREEMENT_SIGN_FAILED = 'Failed to agree. Please try again!';
export const AGREEMENT_CONSENTED_AT_FIELD = 'agreement_consented_at_field';

//TODO: Move this template to templating service
export const getPosPricingTemplate = (org, pricingTableV2) => {
  return `
<style>
table {
    width: 50%;
    border-collapse: collapse;
    margin: 20px 0;
}
th, td {
    border: 1px solid #000;
    padding: 8px;
    text-align: left;
}
th {
    background-color: #d9d9d9;
}
</style>
    <h2 class='center-heading mb-24'><u>COMMERCIAL TERMS</u></h2>
       <div>
       <ul class='noListStyle' style="list-style-type: none;">
            <li class='scroll'>
                ${pricingTableV2}
            </li>
            <li>
                 <ul class='mb-24'>
            <li>Applicable taxes wherever not mentioned shall be charged separately.</li>
            <li>3 (three) free of charge paper rolls will be provided (one time) at the time of Device deployment.</li>
            <li>In cases where the Merchant requires additional paper rolls, then the same shall be provided @ INR 15 (plus GST) per paper roll, subject to the minimum order quantity of 5 paper rolls per order. </li>
       </ul>
       </div>
            </li>
       </ul>
       <h3 class='center-heading mb-24'><u>Additional Device Commercial Terms for Android POS Devices</u></h3>
       <ol type="1">
            <li>Following Charges / fee have been waived off by ${org?.business_name}:</li>
             <table>
        <thead>
            <tr>
                <th>Particular</th>
                <th>Fee</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>De-installation Charges</td>
                <td>Waived Off</td>
            </tr>
            <tr>
                <td>Refundable Usage Deposit</td>
                <td>Waived Off</td>
            </tr>
            <tr>
                <td>Auto-Debit Processing Fee</td>
                <td>Waived Off</td>
            </tr>
            <tr>
                <td>Communication Charges</td>
                <td>Waived Off</td>
            </tr>
            <tr>
                <td>Auto Batch Settlement Feature</td>
                <td>Waived Off</td>
            </tr>
            <tr>
                <td>One-Time EMI Setup Charges</td>
                <td>Waived Off</td>
            </tr>
            <tr>
                <td>Monthly EMI Service Fee</td>
                <td>Waived Off</td>
            </tr>
            <tr>
                <td>One-Time International Set-up Charges</td>
                <td>Waived Off</td>
            </tr>
        </tbody>
    </table>
    <li>${org?.business_name} will charge low usage fee, if in any month, the merchant fails to accomplish the minimum transaction threshold of INR 1,00,000/- (One Lakh Rupees). Low usage charges will be as per below criteria:</li>
      <table>
        <thead>
            <tr>
                <th>Transaction Volume Threshold</th>
                <th>Low usage charges (per Device)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Overall Monthly Transaction Volume is less than <span class="textBold">INR 1,000</span> (one Thousand Rupees)</td>
                <td>INR 299 (plus GST)</td>
            </tr>
            <tr>
                <td>Overall Monthly Transaction Volume is more than <span class="textBold">INR 1,000</span> (One Thousand Rupees) but upto <span class="textBold">INR 25,000</span> (Twenty-Five Thousand Rupees)</td>
                <td>INR 249 (plus GST)</td>
            </tr>
            <tr>
                <td>Overall Monthly Transaction Volume is more than <span class="textBold">INR 25,000</span> (Twenty-Five Thousand Rupees) but upto <span class="textBold">INR 1,00,000</span> (One Lakh Rupees)</td>
                <td>INR 199 (plus GST)</td>
            </tr>
            
        </tbody>
    </table>
    <li>Notwithstanding anything contained herein, the aforementioned terms shall be subject to the below conditions:
        <br/>
        <br/>

        <ol type="a">
            <li>If the Merchant terminates the Master Agreement and / or returns the Devices within 12 months from the date of installation, then the Merchant shall be liable to pay one-time early cancellation fee of INR 399 (plus GST) to ${org?.business_name}.</li>
            <br/>
            <li>The Merchant hereby understands that if the Device terminals deployed at Merchant location remains inactive for a continuous period of 45 days, then the Merchant shall be liable to pay terminal recovery charges of INR 5500 (for A50/A99) and / or INR 7500 (for A910) per Device terminal. The same may be recovered in any manner including charging the Merchant account via Nach / E-Nach. <br/><br/>The aforementioned terminal recovery charges will be refunded / reversed to the Merchant only in cases where the Merchant initiates a suo moto de-installation request to ${org?.business_name} and subsequently ${org?.business_name} is successfully able to recover the Device in the original condition, subject to normal wear & tear resulting from daily usage. It is to be noted that if the event mentioned under this sub-clause (b) is triggered within a period of 12 months from the date of installation of Device terminals then ${org?.business_name} will also recover early cancellation fee, as enumerated under sub-clause (a) above. </li>
             <br/>
            <li>In cases where ${org?.business_name} is not able to collect terminal recovery charges as mentioned in sub-clause (b) above, then ${org?.business_name} may at its discretion initiate the deinstallation of Devices from Merchant’s location.</li>
             <br/>
            <li>Annual Terminal Maintenace Charges (AMC) of INR 399 (plus GST) shall be charged from the Merchant on an annual basis. However, for the first year, AMC will be charged after 90 days from the date of installation of the Devices.</li>
             <br/>
            <li>The Merchant understands that this “Commercial / Fee” document shall be a part of the Master Agreement (Merchant Acquiring Form) and / or T&Cs signed-up by the Merchant. The Merchant further understands that except for the terms mentioned herein, all the remaining terms & conditions of the Master Agreement / T&Cs shall be applicable mutatis-mutandis. In relation to the terms wherein there is a direct conflict between this “Commercial / Fee” document and the Master Agreement / T&Cs, the provision of this “Commercial / Fee”  document shall prevail.</li>
        </ol>
    </li>
    <br/>
    <li><b><u>Commercials / Fee / MDR for Payment Aggregation Services:</u></b></li>
    <table>
        <thead>
            <tr>
                <th>Particulars</th>
                <th>Standard MDR (in %)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><b>Credit Card (Visa/Master/Rupay)</b></td>
                <td>{{credit_card_mdr_rate_field}}</td>
            </tr>
            <tr>
                <td>Grocery Stores & Supermarkets</td>
                <td>{{stdMDRGrocery}}</td>
            </tr>
            <tr>
                <td>(Utility, Govt, Education, Fuel, Insurance, Transport)</td>
                <td>{{stdMDRUtility}}</td>
            </tr>
            <tr>
                <td>Other Segments (On US/Off US)</td>
                <td>{{stdMDROthers}}</td>
            </tr>
            <tr>
                <td><b>International Card/Corp Cards/Amex/Diners</b></td>
                <td>{{prepaid_b2b_corporate_channel_international_card_mdr_rate_field}}</td>
            </tr>
            <tr>
                <td><b>Debit Card (Excl. Rupay)</b></td>
                <td>{{debit_card_rupay_mdr_rate_field}}</td>
            </tr>
            <tr>
                <td>&lt; 2000*</td>
                <td>{{debit_card_visa_mastercard_maestro_less_than_2k_mdr_rate_field}}</td>
            </tr>
            <tr>
                <td>&gt; 2000*</td>
                <td>{{debit_card_visa_mastercard_maestro_greater_than_2k_mdr_rate_field}}</td>
            </tr>
            <tr>
                <td><b>BQR through Debit Card</b></td>
                <td>{{stdMDRBqr}}</td>
            </tr>
            <tr>
                <td>&lt; 2000*</td>
                <td>{{stdMDRBqrlt2000}}</td>
            </tr>
            <tr>
                <td>&gt; 2000*</td>
                <td>{{stdMDRBqrgt2000}}</td>
            </tr>
            <tr>
                <td><b>UPI/Rupay Debit Card</b></td>
                <td>{{upi_mdr_rate_field}}</td>
            </tr>
        </tbody>
    </table>
    <p>(a) The aforementioned MDR shall be deducted upfront from the transaction settlement amount.</p>
    <p>(b) The MDR shall be applied on the gross transaction value.</p>
    <p>(c) Applicable taxes shall be charged separately.</p>
    <br/>
    <li><b><u>Commercials / Fee for Affordability Services:</u></b></li>
    <table>
        <thead>
            <tr>
                <th>Particulars</th>
                <th>Transaction Fee (to be calculated on gross transaction value)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>CC EMI</td>
                <td>{{vas_cc_emi_rate_field}}</td>
            </tr>
            <tr>
                <td>DC EMI</td>
                <td>{{vas_dc_emi_rate_field}}</td>
            </tr>
            <tr>
                <td>BNPL</td>
                <td>{{feeBnpl}}</td>
            </tr>
            <tr>
                <td>NBFC EMI</td>
                <td>{{feeNbfc}}</td>
            </tr>
            <tr>
                <td>Brand EMI Credit Card
</td>
                <td>{{brand_emi_cc_rate_field}}</td>
            </tr>
            <tr>
                <td>Brand EMI Debit Card
</td>
                <td>{{brand_emi_dc_rate_field}}</td>
            </tr>
            <tr>
            <td>EMI Plus Credit Card</td>
            <td>{{emi_plus_cc_rate_field}}</td>
        </tr>
            <tr>
            <td>EMI Plus Debit Card</td>
            <td>{{emi_plus_dc_rate_field}}</td>
        </tr>
        </tbody>
    </table>
    <p> *Applicable taxes shall be charged separately</p>
    <br/>
    <li><b><u>Other Additional Services</u></b></li>
    <table>
        <thead>
            <tr>
                <th>Particulars</th>
                <th>Commercials/Fee</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Same Day Settlement</td>
                <td>{{sameDay}}</td>
            </tr>
            <tr>
                <td>Reconciliation Services</td>
                <td>{{reconciliation}}</td>
            </tr>
            <tr>
                <td>SMS Pay - CNP</td>
                <td>{{smsPayMinusCnp}}</td>
            </tr>
            <tr>
                <td>Digital Invoicing (BillMe) and SMS charges</td>
                <td>{{billMeSms}}</td>
            </tr>
            <tr>
                <td>One time Tech Integration Fee</td>
                <td>{{techFee}}</td>
            </tr>
        </tbody>
    </table>
    <p> *Applicable taxes shall be charged separately</p>
       </ol>
`;
};
