import React from 'react';
import { Link } from 'react-router-dom';
import { titleCase } from 'util/index';

import Amount from 'ui/Amount';
import EntityRow from 'ui/EntityRow';
import Table from 'ui/Table';

import CreditsDetails from './CreditsDetails';
import FeaturesDetails from './FeaturesDetails';

/*---------------------------------------- Functionality ------------------------------------------*/

/* RESOURCE UTILS */
export function openMerchantEntity() {
  window.open(`/admin/_#/app/merchants/${this.id}/detail`);
}

/*---------------------------------------- Getters ------------------------------------------------*/
function _getRiskRating(value) {
  const riskMap = {
    1: ['Very Low', 'success'],
    2: ['Low', 'success'],
    3: ['Default', 'info'],
    4: ['High', 'danger'],
    5: ['Very High', 'danger'],
  };

  return riskMap[value];
}

function _getBoolIcon(value) {
  return () => <span>{value ? '✓' : 'x'}</span>;
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
    ['Enabled', item => item.enabled],
    ['Category', item => item.category],
    ['Network Category', item => item.network_category],
    ['Merchant Id', item => item.gateway_merchant_id],
    ['Merchant Id 2', item => item.gateway_merchant_id2],
    ['Acquirer', item => item.acquirer],
    ['Terminal Id', item => item.terminal_id],
    ['Card Allowed', item => item.card_allowed],
    ['Netbanking', item => item.netbanking],
    ['Emi', item => item.emi],
    ['Emi Duration (Months)', item => item.emi_duration],
    ['Shared', item => item.shared],
    ['Tpv', item => item.tpv],
    ['Currency', item => item.currency],
    ['Created At', item => item.created_at],
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
    ['Starts At', item => item.starts_at],
    ['Display Text', item => item.display_text],
  ];
}

function _getCreditsFields(deleteCreditLogs) {
  // Delete btn is based on mode(state of component where this table is used )
  return mode => {
    return [
      ['Id', item => item.id],
      ['Campaign', item => item.campaign],
      ['Type', item => item.type],
      ['Value', item => item.value],
      ['Created At', item => item.created_at],
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
          <div class="link danger" onClick={() => deleteFeature(item, mode)}>
            Delete
          </div>
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

/*---------------------------------------- UI resource --------------------------------------------*/
export function getDetailsViewMap(model) {
  const {
    details,
    terminals,
    pricingPlans,
    bankDetails,
    features,
    gatewayRules,
    offers,
    creditsLogs,
  } = model.merchant;

  // console.log('FEATURES...', features);

  return [
    {
      label: 'Group Details',
      value: () => <button class="btn-default">Show/Hide</button>,
    },
    {
      label: 'Admins',
      value: () => <button class="btn-default">Show/Hide</button>,
    },
    {
      label: 'Tags',
      value: details && details.tags ? details.tags.join(', ') : '',
    },
    {
      label: 'Features',
      value: () => <button class="btn-default">Show/Hide</button>,
      toggleChildren: () => (
        <FeaturesDetails
          features={features}
          getFeaturesFields={_getFeaturesFields(model.deleteFeature)}
        />
      ),
    },
    {
      label: 'Balance(Test)',
      value: details.amount,
    },
    {
      label: 'Balance(Live)',
      value: details.amount,
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
            <Link to={details.website} target="_blank">
              {details.website}
            </Link>
          )
        : null,
    },
    {
      label: 'MCC',
      value: details.amount,
    },
    {
      label: 'Category 2',
      value: details.amount,
    },
    {
      label: 'Billing Label',
      value: details.amount,
    },
    {
      label: 'Merchant Handle',
      value: details.amount,
    },
    {
      label: 'Transaction Report Email',
      value: details.amount,
    },
    {
      label: 'International',
      value: _getBoolIcon(details.international),
    },
    {
      label: 'Registration Date',
      value: details.created_at,
    },
    {
      label: 'Submission Date',
      value: details.merchant_details
        ? details.merchant_details.submitted_at
        : null,
    },
    {
      label: 'Activation Date',
      value: details.activated_at,
    },
    {
      label: 'Confirmed',
      value: _getBoolIcon(details.confirmed),
    },
    {
      label: 'Activation Form Progress',
      value: details.merchant_details
        ? `${details.merchant_details.activation_progress}%`
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
      value: details.merchant_details ? details.merchant_details.locked : null,
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
            const riskRate = _getRiskRating(details.risk_rating);

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
      value: details.fee_bearer,
    },
    {
      label: 'Fee Model',
      value: details.fee_model,
    },
    {
      label: 'Settlement Schedule',
      value: () => <button class="btn-default">Show/Hide</button>,
    },
    {
      label: 'Methods',
      value: () => <button class="btn-default">Show/Hide</button>,
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
      value: details.receipt_email_enabled,
    },
    {
      label: 'Print Screenshots',
      value: details.amount,
    },
    // TODO: Convert this in ToggleEntityRow container. Check EntityRow.js TODO
    {
      label: 'Pricing Plan',
      value: () => <button class="btn-default">Show/Hide</button>,
      toggleChildren: function() {
        return (
          <div>
            <EntityRow label="Plan Id" value={pricingPlans.id} />
            <EntityRow label="Plan Name" value={pricingPlans.name} />
            <Table
              items={pricingPlans.rules}
              fields={_getPricingPlansFields()}
            />
          </div>
        );
      },
    },
    {
      label: 'Terminal',
      value: () => <button class="btn-default">Show/Hide</button>,
      toggleChildren: function() {
        return (
          <div>
            <Table items={terminals.items} fields={_getTerminalFields()} />
          </div>
        );
      },
    },
    {
      label: 'Gateway Rules (Live)',
      value: () => <button class="btn-default">Show/Hide</button>,
      toggleChildren: function() {
        return (
          <div>
            <Table items={gatewayRules} fields={_getGatewayFields()} />
          </div>
        );
      },
    },
    {
      label: 'Offers (Live)',
      value: () => <button class="btn-default">Show/Hide</button>,
      toggleChildren: function() {
        return (
          <div>
            <Table items={offers} fields={_getOfferFields()} />
          </div>
        );
      },
    },
    {
      label: 'Credits',
      value: () => <button class="btn-default">Show/Hide</button>,
      toggleChildren: () => (
        <CreditsDetails
          creditsLogs={creditsLogs}
          fetchCreditsLogs={model.fetchCreditsLogs}
          getCreditsFields={_getCreditsFields(model.deleteCreditLogs)}
        />
      ),
    },
  ];
}
