import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailToggler from 'common/ui/Toggler/NestedEntityDetailToggler';

export default ({ label, value = {} }) => {
  const placeholder = '--';

  if (value && Object.keys(value).length) {
    return (
      <NestedEntityDetailToggler label={label} show={false}>
        <div className="table-responsive">
          {Object.keys(value).map((key) => (
            <div key={key} className="pair-list-item">
              <div className="item-label">{key}</div>
              <div className="items-value">{value[key] || placeholder}</div>
            </div>
          ))}
        </div>
      </NestedEntityDetailToggler>
    );
  }

  return <EntityDetailRow label={label} value={placeholder} />;
};
