import FormWrapper from 'merchant/views/MagicCheckout/MagicSettings/components/common/FormWrapper';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';

const Form = ({ formTitle, formItems, onToggle, extraClass }) => {
  return (
    <FormWrapper formTitle={formTitle} extraClass={extraClass}>
      {formItems.map((setting) => (
        <SettingsToggle key={setting.label} setting={setting} onToggle={onToggle} />
      ))}
    </FormWrapper>
  );
};

export default Form;
