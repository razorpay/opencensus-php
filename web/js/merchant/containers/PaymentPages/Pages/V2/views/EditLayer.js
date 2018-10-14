import { Info } from 'component/Input';
import { classList } from 'common/util';

export default ({ children, infoTxt, customClass, ...rest }) => (
  <div {...rest} class={classList('wysiwyg-edit-layer', customClass)}>
    {children}
    {infoTxt && <Info text={infoTxt} />}
  </div>
);
