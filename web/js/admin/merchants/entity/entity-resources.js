import React from 'react';

import { titleCase, snakeToTitleCase, formatDate } from 'common/util';
import { statusPill } from 'common/data';
import user from 'admin/user';

import Amount from 'ui/Amount';
import EntityRow from 'ui/EntityRow';
import ToggleEntityRow from 'ui/ToggleEntityRow';
import Table from 'ui/Table';
import ShowWhen from 'admin/components/ShowWhen';
import CreditsDetails from './entityDetails/CreditsDetails';
import FeaturesDetails from './entityDetails/FeaturesDetails';
import ActivationStatusLogsDetails from './entityDetails/ActivationStatusLogsDetails';

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
    ['Feature', item => item.feature || 'payment'],
    ['Payment Method', item => item.payment_method || 'Any'],
    ['Payment Method Type', item => item.payment_method_type || 'Any'],
    ['Payment Network', item => item.payment_network || 'Any'],
    ['Payment Issuer', item => item.payment_issuer || 'Any'],
    ['International', item => item.international || 'false'],
    ['Amount Range Active', item => item.amount_range_active || 'false'],
    ['Amount Range Min', item => <Amount value={item.amount_range_min} />],
    ['Amount Range Max', item => <Amount value={item.amount_range_max} />],
    ['Percent Rate', item => <span>{item.percent_rate / 100}%</span>],
    ['Fixed Rate', item => <Amount value={item.fixed_rate} />],
  ];
}

function _getTerminalFields() {
  return [
    [
      'Terminal Id',
      item => (
        <a
          class="link"
          href={`/admin/entity/terminal/${item.id}`}
          target="_blank"
        >
          {item.id}
        </a>
      ),
    ],
    ['Mode', item => item.mode],
    ['Gateway', item => item.gateway],
    [
      'Deleted',
      item =>
        item.deleted_at ? (
          <i class="i i-yes text-danger" />
        ) : (
          <div style={{ textAlign: 'center' }}>--</div>
        ),
    ],
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
    [
      'Offer Id',
      item => (
        <a class="link" href={`/admin/entity/offer/${item.id}`} target="_blank">
          {item.id}
        </a>
      ),
    ],
    ['Name', item => item.name],
    ['Percentage', item => item.percent_rate],
    ['Flat Cashback', item => item.flat_cashback],
    ['Starts At', item => formatDate(item.starts_at)],
    ['Ends At', item => formatDate(item.ends_at)],
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

function _getCreditsFields() {
  // Delete btn is based on mode(state of component where this table is used )
  return mode => {
    return [
      ['Id', item => item.id],
      ['Campaign', item => item.campaign],
      ['Type', item => statusPill(item.type)],
      ['Value', item => item.value],
      ['Created At', item => formatDate(item.created_at)],
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

function _getRejectedReasonsFields() {
  return [
    ['Category', item => snakeToTitleCase(item.reason_category)],
    [
      'Reason',
      item =>
        item.reason_description ? item.reason_description : item.reason_code,
    ],
  ];
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
    upi_hulk: 'UPI/HULK',
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
    amazonpay: 'Amazon Pay',
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
  emandate: 'e-Mandate',
  mpesa: 'Mpesa',
  amazonpay: 'Amazon Pay',
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
      permission: 'view_all_admin',
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
      children: () => (
        <FeaturesDetails
          features={features}
          getFeaturesFields={_getFeaturesFields(model.deleteFeature)}
        />
      ),
    },
    {
      label: 'Partner',
      value: details.partner_type
        ? snakeToTitleCase(details.partner_type)
        : _getBoolIcon(false),
    },
    {
      label: 'Marketplace Merchant',
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
      label: 'GST Number',
      value: details.merchant_details ? details.merchant_details.gstin : null,
    },
    {
      label: 'Website',
      value: details.merchant_details
        ? () => (
            <a href={details.merchant_details.business_website} target="_blank">
              {details.merchant_details.business_website}
            </a>
          )
        : null,
    },
    {
      label: 'Keyless Auth',
      value: _getBoolIcon(details.activated && !details.has_key_access),
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
              class={`pill ${
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
      label: 'Activation Form Status',
      value: details.merchant_details
        ? () => statusPill(details.merchant_details.activation_status)
        : '--',
    },
    {
      label: 'Rejection Reason',
      children:
        details.merchant_details && details.merchant_details.rejection_reasons
          ? () => (
              <Table
                items={details.merchant_details.rejection_reasons.items}
                fields={_getRejectedReasonsFields()}
              />
            )
          : null,
    },
    /*- conditionaly add this field based on permission */
    ...(user.permissions.indexOf('view_activation_form') !== -1 && [
      {
        label: 'Activation Status Change Logs',
        children: () => <ActivationStatusLogsDetails merchantId={details.id} />,
      },
    ]),
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
      label: 'Auto Refund Delay',
      value: details.auto_refund_delay_val
        ? details.auto_refund_delay_val + ' ' + details.auto_refund_delay_type
        : '5 Days',
    },
    {
      label: 'Auto Capture Late Auth',
      value: _getBoolIcon(details.auto_capture_late_auth),
    },
    {
      label: 'Settlement Schedule',
      children: () => (
        <div>
          {hasSettlementSchedule ? (
            <Table
              onClick={openSettlementSchedule}
              items={scheduleTasks}
              fields={_getSettlementScheduleFields()}
            />
          ) : hasSettlementSchedule === false ? (
            'No Settlement Schedule Assigned'
          ) : (
            <div class="small spinner" />
          )}
        </div>
      ),
    },
    {
      label: 'Methods',
      children: () => {
        let methodRows = Object.keys(_getMethods).map(method => (
          <EntityRow
            key={method}
            label={_getMethods[method]}
            value={
              details.methods ? _getBoolIcon(details.methods[method]) : '-'
            }
          />
        ));

        if (details.methods) {
          let disabledBanks = details.methods.disabled_banks;
          methodRows.push(
            <EntityRow
              key="disabled_banks"
              label="Disabled Banks"
              value={disabledBanks.length ? disabledBanks : '--'}
            />
          );
        }

        return methodRows;
      },
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
          getCreditsFields={_getCreditsFields()}
          merchantId={details.id}
        />
      ),
    },
  ];
}
