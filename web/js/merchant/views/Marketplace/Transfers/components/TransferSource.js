import { Link } from 'react-router-dom';

const URLS = {
  order_: (source) => `/orders/${source}`,
  pay_: (source) => `/payments/${source}`,
};

export default function TransferSource({ source }) {
  if (!source) return '-';

  let URL;

  Object.keys(URLS).forEach((key) => {
    if (source.includes(key)) {
      URL = URLS[key](source);
    }
  });

  if (!URL) {
    return 'Direct Transfer';
  }

  return <Link to={URL}>{source}</Link>;
}
