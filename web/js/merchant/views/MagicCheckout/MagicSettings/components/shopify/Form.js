import FormWrapper from 'merchant/views/MagicCheckout/common/components/FormWrapper';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';

const Form = ({ formTitle, formItems, onToggle }) => {
  return (
    <FormWrapper formTitle={formTitle}>
      {formItems.map((setting) => (
        <SettingsToggle key={setting.label} setting={setting} onToggle={onToggle} />
      ))}
    </FormWrapper>
  );
};

export default Form;
