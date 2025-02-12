import { Component } from 'react';
import { Link } from 'react-router-dom';

import Banner from 'common/ui/Banner';

class OpenDisputeAlert extends Component {
  state = { open: true };

  handleClose = () => {
    this.setState({ open: false });
  };

  render() {
    const { customClass } = this.props;
    return (
      this.state.open && (
        /* disabling this check for a while */
        // openDisputes > 0 && (
        <div className={`open-dispute-banner ${customClass || ''}`}>
          <Banner>
            {/* disabling this message for a while */}
            {/*There {openDisputes > 1 ? 'are' : 'is'} {openDisputes} open dispute{openDisputes >
              1 && 's'}{' '}
            against {openDisputes < 2 && 'a'} payment{openDisputes > 1 && 's'}&nbsp;
            that needs your attention. &nbsp;<Link to="/disputes">
              Show Disputes
            </Link>
            */}
            <span className="icon i-info-outline" />
            &nbsp; You can now view all your disputes on the dashboard.{' '}
            <Link to="/disputes">Show Disputes</Link>
            <i className="i i-close pull-right" onClick={this.handleClose} />
          </Banner>
        </div>
      )
    );
  }
}

export default OpenDisputeAlert;
