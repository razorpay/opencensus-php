import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailToggler from 'common/ui/Toggler/NestedEntityDetailToggler';

export default ({ label, value = {} }) => {
  const placeholder = '--';

  if (value && Object.keys(value).length) {
    return (
      <NestedEntityDetailToggler label={label} show={false}>
        <div class="table-responsive">
          {Object.keys(value).map((key) => (
            <div key={key} class="pair-list-item">
              <div class="item-label">{key}</div>
              <div class="items-value">{value[key] || placeholder}</div>
            </div>
          ))}
        </div>
      </NestedEntityDetailToggler>
    );
  }

  return <EntityDetailRow label={label} value={placeholder} />;
};
