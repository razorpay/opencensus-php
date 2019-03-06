import Field, { SelectField, SearchableSelectField } from 'ui/Field';

export default function PerTransactionForm({ plans, values, internals }) {
  const { commission, pricing } = plans;
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
        defaultValue={values.default_plan_id}
        pending={pricing.pending}
      />

      <Searchable
        name="implicit_plan_id"
        trackBy="id"
        label="Partner Pricing"
        options={internals === 'fixed' ? commission.data : pricing.data}
        defaultValue={values.implicit_plan_id}
        pending={pricing.pending || commission.pending}
      />

      <Searchable
        name="explicit_plan_id"
        trackBy="id"
        label="Add-on Commission"
        options={commission.data}
        helpMsg="This will be exposed to sub-merchants"
        defaultValue={values.explicit_plan_id}
        pending={commission.pending}
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
