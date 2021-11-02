import { Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import React from 'react';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { titleCase, getEMI } from 'common/utils/rzp-utils';

/*
 * Design:
 * https://projects.invisionapp.com/d/main#/console/11691503/247373211/preview
 * Inputs:
 * @param {Object} payment
 * @param {Object} card
 * @param {Object} bankTransfer
 * @param {Object} upiTransfer
 *
 * Description:
 * Given the `payment` parameter exactly the same as
 * fetch payments api and `card` parameter as fetch card details api,
 * `bankTransfer` as fetch bank transfer api,
 * the content will be shown according to the Design^
 */
export default ({ payment, card = {}, bankTransfer = {}, upiTransfer = {} }) => {
  const paymentMethod = payment.method;

  const methodKeyMap = {
    netbanking: 'bank',
    wallet: 'wallet',
    emandate: 'emandate',
    aeps: 'aeps',
    cardless_emi: 'cardless_emi',
    paylater: 'paylater',
  };

  const cardDetails = card || {};

  let el = null;

  if (methodKeyMap[paymentMethod]) {
    let paymentMethodText = payment[methodKeyMap[paymentMethod]];

    paymentMethodText =
      paymentMethod !== 'netbanking' ? titleCase(paymentMethodText) : paymentMethodText;
    el = (
      <Definition>
        <span>{`${paymentMethodText}' '${titleCase(paymentMethod)}`}</span>
      </Definition>
    );
  } else if (['card', 'emi'].indexOf(paymentMethod) !== -1) {
    if (Object.keys(card) === 0 || card.loading) {
      return <PlaceholderLoader />;
    }

    const emiPlan = payment.emi_plan;
    const emi = !!emiPlan && getEMI(payment.amount, emiPlan.duration, emiPlan.rate / 100);

    const subTypeMap = {
      consumer: 'Consumer',
      business: 'Business',
    };

    const cardTitle = (
      <span>
        {paymentMethod === 'emi' ? 'EMI on ' : ''}
        {subTypeMap[cardDetails.sub_type]}{' '}
        {cardDetails.international ? 'International ' : 'Domestic '}
        {cardDetails.type !== 'unknown' && titleCase(`${cardDetails.type} `)}
        Card
      </span>
    );
    const cardInfo = (
      <span>
        <span>
          {cardDetails.issuer ? `${cardDetails.issuer} ,` : ''}
          {`${cardDetails.network} ending `}
        </span>
        <b>{cardDetails.last4}</b>
      </span>
    );
    const customer = <span>Name on card - {cardDetails.name}</span>;
    const emiInfo = emi !== false && (
      <span>
        <span>{emiPlan.duration} Months EMI at</span>
        <span> {emiPlan.rate / 100}%</span>
        <span>
          {' '}
          (<Amount value={emi} currency={payment.currency} />)
        </span>
      </span>
    );
    const cardId = <code>{cardDetails.id}</code>;

    el = (
      <ContentToggler>
        {cardTitle}
        <Definition allowEmptyTitle={true}>
          {null}
          {cardInfo}
          {customer}
          {emiInfo}
          {cardId}
        </Definition>
      </ContentToggler>
    );
  } else if (paymentMethod === 'upi') {
    const isDetailsLoading = upiTransfer.loading;
    const isVPADetailsAvailable = Object.keys(upiTransfer.details).length !== 0;

    let content;

    if (isDetailsLoading) {
      content = (
        <Definition allowEmptyTitle={true}>
          <PlaceholderLoader />;
        </Definition>
      );
    } else {
      upiTransfer = isVPADetailsAvailable ? upiTransfer.details : null;

      content = (
        <Definition allowEmptyTitle={true}>
          {upiTransfer &&
            upiTransfer.virtual_account &&
            upiTransfer.virtual_account.description && (
              <span>{upiTransfer.virtual_account.description}</span>
            )}

          {
            <div>
              {upiTransfer && (
                <div class="row m-b">
                  <div class="col-sm-12">
                    <Link to={`/virtualaccounts/${upiTransfer.virtual_account_id}`}>
                      <code>{upiTransfer.virtual_account_id}</code>
                    </Link>
                  </div>
                </div>
              )}
              <div class="row">
                <div class="col-sm-12">
                  <div class="row">
                    <div class="col-sm-5 col-xs-5">Payer UPI ID:</div>
                    <div class="col-sm-7 col-xs-7">
                      {upiTransfer ? upiTransfer.payer_vpa : payment.vpa}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          }
        </Definition>
      );
    }

    el = (
      <ContentToggler>
        <span>UPI</span>
        {content}
      </ContentToggler>
    );
  } else if (paymentMethod === 'bank_transfer') {
    const isDetailsLoading = Object.keys(bankTransfer.details).length === 0 || bankTransfer.loading;

    bankTransfer = bankTransfer.details;

    el = (
      <ContentToggler>
        <span>Bank Transfer</span>
        <Definition allowEmptyTitle={true}>
          {null}
          {!!(bankTransfer.virtual_account && bankTransfer.virtual_account.description) && (
            <span>{bankTransfer.virtual_account.description}</span>
          )}
          {isDetailsLoading ? (
            <PlaceholderLoader />
          ) : (
            <div>
              <div class="row m-b">
                <div class="col-sm-12">
                  <Link to={`/virtualaccounts/${bankTransfer.virtual_account_id}`}>
                    <code>{bankTransfer.virtual_account_id}</code>
                  </Link>
                </div>
              </div>
              {!!bankTransfer.payer_bank_account && (
                <div class="row">
                  <div class="col-sm-12">
                    <div class="row">
                      <div class="col-sm-4 col-xs-5">Payer Name:</div>
                      <div class="col-sm-8 col-xs-7">{bankTransfer.payer_bank_account.name}</div>
                    </div>
                    <div class="row">
                      <div class="col-sm-4 col-xs-5">Payer a/c:</div>
                      <div class="col-sm-8 col-xs-7">
                        {bankTransfer.payer_bank_account.account_number}
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-sm-4 col-xs-5">Payer IFSC:</div>
                      <div class="col-sm-8 col-xs-7">{bankTransfer.payer_bank_account.ifsc}</div>
                    </div>
                  </div>
                </div>
              )}
            </div>
          )}
        </Definition>
      </ContentToggler>
    );
  } else if (paymentMethod === 'app') {
    el = (
      <Definition>
        <span>Application</span>
      </Definition>
    );
  } else if (paymentMethod === 'cod') {
    el = (
      <Definition>
        <span>Cash on Delivery</span>
      </Definition>
    );
  } else if (paymentMethod === 'unselected') {
    el = (
      <Definition>
        <span>{titleCase(paymentMethod)}</span>
      </Definition>
    );
  }

  return el;
};
