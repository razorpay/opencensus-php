import Field, { SelectField, SearchableSelectField } from 'ui/Field';

export default function PerTransactionForm({
  plans,
  values,
  internals,
  ...props
}) {
  const { commission, pricing } = plans;
  const implicitPlanOptions =
    internals._commission_mode === 'fixed' ? commission.data : pricing.data;

  return (
    <>
      <SelectField data-name="_commission_mode" label="Commission Type">
        <option value="variable">Variable</option>
        <option value="fixed">Fixed</option>
      </SelectField>

      <Searchable
        name="default_plan_id"
        trackBy="id"
        label="Default sub-merchant pricing"
        options={pricing.data}
        pending={pricing.pending}
        selected={findSelectedName(pricing.data, values.default_plan_id)}
        onChange={props.onSearchableChange('default_plan_id')}
      />

      <Searchable
        name="implicit_plan_id"
        trackBy="id"
        label="Partner Pricing"
        options={implicitPlanOptions}
        pending={pricing.pending || commission.pending}
        selected={findSelectedName(
          implicitPlanOptions,
          values.implicit_plan_id
        )}
        onChange={props.onSearchableChange('implicit_plan_id')}
      />

      <Searchable
        name="explicit_plan_id"
        trackBy="id"
        label="Add-on Commission"
        options={commission.data}
        helpMsg="This will be exposed to sub-merchants"
        pending={commission.pending}
        selected={findSelectedName(commission.data, values.explicit_plan_id)}
        onChange={props.onSearchableChange('explicit_plan_id')}
      />
    </>
  );
}

function Searchable({ pending, ...props }) {
  return pending ? (
    <Field label={props.label} value="Loading..." disabled />
  ) : (
    <SearchableSelectField {...props} />
  );
}

function findSelectedName(options, selectedId) {
  return (options.find(({ id }) => id === selectedId) || {}).name || '';
}
