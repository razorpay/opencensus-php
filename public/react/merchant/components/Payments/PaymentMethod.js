import React from 'react';
import Definition from 'rzp/ui/Definition';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
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
 * the content will be shown according to the Design^
 */
export default ({ payment, card = {} }) => {
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
        {paymentMethod === 'upi' &&
          <span>
            {payment.vpa}
          </span>}
      </Definition>
    );
  } else if (paymentMethod === 'card') {
    if (Object.keys(card) === 0 || card.loading) {
      return <PlaceholderLoader />;
    }

    const cardTitle = (
        <span>
          {cardDetails.emi ? 'EMI on ' : ''}
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
          <b>
            {cardDetails.last4}
          </b>
        </span>
      ),
      customer = (
        <span>
          Name on card - {cardDetails.name}
        </span>
      ),
      cardId = (
        <code>
          {cardDetails.id}
        </code>
      );

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
  }

  return el;
};
