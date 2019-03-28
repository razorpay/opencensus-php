import Field, { SelectField, SearchableSelectField } from 'ui/Field';

export default function PerTransactionForm({
  plans,
  values,
  internals,
  ...props
}) {
  const { commission, pricing } = plans;

  let implicitPlanOptions = [];
  if (internals._commission_mode) {
    implicitPlanOptions =
      internals._commission_mode === 'fixed' ? commission.data : pricing.data;
  }

  return (
    <>
      {!pricing.pending &&
        !commission.pending && (
          <SelectField
            data-name="_commission_mode"
            label="Commission Type"
            value={internals._commission_mode}
          >
            <option value="">Select...</option>
            <option value="variable">Variable</option>
            <option value="fixed">Fixed</option>
          </SelectField>
        )}

      {!!internals._commission_mode && (
        <Searchable
          name="implicit_plan_id"
          label="Partner Pricing"
          options={implicitPlanOptions}
          pending={pricing.pending || commission.pending}
          selected={findSelectedName(
            implicitPlanOptions,
            values.implicit_plan_id
          )}
          onChange={props.onSearchableChange('implicit_plan_id')}
        />
      )}

      {props.showSubmerchantPricing && (
        <Searchable
          name="default_plan_id"
          label="Default sub-merchant pricing"
          options={pricing.data}
          pending={pricing.pending}
          selected={findSelectedName(pricing.data, values.default_plan_id)}
          onChange={props.onSearchableChange('default_plan_id')}
          helpMsg={
            props.isUpdate
              ? 'This will not update the pricing plans of existing submerchants'
              : ''
          }
        />
      )}

      <Searchable
        name="explicit_plan_id"
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
    <SearchableSelectField
      selectedOptionLabelPath="label"
      searchIndices={['name', 'value']}
      selectedOptionComponent={PowerSelectOption}
      optionComponent={PowerSelectOption}
      {...props}
    />
  );
}

function findSelectedName(options, selectedId) {
  return options.find(({ value }) => value === selectedId);
}

function PowerSelectOption({ option }) {
  return (
    <span>
      <strong>{option.name}</strong> - <em>{option.value}</em>
    </span>
  );
}
