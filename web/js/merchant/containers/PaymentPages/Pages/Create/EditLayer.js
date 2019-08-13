import { Info } from 'component/Input';
import { classList } from 'common/util';

export default ({ children, infoTxt, className, onClick, setRef, ...rest }) => (
  <div
    {...rest}
    onClick={onClick}
    class={classList('wysiwyg-edit-layer', className)}
    ref={setRef}
  >
    {children}
    {infoTxt && <Info text={infoTxt} />}
  </div>
);
