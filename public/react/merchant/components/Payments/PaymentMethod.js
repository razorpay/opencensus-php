import { Link } from 'react-router-dom';
import React from 'react';

import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import Definition from 'rzp/ui/Definition';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
import { titleCase } from 'rzp/utils/rzp-utils';

/*
 * Design:
 * https://projects.invisionapp.com/d/main#/console/11691503/247373211/preview
 * Inputs:
 * @param {Object} payment
 * @param {Object} card
 *
 * Description:
 * Given the `payment` parameter exactly the same as
 * fetch payments api and `card` parameter as fetch card details api,
 * `bankTransfer` as fetch bank transfer api,
 * the content will be shown according to the Design^
 */
export default ({ payment, card = {}, bankTransfer = {} }) => {
  const paymentMethod = payment.method,
    methodKeyMap = {
      netbanking: 'bank',
      wallet: 'wallet',
      upi: 'vpa',
    },
    cardDetails = card.details || {};

  let el = null;

  if (methodKeyMap[paymentMethod]) {
    let paymentMethodText = payment[methodKeyMap[paymentMethod]];

    paymentMethodText =
      paymentMethod !== 'netbanking'
        ? titleCase(paymentMethodText)
        : paymentMethodText;
    el = (
      <Definition>
        <span>
          {paymentMethod !== 'upi'
            ? paymentMethodText + ' ' + titleCase(paymentMethod)
            : 'UPI'}
        </span>
        {paymentMethod === 'upi' && <span>{payment.vpa}</span>}
      </Definition>
    );
  } else if (paymentMethod === 'card' || paymentMethod === 'emi') {
    if (Object.keys(card) === 0 || card.loading) {
      return <PlaceholderLoader />;
    }

    const cardTitle = (
        <span>
          {paymentMethod === 'emi' ? 'EMI on ' : ''}
          {cardDetails.international ? 'International ' : 'Domestic '}
          {cardDetails.type !== 'unknown' && titleCase(cardDetails.type + ' ')}
          Card
        </span>
      ),
      cardInfo = (
        <span>
          <span>
            {cardDetails.issuer ? cardDetails.issuer + ', ' : ''}
            {cardDetails.network + ' ending '}
          </span>
          <b>{cardDetails.last4}</b>
        </span>
      ),
      customer = <span>Name on card - {cardDetails.name}</span>,
      cardId = <code>{cardDetails.id}</code>;

    el = (
      <ContentToggler>
        {cardTitle}
        <Definition allowEmptyTitle={true}>
          {null}
          {cardInfo}
          {customer}
          {cardId}
        </Definition>
      </ContentToggler>
    );
  } else if (paymentMethod === 'bank_transfer') {
    const isDetailsLoading =
      Object.keys(bankTransfer.details).length === 0 || bankTransfer.loading;

    bankTransfer = bankTransfer.details;

    el = (
      <ContentToggler>
        <span>Virtual Account</span>
        <Definition allowEmptyTitle={true}>
          {null}
          {!!(
            bankTransfer.virtual_account &&
            bankTransfer.virtual_account.description
          ) && <span>Virtual account description</span>}
          {isDetailsLoading ? (
            <PlaceholderLoader />
          ) : (
            <div>
              <div className="row m-b">
                <div className="col-sm-12">
                  <Link
                    to={`/virtualaccounts/${bankTransfer.virtual_account_id}`}
                  >
                    <code>{bankTransfer.virtual_account_id}</code>
                  </Link>
                </div>
              </div>
              <div className="row">
                <div className="col-sm-4 col-xs-5">Payer Name:</div>
                <div className="col-sm-8 col-xs-7">
                  {bankTransfer.payer_bank_account.name}
                </div>
              </div>
              <div className="row">
                <div className="col-sm-4 col-xs-5">Payer a/c:</div>
                <div className="col-sm-8 col-xs-7">
                  {bankTransfer.payer_bank_account.account_number}
                </div>
              </div>
              <div className="row">
                <div className="col-sm-4 col-xs-5">Payer IFSC:</div>
                <div className="col-sm-8 col-xs-7">
                  {bankTransfer.payer_bank_account.ifsc}
                </div>
              </div>
            </div>
          )}
        </Definition>
      </ContentToggler>
    );
  }

  return el;
};
