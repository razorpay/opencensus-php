import React from 'react';
import { Link } from 'react-router-dom';
import Amount from 'ui/Amount';

/* RESOURCE UTILS */
export function openMerchantEntity() {
  const url = window.location.href + '/' + this.id;
  window.open(url);
}

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

export function getDetailsViewMap(merchant) {
  const { details, terminals, pricingPlans, bankDetails } = merchant;
  console.log('DETAILS....', details);

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
      value: '',
    },
    {
      label: 'Features',
      value: () => <button class="btn-default">Show/Hide</button>,
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
    {
      label: 'Pricing Plan',
      value: () => <button class="btn-default">Show/Hide</button>,
    },
    {
      label: 'Terminal',
      value: () => <button class="btn-default">Show/Hide</button>,
    },
    {
      label: 'Gateway Rules',
      value: () => <button class="btn-default">Show/Hide</button>,
    },
    {
      label: 'Offers',
      value: () => <button class="btn-default">Show/Hide</button>,
    },
    {
      label: 'Credits',
      value: () => <button class="btn-default">Show/Hide</button>,
    },
  ];
}
