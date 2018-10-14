import { Info } from 'component/Input';
import { classList } from 'common/util';

export default ({ children, infoTxt, customClass, onClick, ...rest }) => (
  <div
    {...rest}
    onClick={onClick}
    class={classList('wysiwyg-edit-layer', customClass)}
  >
    {children}
    {infoTxt && <Info text={infoTxt} />}
  </div>
);
