import TableBody from '../TableBody'
import Time from 'rzp/ui/Time'
import Key from 'merchant/models/Key'
// import RegenerateKey from 'merchant/models/Key'

const KeysListItem = (props) => {
  let mode = props.mode
  let { id, created_at, expired_at } = props.apiKey
  return (
    <tr>
      <td>
        {id}
      </td>
      <td>
        <Time value={created_at} format={'MMM Do, YYYY hh:mm:ss A'} />
      </td>
      <td>
        {
          expired_at ?
          <Time value={expired_at} format={'MMM Do, YYYY hh:mm:ss A'} /> :
          'Never'
        }
      </td>
      <td>
        {
          expired_at ? 'None' :
          <div class='row-action'>
            <button class='btn btn-xs btn-primary' onClick={() => {
              props.showRollKeyModal({id})
            }}>
              <i class='fa fa-refresh'></i>
              <span>Regenerate {mode} Key</span>
            </button>
          </div>
        }
      </td>
    </tr>
  )
}

export default (props) => {
  let {
    mode,
    keys,
    isLoading,
    merchantId,
    showRollKeyModal = () => {},
    generateKey = () => {}
  } = props

  let params = {
    merchantId: merchantId
  }

  return (
    <div>
      <div class='table-responsive'>
        <table class='table table-hover'>
          <thead>
            <tr>
              <th>Key Id</th>
              <th>Created At</th>
              <th>Expiry</th>
              <th>Action</th>
            </tr>
          </thead>
          <TableBody
            isLoading={isLoading}
            colSpan={4}
            rows={keys}
            emptyTableRow={
              <tr>
                <td class='text-center empty-table' colSpan={4}>
                  <button class='btn btn-primary' onClick={() =>{
                      generateKey(params)
                    }
                  }>
                    Generate {mode} Key
                  </button>
                </td>
              </tr>
            }
          >
            {
              keys.map((key) =>
                <KeysListItem
                  key={key.id}
                  apiKey={key}
                  mode={mode}
                  showRollKeyModal={showRollKeyModal}
                />
              )
            }
          </TableBody>
        </table>
      </div>
    </div>
  )
}