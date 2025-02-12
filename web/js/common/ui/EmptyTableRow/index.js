export default props => {
  return (
    <tr>
      <td className="text-center empty-table" colSpan={props.colSpan}>
        <h4>{props.message || 'No data found!'}</h4>
      </td>
    </tr>
  );
};
