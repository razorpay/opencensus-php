// Blame @aseem for suggesting this name

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import ListGroupToggler from 'rzp/ui/ListGroupToggler';

export default ({ label, value = {} }) => {
  if (Object.keys(value).length) {
    return (
      <div class="detail-notes">
        <ListGroupToggler label={label} show={true}>
          {Object.keys(value).map(key => (
            <EntityDetailRow label={key} value={value[key]} />
          ))}
        </ListGroupToggler>
      </div>
    );
  }
  return <EntityDetailRow label={label} value="--" />;
};
