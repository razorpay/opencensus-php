import PlatformSettings from './containers/PlatformSettings';
import { withRouter } from 'react-router';

const MagicSettings = (props) => {
  const { location } = props;
  return (
    <div className="content-wrapper no-padding">
      <PlatformSettings path={location.pathname} />
    </div>
  );
};

export default withRouter(MagicSettings);
