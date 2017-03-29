import Time from 'rzp/ui/Time'
import TableBody from '../TableBody'

const WebhooksListItem = ({ webhook, canHighlightRow }) => {
  let activeEventsCount = Object.keys(webhook.events).filter((key) => webhook.events[key] === true).length

  return (
    <tr class={ canHighlightRow ? 'luminate' : '' }>
      <td>
        <code>{webhook.url}</code>
      </td>
      <td>
        <Time
          value={webhook.created_at}
          format='DD MMM YYYY, hh:mm:ss a'
        />
      </td>
      <td>
        <i class={`fa ${webhook.active ? 'fa-check text-success' : 'fa-times text-danger'}`}></i>
      </td>
      <td>
        { activeEventsCount } { activeEventsCount > 1 ? 'events' : 'event'} enabled
      </td>
    </tr>
  )
}

const WebhooksList = (props) => {
  let {
    webhooks,
    isLoading,
    modeFormatted,
    highlightRow,
  } = props
  let tableRowComponent

  return (
    <div class='table-responsive'>
      <table class='table table-hover'>
        <thead>
          <tr>
            <th>URL</th>
            <th>Created At</th>
            <th>Active</th>
            <th>Events</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={4}
          rows={webhooks}
          emptyTableRow={
            <tr>
              <td class='text-center empty-table' colSpan={4}>
                <button
                  class='btn btn-primary'
                  onClick={() => props.onSetupWebhookClick()}
                >
                  Setup your {modeFormatted} Webhook
                </button>
              </td>
            </tr>
          }
        >
          {
            webhooks.map((webhook) =>
              <WebhooksListItem
                key={webhook.id}
                canHighlightRow={highlightRow(webhook)}
                webhook={webhook}
              />
            )
          }
          {
            !isLoading &&
              <tr class='action-row'>
                <td class='text-center' colSpan='4'>
                  <button
                    class='btn btn-primary'
                    onClick={() => props.onSetupWebhookClick(webhooks[0])}
                  >
                    {
                      webhooks.length ?
                      `Edit your ${modeFormatted} Webhook` :
                      `Setup your ${modeFormatted} Webhook`
                    }
                  </button>
                </td>
              </tr>
          }
        </TableBody>
      </table>
    </div>
  )
}

WebhooksList.defaultProps = {
  highlightRow: () => {}
}

export default WebhooksList
