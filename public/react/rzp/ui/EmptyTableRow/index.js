import './EmptyTableRow.styl'

export default (props) => {
  return (
    <tr>
      <td class='text-center empty-table' colSpan={props.colSpan}>
        <h4>{props.message || 'No data found!'}</h4>
      </td>
    </tr>
  )
}
