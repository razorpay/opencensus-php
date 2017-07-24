import TableBody from 'rzp/ui/TableBody';
import { titleCase } from 'rzp/utils/rzp-utils';

const ListItem = ({ item, value }) => {
  return (
    <tr>
      <td>
        {item}
      </td>
      <td class="text-right">
        {value}
      </td>
    </tr>
  );
};

export default function ObjectTable(props) {
  let { loading, objDetails } = props;

  return (
    <div class="table-responsive">
      <table class="table table-hover table-striped">
        <TableBody
          colSpan={2}
          isLoading={loading}
          rows={Object.keys(objDetails)}
        >
          {Object.keys(objDetails).map(key => (
            <ListItem key={key} item={titleCase(key)} value={objDetails[key]} />
          ))}
        </TableBody>
      </table>
    </div>
  );
}
