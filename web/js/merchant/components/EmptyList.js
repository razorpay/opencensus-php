const EmptyList = ({ description }) => (
  <div class="EmptyList">
    <div class="dummy-circle" />
    <div class="description">{description}</div>
  </div>
);

export const EmptyListWithTableRow = (props) => (
  <tr>
    <td class="text-center empty-table" colSpan={props.colSpan}>
      <EmptyList {...props} />
    </td>
  </tr>
);

export default EmptyList;
