import { Info } from 'component/Input';

export default ({ children, infoTxt, ...rest }) => (
  <div class="wysiwyg-edit-layer" {...rest}>
    {children}
    {infoTxt && <Info text={infoTxt} />}
  </div>
);
