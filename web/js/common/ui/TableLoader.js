import Spinner from 'common/ui/Spinner';

export default (props) => {
  return (
    <tr>
      <td className="text-center empty-table" colSpan={props.colSpan}>
        <Spinner />
      </td>
    </tr>
  );
};
