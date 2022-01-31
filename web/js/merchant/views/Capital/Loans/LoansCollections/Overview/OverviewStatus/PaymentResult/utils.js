import React from 'react';

import Amount from 'common/ui/Amount';
import { COLLECTIONS_PAYMENT_TYPE } from '../../../constants';

import { Seperator, HeaderIcon, ICONS } from './styles';

function RenderMessage({ config }) {
  const { title, subtitle, button, headers, data, icon, sibling, className = '' } = config;

  return (
    <div className={`payment_suc_fail__msg ${className}`}>
      <div className="payment_suc_fail__header flex items-center">
        <div>
          <div className="flex items-center">
            <HeaderIcon name={icon} />
            <span className="title ml-8">{title}</span>
          </div>
          <div className="subtitle mt-12">{subtitle}</div>
        </div>
        <div className="flex items-center">{button}</div>
      </div>
      <Seperator />
      <div>
        <table>
          <thead>
            <tr>
              {headers.map((head) => (
                <th key={head.key}>{head.header}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {data.map((repayment, i) => (
              <tr key={i}>
                {headers.map((head) => (
                  <td key={head.key}>
                    {head.key === 'amount' ? (
                      <Amount
                        value={repayment[head.key]}
                        className="text-navy-blue text-sm font-bold"
                      />
                    ) : (
                      COLLECTIONS_PAYMENT_TYPE[repayment[head.key]] || repayment[head.key] || '-'
                    )}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {sibling}
    </div>
  );
}
const isPaymentBeingProcessing = (repayment) =>
  !!repayment.error?.errors?.some?.((err) => err.includes('resource already acquired'));

// config used by RenderMessage
const getBaseMessageConfig = () => ({
  // NOTE: Apart from this, each object will have data, className(optional) and button(optional) key is added by component
  success: {
    title: 'Repayment Successful!',
    subtitle: 'Woohoo! We’ve recieved your payment 🎉',
    icon: ICONS.success,
    headers: [
      { header: 'Repaid Amount', key: 'amount' },
      { header: 'Repayment Via', key: 'type' },
      { header: 'Reference Id', key: 'id' },
    ],
    containerClass: 'cont--success',
  },
  failure: {
    title: 'Repayment Failure!',
    subtitle:
      'Your repayment has been failed. Incase if any money has been debited , it will be added back to your account within 5-7 working day.',
    icon: ICONS.failed,
    headers: [
      { header: 'Repayment Amount', key: 'amount' },
      { header: 'Repayment Via', key: 'type' },
    ],
    containerClass: 'cont--error',
  },
  processing: {
    title: 'Your repayment is being processed',
    subtitle:
      'We are processing your repayment and status will be updated in the next few seconds.',
    icon: ICONS.processing,
    headers: [
      { header: 'Repayment Amount', key: 'amount' },
      { header: 'Repayment Via', key: 'type' },
    ],
    containerClass: 'cont--processing',
  },
});

export { RenderMessage, isPaymentBeingProcessing, getBaseMessageConfig };
