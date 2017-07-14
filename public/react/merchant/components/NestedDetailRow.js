import DetailRow from 'merchant/components/DetailRow';
import ListGroupToggler from 'rzp/ui/ListGroupToggler';

export default ({ label, value = {} }) => {
  if (Object.keys(value).length) {
    return (
      <div class="detail-notes">
        <ListGroupToggler label={label} show={true}>
          {Object.keys(value).map(key => (
            <DetailRow label={key} value={value[key]} />
          ))}
        </ListGroupToggler>
      </div>
    );
  }
  return <DetailRow label={label} value="--" />;
};
