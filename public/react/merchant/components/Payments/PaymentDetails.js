import AsyncButton from 'react-async-button'
import Amount from 'rzp/ui/Amount'
import Time from 'rzp/ui/Time'
import Spinner from 'rzp/ui/Spinner'
import CheckIcon from 'rzp/ui/CheckIcon'
import Alert from 'rzp/ui/Forms/Alert'
import { titleCase } from 'rzp/utils/rzp-utils'
import ListGroupToggler from 'rzp/ui/ListGroupToggler'
import { PaymentStatusLabel } from 'merchant/components/StatusLabel'
import ShowWhen from 'merchant/components/ShowWhen'
import TableBody from 'merchant/components/TableBody'
import DetailRow from 'merchant/components/DetailRow'

const ListItem = ({ item, value }) => {
  return (
    <tr>
      <td>
        {item}
      </td>
      <td class='text-right'>
        {value}
      </td>
    </tr>
  )
}

const RefundsListItem = ({ refund }) => {
  return (
    <tr>
      <td>
        <a
          target='_blank'
          href={`#/app/refunds/${refund.id}`}
        >
          {refund.id}
        </a>
      </td>
      <td>
        <Amount value={refund.amount} />
      </td>
      <td class='text-right'>
        <Time
          value={refund.created_at}
          format='DD MMM YYYY, hh:mm:ss a'
        />
      </td>
    </tr>
  )
}

export default (props) => {
  let {
    payment,
    card,
    refunds,
    isLoading,
    statusMsg
  } = props

  return (
    <div>
      {
        isLoading ?
        <div class='page-spinner-container'>
          <Spinner />
        </div> :
        <div class='panel-detail-container'>
          <Alert type={statusMsg.type} message={statusMsg.message} />

          <div class='panel panel-default'>
            <div class='panel-heading'>
              Payment ID: <b>{payment.id}</b>
            </div>

            <div class='panel-body'>
              <div class='list-group'>
                <DetailRow
                  label='Amount'
                  value={ () => <Amount value={payment.amount} /> }
                />

                <DetailRow
                  label='Amount Refunded'
                  value={ () => <Amount value={payment.amount_refunded} /> }
                />

                <DetailRow label='Currency' value={payment.currency} />
                <DetailRow
                  label='Status'
                  value={ () => <PaymentStatusLabel status={payment.status} /> }
                />

                <DetailRow
                  label='Captured'
                  value={payment.captured}
                />

                <DetailRow
                  label='Method'
                  value={titleCase(payment.method)}
                />

                {
                  payment.bank ?
                  <DetailRow
                    label='Bank'
                    value={payment.bank}
                  /> : null
                }
                {
                  payment.wallet ?
                  <DetailRow
                    label='Wallet'
                    value={payment.wallet}
                  /> : null
                }
                {
                  payment.method === 'card' ?
                  <ListGroupToggler
                    label='Card Details'
                    onToggleClick={() => props.onToggleCardDetails(payment)}
                  >
                    <table class='table table-hover table-striped'>
                      <TableBody
                        colSpan={2}
                        isLoading={card.loading}
                        rows={Object.keys(card.details)}
                      >
                        {
                          Object.keys(card.details).map((key) =>
                            <ListItem key={key}
                                      item={titleCase(key)}
                                      value={card.details[key]}
                            />
                          )
                        }
                      </TableBody>
                    </table>
                  </ListGroupToggler> : null
                }

                <DetailRow
                  label='Refund Status'
                  value={titleCase(payment.refund_status)}
                />

                <DetailRow
                  label='Description'
                  value={payment.description}
                />

                <DetailRow
                  label='Email'
                  value={payment.email}
                />

                <DetailRow
                  label='Contact'
                  value={payment.contact}
                />

                <DetailRow
                  label='Fees'
                  value={ () => <Amount value={payment.fee - payment.service_tax} /> }
                />

                <DetailRow
                  label='Service Tax'
                  value={ () => <Amount value={payment.service_tax} /> }
                />

                <DetailRow
                  label='Total Fees'
                  tooltip='Total Fees is inclusive of Service Tax charges'
                  value={ () => <Amount value={payment.fee} /> }
                />

                <DetailRow
                  label='International'
                  value={ () =>  <CheckIcon value={payment.international} /> }
                />

                <DetailRow
                  label='Base Amount'
                  value={ () =>  <Amount value={payment.base_amount} /> }
                />

                <DetailRow
                  label='Amount Paidout'
                  value={ () =>  <Amount value={payment.amount_paidout} /> }
                />
                {
                  payment.error_code ?
                  <DetailRow
                    label='Error'
                    value={payment.error_code}
                  /> : null
                }
                {
                  payment.error_description ?
                  <DetailRow
                    label='Error Description'
                    value={payment.error_description}
                  /> : null
                }
                {
                  Object.keys(payment.notes).length > 0 ?
                    <ListGroupToggler
                      label='Notes'
                    >
                      <table class='table table-hover table-striped'>
                        <TableBody
                          colSpan={2}
                          rows={Object.keys(payment.notes)}
                        >
                          {
                            Object.keys(payment.notes).map((note) =>
                              <ListItem key={note}
                                        item={note}
                                        value={payment.notes[note]}
                              />
                            )
                          }
                        </TableBody>
                      </table>
                    </ListGroupToggler> :
                    <DetailRow label='Notes' value='No Notes' />
                }

                <DetailRow
                  label='Created At'
                  value={ () => <Time value={payment.created_at} format='DD MMM YYYY, hh:mm:ss a' /> }
                />
                {
                  payment.refund_status ?
                  <ListGroupToggler
                    label='Refunds'
                    onToggleClick={() => props.onToggleRefundList(payment)}
                  >
                    <table class='table table-hover table-striped'>
                      <TableBody
                        colSpan={2}
                        isLoading={refunds.loading}
                        rows={refunds.items}
                      >
                        {
                          refunds.items.map((refund) =>
                            <RefundsListItem key={refund.id}
                                             refund={refund}
                            />
                          )
                        }
                      </TableBody>
                    </table>
                  </ListGroupToggler> :
                  <DetailRow label='Refunds' value='No Refunds' />
                }
              </div>
              <ShowWhen myRole='owner manager operations admin'>
                <div class='text-center'>
                  {
                    payment.status === 'authorized' ?
                    <button
                      type='submit'
                      class='btn btn-success'
                      onClick={()=> {props.confirmCapture(payment)}}
                    >
                      Capture Payment
                    </button> : null
                  }
                  {
                    payment.status === 'captured' &&
                    payment.refund_status !== 'full' ?
                    <button
                      type='submit'
                      class='btn btn-primary'
                      onClick={()=> {props.openRefundModal(payment)}}
                    >
                      Refund Payment
                    </button>: null
                  }
                </div>
              </ShowWhen>
            </div>
          </div>
        </div>
      }
    </div>
  )
}
