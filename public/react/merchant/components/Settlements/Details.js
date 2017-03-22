import Amount from 'rzp/ui/Amount'
import Time from 'rzp/ui/Time'
import Spinner from 'rzp/ui/Spinner'
import Alert from 'rzp/ui/Forms/Alert'
import ListGroupToggler from 'rzp/ui/ListGroupToggler'
import { SettlementStatusLabel } from 'merchant/components/StatusLabel'
import SettlementBreakupTable from './BreakupTable'

export default (props) => {
  let {
    settlement,
    breakupDetails,
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
              Settlement ID: <b>{settlement.id}</b>
            </div>

            <div class='panel-body'>
              <div class='list-group'>
                <div class='list-group-item'>
                  <span>Amount</span>
                  <Amount value={settlement.amount} />
                </div>

                <div class='list-group-item'>
                  <span>Status</span>
                  <SettlementStatusLabel status={settlement.status} />
                </div>

                <div class='list-group-item'>
                  <span>Created At</span>
                  <Time
                    value={settlement.created_at}
                    format='DD MMM YYYY, hh:mm:ss a'
                  />
                </div>

                <div class='list-group-item'>
                  <span>Fees</span>
                  <Amount value={settlement.fees} />
                </div>

                <div class='list-group-item'>
                  <span>UTR</span>
                  <span>{settlement.utr}</span>
                </div>

                <div class='list-group-item'>
                  <span>Service Tax</span>
                  <Amount value={settlement.service_tax} />
                </div>

                <ListGroupToggler
                  label='Breakup'
                  onToggleClick={() => props.onToggleBreakupDetails(settlement)}
                >
                  <SettlementBreakupTable
                    items={breakupDetails.items}
                    loading={breakupDetails.loading}
                  />
                </ListGroupToggler>

              </div>
            </div>
          </div>
        </div>
      }
    </div>
  )
}
