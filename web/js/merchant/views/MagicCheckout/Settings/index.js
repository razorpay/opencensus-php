import PlatformSettings from './containers/PlatformSettings';
import { withRouter } from 'common/deprecated/withRouter';

const MagicSettings = (props) => {
  const { location } = props;
  return (
    <div className="content-wrapper no-padding">
      <PlatformSettings path={location.pathname} />
    </div>
  );
};

export default withRouter(MagicSettings);
