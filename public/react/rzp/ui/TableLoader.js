import Spinner from 'rzp/ui/Spinner'
import './EmptyTableRow/EmptyTableRow.styl'

export default (props) => {
  return (
    <tr>
      <td class='text-center empty-table' colSpan={props.colSpan}>
        <Spinner />
      </td>
    </tr>
  )
}
