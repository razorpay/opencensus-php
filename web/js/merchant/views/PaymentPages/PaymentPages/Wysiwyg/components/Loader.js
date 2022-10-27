import { FullPageLoader } from 'common/components/Loader';
import 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/style.styl';

const Loader = () => (
  <div className="magic-loader">
    <FullPageLoader />
  </div>
);

export default Loader;
