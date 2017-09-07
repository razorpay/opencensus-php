import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import CheckIcon from 'rzp/ui/CheckIcon';
import Alert from 'rzp/ui/Forms/Alert';
import DataTable from 'rzp/ui/Table/DataTable';
import { titleCase } from 'rzp/utils/rzp-utils';
import ListToggler from 'rzp/ui/Toggler/ListToggler';
import ListGroupToggler from 'rzp/ui/Toggler/ListGroupToggler';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';
import ShowWhen from 'merchant/components/ShowWhen';
import OtherDetail from 'merchant/components/OtherDetail';
import { refundId, amount, createdAt } from 'rzp/ui/item/pair';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { Link } from 'react-router-dom';

const shownByDefault = [
  'amount',
  'amount_refunded',
  'bank',
  'captured',
  'contact',
  'created_at',
  'currency',
  'description',
  'email',
  'entity',
  'error_code',
  'error_description',
  'fee',
  'id',
  'international',
  'method',
  'notes',
  'refund_status',
  'refunds',
  'tax',
  'service_tax',
  'status',
  'wallet',
];

const keysNotShown = entity => {
  var keys = [];

  if (!entity.getPayload) {
    return keys;
  }

  for (var key in entity) {
    let payload = entity.getPayload();
    // If the entity has that key and its not currently shown
    if (
      entity.hasOwnProperty(key) &&
      payload[key] !== undefined &&
      shownByDefault.indexOf(key) < 0 &&
      entity[key] !== null
    ) {
      keys.push(key);
    }
  }
  return keys;
};

export default props => {
  let { payment, card, refunds, isLoading, statusMsg = {} } = props;

  let otherKeys = payment && keysNotShown(payment);

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading
        ? <div class="page-spinner-container">
            <Spinner />
          </div>
        : <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              {props.onClose &&
                <button
                  type="button"
                  class="close close-secondary"
                  onClick={props.onClose}
                >
                  <i class="icon icon-close" />
                </button>}
              Payment Id: <b>{payment.id}</b>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <Alert type={statusMsg.type} message={statusMsg.message} />
                <div class="list-group pair-row-container">
                  <EntityDetailRow
                    label="Amount"
                    value={() => <Amount value={payment.amount} />}
                  />

                  <EntityDetailRow
                    label="Amount Refunded"
                    value={() => <Amount value={payment.amount_refunded} />}
                  />

                  <EntityDetailRow label="Currency" value={payment.currency} />
                  <EntityDetailRow
                    label="Status"
                    value={() => <PaymentStatusLabel status={payment.status} />}
                  />

                  <EntityDetailRow label="Captured" value={payment.captured} />

                  <ShowWhen featureEnabled="Marketplace">
                    <EntityDetailRow
                      label="Transfer"
                      value={() =>
                        <button
                          class="btn btn-default"
                          onClick={props.goToLink}
                        >
                          Create Transfer
                        </button>}
                    />
                  </ShowWhen>

                  <EntityDetailRow
                    label="Method"
                    value={titleCase(payment.method)}
                  />

                  {payment.bank
                    ? <EntityDetailRow label="Bank" value={payment.bank} />
                    : null}
                  {payment.wallet
                    ? <EntityDetailRow label="Wallet" value={payment.wallet} />
                    : null}
                  {
                    do {
                      if (card && payment.method === 'card') {
                        <ListGroupToggler
                          label="Card Details"
                          onToggleClick={() =>
                            props.onToggleCardDetails(payment)}
                        >
                          {Object.keys(card.details).map(key =>
                            <EntityDetailRow
                              key={key}
                              label={titleCase(key)}
                              value={card.details[key]}
                            />
                          )}
                        </ListGroupToggler>;
                      }
                    }
                  }

                  <EntityDetailRow
                    label="Refund Status"
                    value={titleCase(payment.refund_status)}
                  />

                  <EntityDetailRow
                    label="Description"
                    value={payment.description}
                  />

                  <EntityDetailRow label="Email" value={payment.email} />
                  <EntityDetailRow label="Contact" value={payment.contact} />

                  <EntityDetailRow
                    label="Fees"
                    value={() => <Amount value={payment.fee - payment.tax} />}
                  />

                  <EntityDetailRow
                    label="Tax"
                    value={() => <Amount value={payment.tax} />}
                  />

                  <EntityDetailRow
                    label="Total Fees"
                    value={() =>
                      <span data-tip="Total Fees is inclusive of tax charges">
                        <Amount value={payment.fee} />
                        <i class="icon icon-info-circle info-tooltip" />
                      </span>}
                  />

                  <EntityDetailRow
                    label="International"
                    value={() => <CheckIcon value={payment.international} />}
                  />

                  {otherKeys.map(key =>
                    <OtherDetail
                      key={key}
                      label={key}
                      value={payment[key]}
                      entity={payment}
                    />
                  )}
                  {payment.error_code
                    ? <EntityDetailRow
                        label="Error"
                        value={payment.error_code}
                      />
                    : null}
                  {payment.error_description
                    ? <EntityDetailRow
                        label="Error Description"
                        value={payment.error_description}
                      />
                    : null}

                  <NestedEntityDetailRow label="Notes" value={payment.notes} />

                  <EntityDetailRow
                    label="Created At"
                    value={() =>
                      <Time
                        value={payment.created_at}
                        format="DD MMM YYYY, hh:mm:ss a"
                      />}
                  />
                  {
                    do {
                      if (refunds) {
                        if (payment.refund_status) {
                          <ListToggler
                            label="Recently created Refunds"
                            subLabel="to this payment"
                            loading={refunds.loading}
                            totalItems={refunds.items.length}
                          >
                            <DataTable
                              customClass="refunds-table"
                              progressLoader={true}
                              title="Refunds"
                              columns={[refundId, amount]}
                              items={refunds.items}
                              loading={refunds.loading}
                              showHeaders={false}
                            />
                          </ListToggler>;
                        } else {
                          <EntityDetailRow
                            label="Refunds"
                            value="No Refunds"
                          />;
                        }
                      }
                    }
                  }
                </div>
                <ShowWhen myRole="owner manager operations admin">
                  <div>
                    <hr />
                    <div class="col-sm-offset-4 col-sm-8">
                      {payment.status === 'authorized'
                        ? <button
                            type="submit"
                            class="btn btn-success"
                            onClick={() => {
                              props.confirmCapture(payment);
                            }}
                          >
                            Capture Payment
                          </button>
                        : null}
                      {payment.status === 'captured' &&
                      payment.refund_status !== 'full'
                        ? <button
                            type="submit"
                            class="btn btn-primary"
                            onClick={() => {
                              props.openRefundModal(payment);
                            }}
                          >
                            Refund Payment
                          </button>
                        : null}
                    </div>
                  </div>
                </ShowWhen>
              </div>
            </div>
          </div>}
    </div>
  );
};
