import { Info } from 'common/new-ui/Input';
import { classList } from 'common/utils/rzp-utils';

export default ({ children, infoTxt, className, onClick, setRef, ...rest }) => (
  <div
    {...rest}
    onClick={onClick}
    className={classList('wysiwyg-edit-layer', className)}
    ref={setRef}
  >
    {children}
    {infoTxt && <Info text={infoTxt} />}
  </div>
);
