const EmptyList = ({ description }) => (
  <div className="EmptyList">
    <div className="dummy-circle" />
    <div className="description">{description}</div>
  </div>
);

export const EmptyListWithTableRow = (props) => (
  <tr>
    <td className="text-center empty-table" colSpan={props.colSpan}>
      <EmptyList {...props} />
    </td>
  </tr>
);

export default EmptyList;
