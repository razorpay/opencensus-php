export default props => {
  const { EmptyComponent } = props;
  return (
    <tr>
      <td class="text-center empty-table" colSpan={props.colSpan}>
        {EmptyComponent ? (
          <EmptyComponent />
        ) : (
          <h4>{props.message || 'No data found!'}</h4>
        )}
      </td>
    </tr>
  );
};
