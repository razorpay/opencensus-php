import { Info } from 'component/Input';

export default ({ children, infoTxt, ...rest }) => (
  <div {...rest} class="wysiwyg-edit-layer">
    {children}
    {infoTxt && <Info text={infoTxt} />}
  </div>
);
