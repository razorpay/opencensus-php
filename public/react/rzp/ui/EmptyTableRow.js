export default (props) => {
  return (
    <tr>
      <td className='text-center' colSpan={props.colSpan} style={{padding: '50px'}}>
        <h4>{props.message || 'No data found!'}</h4>
      </td>
    </tr>
  )
}
