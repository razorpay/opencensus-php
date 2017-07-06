// Blame @aseem for suggesting this name

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import ListGroupToggler from 'rzp/ui/ListGroupToggler';
import TableBody from 'rzp/ui/TableBody';

export default ({ label, value = {} }) => {
  if (Object.keys(value).length) {
    return (
      <ListGroupToggler label={label} show={true}>
        <div class="table-responsive">
          <table class="table table-hover">
            <TableBody colSpan={2} rows={Object.keys(value)}>
              {Object.keys(value).map(key => (
                <tr key={key}>
                  <td colSpan="2">
                    <EntityDetailRow label={key} value={value[key]} />
                  </td>
                </tr>
              ))}
            </TableBody>
          </table>
        </div>
      </ListGroupToggler>
    );
  }
  return <EntityDetailRow label={label} value="--" />;
};
