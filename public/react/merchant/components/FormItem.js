import EntityDetailRow from './EntityDetailRow';

export default ({ label, field, ...otherProps }) => {
  return <EntityDetailRow label={label} value={field} {...otherProps} />;
};
