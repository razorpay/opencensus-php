import React from 'react';
import { titleCase, formatDate } from 'util/index';

import Amount from 'ui/Amount';
import EntityRow from 'ui/EntityRow';
import ToggleEntityRow from 'ui/ToggleEntityRow';
import Table from 'ui/Table';
import ShowWhen from 'admin/components/ShowWhen';
import CreditsDetails from './entityDetails/CreditsDetails';
import FeaturesDetails from './entityDetails/FeaturesDetails';

/*---------------------------------------- Functionality ------------------------------------------*/

/* RESOURCE UTILS */
export function openMerchantEntity() {
  window.open(`/admin/merchants/${this.id}`);
}

/*---------------------------------------- Getters ------------------------------------------------*/
export function getRiskRating(value) {
  const riskMap = {
    1: ['Very Low', 'success'],
    2: ['Low', 'success'],
    3: ['Default', 'info'],
    4: ['High', 'danger'],
    5: ['Very High', 'danger'],
  };

  if (!value) {
    return riskMap;
  }

  return riskMap[value];
}

function _getBoolIcon(value) {
  return () => (
    <i class={`i ${value ? 'i-yes text-success' : 'i-no text-danger'}`} />
  );
}

function _getGroupsFields() {
  return [
    ['Group', item => item.name],
    ['Description', item => item.description],
  ];
}

function _getAdminsFields(adminsMap) {
  return [
    ['Role', item => (adminsMap[item.id] ? adminsMap[item.id].role : '')],
    ['Name', item => item.name],
  ];
}

function _getPricingPlansFields() {
  return [
    ['Payment Method', item => item.payment_method],
    ['Payment Method Type', item => item.payment_method_type],
    ['Payment Network', item => item.payment_network],
    ['Payment Issuer', item => item.payment_issuer],
    ['International', item => item.international],
    ['Amount Range Active', item => item.amount_range_active],
    ['Amount Range Min', item => item.amount_range_min / 100],
    ['Amount Range Max', item => item.amount_range_max / 100],
    ['Percent Rate', item => item.percent_rate / 100],
    ['Fixed Rate', item => <Amount value={item.fixed_rate} />],
  ];
}

function _getTerminalFields() {
  return [
    ['Terminal Id', item => item.id],
    ['Mode', item => item.mode],
    ['Gateway', item => item.gateway],
    ['Deleted', item => !!item.deleted_at],
    ['Created At', item => formatDate(item.created_at)],
  ];
}

function _getGatewayFields() {
  return [
    ['Rule ID', item => item.id],
    ['Gateway', item => item.gateway],
    ['Gateway Acquirer', item => item.gateway_acquirer],
    ['Method', item => item.method],
    ['Method Type', item => item.method_type],
    ['Network', item => item.network],
    ['Issuer', item => item.issuer],
    ['International', item => item.international],
    ['Load', item => item.load],
  ];
}

function _getOfferFields() {
  return [
    ['Offer Id', item => item.id],
    ['Name', item => item.name],
    ['Starts At', item => formatDate(item.starts_at)],
    ['Display Text', item => item.display_text],
  ];
}

function _getSettlementScheduleFields() {
  return [
    ['Schedule Name', item => item.schedule_name],
    ['Type', item => item.type],
    ['Method', item => item.method || '-'],
    ['Schedule Id', item => item.schedule_id],
    ['Next Run At', item => formatDate(item.next_run_at)],
  ];
}

function openSettlementSchedule() {
  window.open(`/admin/entity/schedule/live/${this.schedule_id}`);
}

function _getCreditsFields(deleteCreditLogs) {
  // Delete btn is based on mode(state of component where this table is used )
  return mode => {
    return [
      ['Id', item => item.id],
      ['Campaign', item => item.campaign],
      ['Type', item => item.type],
      ['Value', item => item.value],
      ['Created At', item => formatDate(item.created_at)],
      [
        'Delete',
        item => (
          <div
            class="link danger"
            onClick={() => deleteCreditLogs(item.id, mode)}
          >
            Delete
          </div>
        ),
      ],
    ];
  };
}

function _getFeaturesFields(deleteFeature) {
  // Delete btn is based on mode(state of component where this table is used )
  return mode => {
    return [
      [`${titleCase(mode)} Mode Features`, item => item],
      [
        'Action',
        item => (
          <ShowWhen permission="delete_merchant_features">
            <div class="link danger" onClick={() => deleteFeature(item, mode)}>
              Delete
            </div>
          </ShowWhen>
        ),
      ],
    ];
  };
}

// mapping used in multiple files
const utilMapping = {
  network: {
    AMEX: 'American Express',
    DICL: 'Diners Club',
    DISC: 'Discover',
    JCB: 'JCB',
    MAES: 'Maestro',
    MC: 'MasterCard',
    RUPAY: 'RuPay',
    VISA: 'Visa',
    UNP: 'Union Pay',
  },
  method: {
    card: 'Card',
    wallet: 'Wallet',
    netbanking: 'Netbanking',
    upi: 'UPI',
    emi: 'EMI',
  },
  gatewayAcquirer: {
    axis: 'Axis',
    hdfc: 'HDFC',
    amex: 'Amex',
    icic: 'ICICI',
  },
  gatewayCard: {
    first_data: 'First Data',
    hdfc: 'FSS',
    axis_migs: 'Axis Migs',
    cybersource: 'Cybersource',
    amex: 'Amex',
    sharp: 'Sharp',
  },
  gatewayEmi: {
    amex: 'Amex',
    hdfc: 'FSS',
    first_data: 'First Data',
    sharp: 'Sharp',
  },
  gatewayNB: {
    netbanking_hdfc: 'HDFC Netbanking',
    netbanking_corporation: 'Corporation Netbanking',
    netbanking_kotak: 'Kotak Netbanking',
    netbanking_icici: 'ICICI Netbanking',
    netbanking_axis: 'Axis Netbanking',
    netbanking_federal: 'Federal Netbanking',
    netbanking_airtel: 'Airtel Netbanking',
    netbanking_rbl: 'RBL netbanking',
    netbanking_indusind: 'IndusInd netbanking',
    billdesk: 'Billdesk',
    ebs: 'Ebs',
    sharp: 'Sharp',
  },
  gatewayWallet: {
    mobikwik: 'Mobikwik',
    wallet_airtelmoney: 'Airtelmoney',
    wallet_freecharge: 'Freecharge',
    wallet_jiomoney: 'Jiomoney',
    wallet_olamoney: 'Olamoney',
    wallet_payumoney: 'Payumoney',
    wallet_payzapp: 'Payzapp',
    wallet_mpesa: 'Mpesa',
    wallet_sbibuddy: 'SbiBuddy',
    wallet_openwallet: 'Openwallet',
    sharp: 'Sharp',
  },
  gatewayUpi: {
    upi_idfc: 'IDFC UPI',
    upi_icici: 'ICICI UPI',
    upi_mindgate: 'Mindgate/HDFC UPI',
    sharp: 'Sharp',
  },
  wallet: {
    payzapp: 'Payzapp',
    mobikwik: 'Mobikwik',
    payumoney: 'Payumoney',
    olamoney: 'Olamoney',
    airtelmoney: 'Airtelmoney',
    freecharge: 'Freecharge',
    jiomoney: 'Jiomoney',
    openwallet: 'Openwallet',
    mpesa: 'Mpesa',
    paytm: 'Paytm',
  },
};

export function getMappingFor(key) {
  return utilMapping[key];
}

const _getMethods = {
  amex: 'Amex',
  debit_card: 'Debit Card',
  credit_card: 'Credit Card',
  netbanking: 'Netbanking',
  airtelmoney: 'AirtelMoney',
  freecharge: 'Freecharge',
  mobikwik: 'Mobikwik',
  olamoney: 'Olamoney',
  payumoney: 'PayUMoney',
  payzapp: 'Payzapp',
  jiomoney: 'Jiomoney',
  sbibuddy: 'SBI Buddy',
  upi: 'UPI',
  emi: 'EMI',
};

export const beneficiaryStateMap = {
  AN: 'Andaman And Nicobar',
  AP: 'Andhra Pradesh',
  AR: 'Arunachal Pradesh',
  AS: 'Assam',
  BI: 'Bihar',
  CH: 'Chandigarh (UT)',
  CT: 'Chattisgarh',
  DN: 'Dadra And Nagar Haveli',
  DD: 'Daman And Diu (UT)',
  DL: 'Delhi',
  GO: 'Goa',
  GJ: 'Gujarat',
  HA: 'Haryana',
  HP: 'Himachal Pradesh',
  JK: 'Jammu And Kashmir',
  JH: 'Jharkhand',
  KA: 'Karnataka',
  KE: 'Kerala',
  MP: 'Madhya Pradesh',
  MH: 'Maharashtra',
  MA: 'Manipur',
  ME: 'Meghalaya',
  MI: 'Mizoram',
  NA: 'Nagaland',
  OR: 'Orissa',
  PO: 'Pondicherry(UT)',
  PB: 'Punjab',
  RJ: 'Rajasthan',
  SK: 'Sikkim',
  TG: 'Telangana',
  TN: 'Tamilnadu',
  TR: 'Tripura',
  UP: 'Uttar Pradesh',
  UT: 'Uttranchal',
  WB: 'West Bengal',
};

/*---------------------------------------- Render UI resource --------------------------------------------*/
export function getDetailsViewMap(model) {
  const {
    details,
    balanceDetails,
    gatewayRules,
    terminals,
    offers,
    pricingPlans,
    scheduleTasks,
    hasSettlementSchedule,
    features,
    bankDetails,
    creditsLogs,
    adminsMap,
  } = model.merchant;

  return [
    {
      label: 'Group Details',
      children: () => (
        <div>
          <Table items={details.groups} fields={_getGroupsFields()} />
        </div>
      ),
    },
    {
      label: 'Admins',
      class: 'highlight',
      children: () => (
        <div>
          <Table items={details.admins} fields={_getAdminsFields(adminsMap)} />
        </div>
      ),
    },
    {
      label: 'Tags',
      value: details && details.tags ? details.tags.join(', ') : '',
    },
    {
      label: 'Features',
      tag: 'view_merchant_features',
      children: () => (
        <FeaturesDetails
          features={features}
          getFeaturesFields={_getFeaturesFields(model.deleteFeature)}
        />
      ),
    },
    {
      label: 'Marketplace Merchant',
      tag: 'marketplace',
      toHide: !details.parent_id,
      value: details.parent_id
        ? () => (
            <a href={`/admin/merchants/${details.parent_id}`}>
              {details.parent_id}
            </a>
          )
        : null,
      class: 'label-success text-white',
    },
    {
      label: 'Balance',
      permission: 'view_merchant_balance',
      value: Object.keys(balanceDetails).length
        ? () => (
            <div style={{ width: '80%', borderLeft: '1px solid #edf1f2' }}>
              {balanceDetails.test && (
                <EntityRow
                  label="Test:"
                  value={() => <Amount value={balanceDetails.test.balance} />}
                />
              )}
              {balanceDetails.live &&
                details.activated && (
                  <EntityRow
                    className="separate"
                    label="Live:"
                    value={() => <Amount value={balanceDetails.live.balance} />}
                  />
                )}
            </div>
          )
        : null,
    },

    {
      label: 'Amount Credits',
      value: Object.keys(balanceDetails).length
        ? () => (
            <div style={{ width: '80%', borderLeft: '1px solid #edf1f2' }}>
              {balanceDetails.test && (
                <EntityRow
                  label="Test:"
                  value={() => <Amount value={balanceDetails.test.credits} />}
                />
              )}
              {balanceDetails.live &&
                details.activated && (
                  <EntityRow
                    className="separate"
                    label="Live:"
                    value={() => <Amount value={balanceDetails.live.credits} />}
                  />
                )}
            </div>
          )
        : null,
    },

    {
      label: 'Fee Credits',
      value: Object.keys(balanceDetails).length
        ? () => (
            <div style={{ width: '80%', borderLeft: '1px solid #edf1f2' }}>
              {balanceDetails.test && (
                <EntityRow
                  label="Test:"
                  value={() => (
                    <Amount value={balanceDetails.test.fee_credits} />
                  )}
                />
              )}
              {balanceDetails.live &&
                details.activated && (
                  <EntityRow
                    className="separate"
                    label="Live:"
                    value={() => (
                      <Amount value={balanceDetails.live.fee_credits} />
                    )}
                  />
                )}
            </div>
          )
        : null,
    },

    {
      label: 'Max Payment Amount',
      value: details.max_payment_amount
        ? () => (
            <span>
              <Amount value={details.max_payment_amount} />
            </span>
          )
        : null,
    },
    {
      label: 'Name',
      value: details.name,
    },
    {
      label: 'Email',
      value: details.email,
    },
    {
      label: 'Website',
      value: details.website
        ? () => (
            <a href={details.website} target="_blank">
              {details.website}
            </a>
          )
        : null,
    },
    {
      label: 'MCC',
      value: details.category,
    },
    {
      label: 'Category 2',
      value: details.category2,
    },
    {
      label: 'Billing Label',
      value: details.billing_label,
    },
    {
      label: 'Merchant Handle',
      value: details.handle,
    },
    {
      label: 'Transaction Report Email',
      value: details.merchant_details
        ? details.merchant_details.transaction_report_email
        : null,
    },
    {
      label: 'International',
      value: _getBoolIcon(details.international),
    },
    {
      label: 'Registration Date',
      value: formatDate(details.created_at),
    },
    {
      label: 'Submission Date',
      value: details.merchant_details
        ? formatDate(details.merchant_details.submitted_at)
        : null,
    },
    {
      label: 'Activation Date',
      value: formatDate(details.activated_at),
    },
    {
      label: 'Confirmed',
      value: _getBoolIcon(details.confirmed),
    },
    {
      label: 'Activation Form Progress',
      value: details.merchant_details
        ? () => (
            <span
              class={`pills ${
                details.merchant_details.activation_progress < 100
                  ? 'label-danger'
                  : 'label-success'
              }`}
            >
              {details.merchant_details.activation_progress}%
            </span>
          )
        : null,
    },
    {
      label: 'Activation Form Submitted',
      value: details.merchant_details
        ? _getBoolIcon(details.merchant_details.submitted)
        : null,
    },
    {
      label: 'Activation Form Status',
      value: details.merchant_details
        ? () => (
            <i
              class={`i i-${
                details.merchant_details.locked ? 'lock' : 'unlock'
              }`}
            />
          )
        : null,
    },
    {
      label: 'Activated',
      value: _getBoolIcon(details.activated),
    },
    {
      label: 'Live',
      value: _getBoolIcon(details.live),
    },
    {
      label: 'Funds on Hold',
      value: _getBoolIcon(details.hold_funds),
    },
    {
      label: 'Risk Rating',
      value: details.merchant_details
        ? () => {
            const riskRate = getRiskRating(details.risk_rating);

            return (
              <span class={`status-label label-${riskRate[1]}`}>
                {riskRate[0]}
              </span>
            );
          }
        : null,
    },
    {
      label: 'Risk Threshold',
      value: details.risk_threshold,
    },
    {
      label: 'Fee Bearer',
      value: titleCase(details.fee_bearer),
    },
    {
      label: 'Fee Model',
      value: titleCase(details.fee_model),
    },
    {
      label: 'Settlement Schedule',
      children: () => (
        <div>
          {hasSettlementSchedule ? (
            <Table
              onClick={openSettlementSchedule}
              items={scheduleTasks.items}
              fields={_getSettlementScheduleFields()}
            />
          ) : (
            'No Settlement Schedule Assigned'
          )}
        </div>
      ),
    },
    {
      label: 'Methods',
      children: () =>
        Object.keys(_getMethods).map(method => (
          <EntityRow
            key={method}
            label={_getMethods[method]}
            value={
              details.methods ? _getBoolIcon(details.methods[method]) : '-'
            }
          />
        )),
    },
    {
      label: 'Archived',
      value: _getBoolIcon(details.archived_at),
    },
    {
      label: 'Suspended',
      value: _getBoolIcon(details.suspended_at),
    },
    {
      label: 'Customer Receipt Emails',
      value: _getBoolIcon(details.receipt_email_enabled),
    },
    {
      label: 'Print Screenshots',
      value: () => (
        <a target="_blank" href={`/admin/merchant/${details.id}/screenshot`}>
          <i class="i i-cloud-download" />
        </a>
      ),
    },
    {
      label: 'Pricing Plan',
      permission: 'view_merchant_pricing',
      children: () => (
        <div>
          <EntityRow label="Plan Id" value={pricingPlans.id} />
          <EntityRow
            label="Plan Name"
            value={pricingPlans.name}
            className="separate"
          />
          <ToggleEntityRow
            label={'Plan Rules'}
            defaultOpen={true}
            value={() => ''}
            className="separate"
          >
            <Table
              items={pricingPlans.rules}
              fields={_getPricingPlansFields()}
            />
          </ToggleEntityRow>
        </div>
      ),
    },
    {
      label: 'Terminal (Live)',
      children: () => (
        <div>
          <Table items={terminals.items} fields={_getTerminalFields()} />
        </div>
      ),
    },
    {
      label: 'Gateway Rules (Live)',
      children: () => (
        <div>
          <Table items={gatewayRules} fields={_getGatewayFields()} />
        </div>
      ),
    },
    {
      label: 'Offers (Live)',
      children: () => (
        <div>
          <Table items={offers} fields={_getOfferFields()} />
        </div>
      ),
    },
    {
      label: 'Credits',
      permission: 'view_merchant_credits_log',
      children: () => (
        <CreditsDetails
          creditsLogs={creditsLogs}
          fetchCreditsLogs={model.fetchCreditsLogs}
          getCreditsFields={_getCreditsFields(model.deleteCreditLogs)}
        />
      ),
    },
  ];
}
