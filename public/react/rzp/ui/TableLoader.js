import Spinner from 'rzp/ui/Spinner'

export default (props) => {
  return (
    <tr>
      <td className='text-center' colSpan={props.colSpan} style={{padding: '50px'}}>
        <Spinner />
      </td>
    </tr>
  )
}
