import EntityDetailRow from 'merchant/components/EntityDetailRow';
import PairListGroupToggler from 'rzp/ui/PairListGroupToggler';

export default ({ label, value = {} }) => {
  if (Object.keys(value).length) {
    return (
      <PairListGroupToggler label={label} show={false}>
        {Object.keys(value).length
          ? <div class="table-responsive">
              {Object.keys(value).map(key => (
                <div key={key} class="pair-list-item">
                  <div class="item-label">{key}</div>
                  <div class="items-value">{value[key]}</div>
                </div>
              ))}
            </div>
          : null}
      </PairListGroupToggler>
    );
  }

  return <EntityDetailRow label={label} value="--" />;
};
