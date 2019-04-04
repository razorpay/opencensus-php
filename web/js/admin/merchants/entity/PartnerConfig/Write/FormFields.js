import Field, {
  SwitchField,
  SelectField,
  SearchableSelectField,
  DateField,
} from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import EntityRow from 'ui/EntityRow';

export default function FormFields({
  values,
  internals,
  plans,
  handleChange,
  handleDateChange,
  onSearchableChange,
  ...props
}) {
  const { commission, pricing } = plans;

  let implicitPlanOptions = [],
    implicitFieldLabel;
  if (internals._commission_mode) {
    if (internals._commission_mode === 'fixed') {
      implicitPlanOptions = commission.data;
      implicitFieldLabel = 'Commission';
    } else {
      implicitPlanOptions = pricing.data;
      implicitFieldLabel = 'Partner Pricing';
    }
  }

  return (
    <>
      {props.showSubmerchantPricing && (
        <Searchable
          name="default_plan_id"
          label="Default sub-merchant pricing"
          options={pricing.data}
          pending={pricing.pending}
          selected={findSelectedName(pricing.data, values.default_plan_id)}
          onChange={onSearchableChange('default_plan_id')}
          helpMsg={
            props.isUpdate
              ? 'If changed, this will not update the pricing plans of existing sub-merchants'
              : 'This pricing plan will assigned by default to all new sub-merchants added by the partner'
          }
        />
      )}

      <SwitchField
        label="Commission"
        name="commissions_enabled"
        enabledLabel="Enable"
        disabledLabel="Disable"
        onChange={handleChange}
        defaultValue={values.commissions_enabled}
      />

      <EntityRow
        label={<strong>Base Commission</strong>}
        value={' '}
        style={{ paddingLeft: '0' }}
        class="m-t"
      />

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
          label={implicitFieldLabel}
          options={implicitPlanOptions}
          pending={pricing.pending || commission.pending}
          selected={findSelectedName(
            implicitPlanOptions,
            values.implicit_plan_id
          )}
          onChange={onSearchableChange('implicit_plan_id')}
        />
      )}

      <DateField
        name="implicit_expiry_at"
        label="Expiry Date"
        allowToday={false}
        disablePastDates
        onChange={handleDateChange('implicit_expiry_at')}
        defaultValue={getDefaultDateVal(values.implicit_expiry_at)}
        helpMsg="If present, commissions will automativally expire after this date"
      />

      <EntityRow
        label={<strong>Add-on Commission</strong>}
        value={' '}
        style={{ paddingLeft: '0' }}
        class="m-t"
      />

      <Searchable
        name="explicit_plan_id"
        label="Add-on Commission"
        options={commission.data}
        pending={commission.pending}
        selected={findSelectedName(commission.data, values.explicit_plan_id)}
        onChange={onSearchableChange('explicit_plan_id')}
        helpMsg="This will be exposed to sub-merchants, Add-on commission does not expire"
      />

      <SwitchField
        name="explicit_should_charge"
        label="Charge Add-on Commission"
        enabledLabel="Yes"
        disabledLabel="No"
        onChange={handleChange}
        defaultValue={values.explicit_should_charge}
        value={values.explicit_should_charge}
        disabled={!values.explicit_plan_id}
      />

      <SwitchField
        name="explicit_refund_fees"
        label="Refund Add-on Commission on payment refund"
        enabledLabel="Yes"
        disabledLabel="No"
        onChange={handleChange}
        defaultValue={values.explicit_refund_fees}
        value={values.explicit_refund_fees}
        disabled={!values.explicit_should_charge}
        helpMsg="If No, we'll only record but not charge Add-on commission from sub-merchant"
      />

      <DateField
        name="revisit_at"
        label="Revisit Date"
        allowToday={false}
        disablePastDates
        onChange={handleDateChange('revisit_at')}
        defaultValue={getDefaultDateVal(values.revisit_at)}
      />

      <AsyncButton
        text={props.buttonText}
        class="btn"
        pendingClass="small spinner"
        onSubmit={props.onSubmit}
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

function getDefaultDateVal(unixTime) {
  return unixTime ? moment(unixTime, 'X') : null;
}
